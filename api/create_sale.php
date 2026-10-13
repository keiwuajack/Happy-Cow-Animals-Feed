<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || empty($data['items']) || !is_array($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'At least one item is required']);
    exit();
}

$allowedPaymentMethods = ['cash', 'mpesa', 'card', 'credit'];
$payment_method = $data['payment_method'] ?? '';
if (!in_array($payment_method, $allowedPaymentMethods, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

// Generate invoice number
$invoice_number = 'INV-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

$customer_id = !empty($data['customer_id']) ? intval($data['customer_id']) : 'NULL';
$user_id = $_SESSION['user_id'];
$discount = max(0, floatval($data['discount'] ?? 0));
$amount_paid = max(0, floatval($data['amount_paid'] ?? 0));
$payment_details = isset($data['payment_details']) ? sanitize($data['payment_details']) : 'NULL';
$notes = sanitize($data['notes'] ?? '');

// Cashier sales must belong to an open shift when shift management is installed.
$shiftTable = query("SHOW TABLES LIKE 'cashier_shifts'");
if ($shiftTable && $shiftTable->num_rows > 0) {
    $openShift = query("SELECT id FROM cashier_shifts WHERE user_id = $user_id AND status = 'open' LIMIT 1");
    if (!$openShift || !$openShift->fetch_assoc()) {
        echo json_encode([
            'success' => false,
            'requires_shift' => true,
            'message' => 'Open a cashier shift before completing a sale.'
        ]);
        exit();
    }
}

// Start transaction
$conn->begin_transaction();

try {
    $subtotal = 0;
    $validatedItems = [];

    foreach ($data['items'] as $item) {
        $product_id = intval($item['id'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        if ($product_id <= 0 || $quantity <= 0) {
            throw new Exception('Invalid product or quantity');
        }

        $productResult = query("SELECT id, name, selling_price, quantity, status FROM products WHERE id = $product_id FOR UPDATE");
        $product = $productResult ? $productResult->fetch_assoc() : null;
        if (!$product || $product['status'] !== 'active') {
            throw new Exception('Product is unavailable');
        }
        if ($quantity > intval($product['quantity'])) {
            throw new Exception('Insufficient stock for ' . $product['name']);
        }

        $unit_price = floatval($product['selling_price']);
        $item_subtotal = $quantity * $unit_price;
        $subtotal += $item_subtotal;
        $batchAllocations = [];
        $batchTable = query("SHOW TABLES LIKE 'product_batches'");
        if ($batchTable && $batchTable->num_rows > 0) {
            $batchResult = query("SELECT id, quantity, expiry_date FROM product_batches WHERE product_id = $product_id AND quantity > 0 ORDER BY expiry_date IS NULL, expiry_date ASC, id ASC FOR UPDATE");
            $remaining = $quantity;
            while ($batchResult && $batch = $batchResult->fetch_assoc()) {
                if ($remaining <= 0) break;
                $allocated = min($remaining, (int) $batch['quantity']);
                $batchAllocations[] = ['id' => (int) $batch['id'], 'quantity' => $allocated];
                $remaining -= $allocated;
            }
            if ($remaining > 0 && count($batchAllocations) > 0) {
                throw new Exception('Insufficient quantity in configured batches for ' . $product['name']);
            }
        }

        $validatedItems[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'quantity' => $quantity,
            'price' => $unit_price,
            'subtotal' => $item_subtotal,
            'batch_allocations' => $batchAllocations
        ];
    }

    if ($discount > $subtotal) {
        throw new Exception('Discount cannot exceed subtotal');
    }
    require_once '../config/settings_helper.php';
    $tax = round(($subtotal - $discount) * hc_tax_rate() / 100, 2);
    $total = $subtotal - $discount + $tax;
    if ($amount_paid > 0 && $amount_paid < $total && $payment_method !== 'credit') {
        throw new Exception('Amount paid is less than total');
    }
    $payment_status = ($amount_paid >= $total) ? 'paid' : (($amount_paid > 0) ? 'partial' : 'unpaid');

    // Insert sale
    $payment_details_clause = $payment_details !== 'NULL' ? "'$payment_details'" : "NULL";
    $sql = "INSERT INTO sales (invoice_number, customer_id, user_id, subtotal, discount, tax, total, amount_paid, payment_method, payment_details, payment_status, notes) 
            VALUES ('$invoice_number', $customer_id, $user_id, $subtotal, $discount, $tax, $total, $amount_paid, '$payment_method', $payment_details_clause, '$payment_status', '$notes')";
    
    if (!query($sql)) {
        throw new Exception('Error creating sale');
    }
    
    $sale_id = $conn->insert_id;

    // Link the sale to the cashier's open shift when the advanced schema is installed.
    $shiftColumn = query("SHOW COLUMNS FROM sales LIKE 'shift_id'");
    if ($shiftColumn && $shiftColumn->num_rows > 0) {
        $openShift = query("SELECT id FROM cashier_shifts WHERE user_id = $user_id AND status = 'open' ORDER BY id DESC LIMIT 1");
        if ($openShift && ($shift = $openShift->fetch_assoc())) {
            query("UPDATE sales SET shift_id = {$shift['id']} WHERE id = $sale_id");
        }
    }
    
    // Insert sale items and update product quantities
    foreach ($validatedItems as $item) {
        $product_id = $item['id'];
        $product_name = sanitize($item['name']);
        $quantity = $item['quantity'];
        $unit_price = $item['price'];
        $item_subtotal = $item['subtotal'];
        
        // Insert sale item
        $cost_row = query("SELECT cost_price FROM products WHERE id = $product_id")->fetch_assoc();
        $cost_price = (float) ($cost_row['cost_price'] ?? 0);
        $sql = "INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, cost_price, subtotal) 
                VALUES ($sale_id, $product_id, '$product_name', $quantity, $unit_price, $cost_price, $item_subtotal)";
        
        if (!query($sql)) {
            throw new Exception('Error adding sale item');
        }

        $sale_item_id = $conn->insert_id;
        if (!empty($item['batch_allocations'])) {
            foreach ($item['batch_allocations'] as $allocation) {
                $batch_id = (int) $allocation['id'];
                $batch_quantity = (int) $allocation['quantity'];
                if (!query("UPDATE product_batches SET quantity = quantity - $batch_quantity WHERE id = $batch_id AND quantity >= $batch_quantity")) {
                    throw new Exception('Error updating product batch');
                }
                if (!query("INSERT INTO sale_item_batches (sale_item_id, batch_id, quantity) VALUES ($sale_item_id, $batch_id, $batch_quantity)")) {
                    throw new Exception('Error recording product batch allocation');
                }
            }
        }
        
        // Update product quantity
        $sql = "UPDATE products SET quantity = quantity - $quantity WHERE id = $product_id";
        
        if (!query($sql)) {
            throw new Exception('Error updating product quantity');
        }
    }
    
    // Award loyalty points if customer is selected
    $points_earned = 0;
    if ($customer_id !== 'NULL' && $payment_status === 'paid') {
        // Get loyalty settings
        $settings_sql = "SELECT points_per_amount FROM loyalty_settings WHERE enabled = 1 LIMIT 1";
        $settings_result = query($settings_sql);
        
        if ($settings_result && $settings_result->num_rows > 0) {
            $settings = $settings_result->fetch_assoc();
            $points_per_amount = max(1, intval($settings['points_per_amount']));
            
            // Calculate points (1 point per X amount)
            $points_earned = floor($total / $points_per_amount);
            
            if ($points_earned > 0) {
                // Update customer points
                $sql = "UPDATE customers SET loyalty_points = loyalty_points + $points_earned WHERE id = $customer_id";
                query($sql);
                
                // Get new balance
                $balance_sql = "SELECT loyalty_points FROM customers WHERE id = $customer_id";
                $balance_result = query($balance_sql);
                $new_balance = $balance_result->fetch_assoc()['loyalty_points'];
                
                // Record points history
                $description = "Points earned from sale $invoice_number";
                $sql = "INSERT INTO customer_points_history (customer_id, type, points, description, balance_after, sale_id) 
                        VALUES ($customer_id, 'earned', $points_earned, '$description', $new_balance, $sale_id)";
                query($sql);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale_id' => $sale_id,
        'invoice_number' => $invoice_number,
        'points_earned' => $points_earned
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
