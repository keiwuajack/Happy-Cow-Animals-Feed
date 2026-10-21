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
$payment_details = null;
if (array_key_exists('payment_details', $data) && $data['payment_details'] !== null && $data['payment_details'] !== '') {
    $paymentDetailsInput = $data['payment_details'];
    if (is_string($paymentDetailsInput)) {
        $paymentDetailsInput = json_decode($paymentDetailsInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
            exit();
        }
    }

    if (!is_array($paymentDetailsInput)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
        exit();
    }

    $payment_details = json_encode($paymentDetailsInput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payment_details === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
        exit();
    }
}
$notes = sanitize($data['notes'] ?? '');

// Cashier sales must belong to an open shift when shift management is installed.
$shiftTable = query("SHOW TABLES LIKE 'cashier_shifts'");
if ($shiftTable && $shiftTable->num_rows > 0) {
    $openShiftStmt = $conn->prepare("SELECT id FROM cashier_shifts WHERE user_id = ? AND status = 'open' LIMIT 1");
    $openShiftStmt->bind_param('i', $user_id);
    $openShiftStmt->execute();
    $openShift = $openShiftStmt->get_result();
    $openShiftStmt->close();
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

        $productStmt = $conn->prepare("SELECT id, name, selling_price, quantity, status FROM products WHERE id = ? FOR UPDATE");
        $productStmt->bind_param('i', $product_id);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        $product = $productResult ? $productResult->fetch_assoc() : null;
        $productStmt->close();
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
            $batchStmt = $conn->prepare("SELECT id, quantity, expiry_date FROM product_batches WHERE product_id = ? AND quantity > 0 ORDER BY expiry_date IS NULL, expiry_date ASC, id ASC FOR UPDATE");
            $batchStmt->bind_param('i', $product_id);
            $batchStmt->execute();
            $batchResult = $batchStmt->get_result();
            $remaining = $quantity;
            while ($batchResult && $batch = $batchResult->fetch_assoc()) {
                if ($remaining <= 0) break;
                $allocated = min($remaining, (int) $batch['quantity']);
                $batchAllocations[] = ['id' => (int) $batch['id'], 'quantity' => $allocated];
                $remaining -= $allocated;
            }
            $batchStmt->close();
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
    $customer_id_value = ($customer_id === 'NULL' || $customer_id === null || (int) $customer_id === 0) ? null : (int) $customer_id;
    $saleInsertStmt = $conn->prepare("INSERT INTO sales (invoice_number, customer_id, user_id, subtotal, discount, tax, total, amount_paid, payment_method, payment_details, payment_status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $saleInsertStmt->bind_param('siidddddssss', $invoice_number, $customer_id_value, $user_id, $subtotal, $discount, $tax, $total, $amount_paid, $payment_method, $payment_details, $payment_status, $notes);
    if (!$saleInsertStmt->execute()) {
        $saleInsertStmt->close();
        throw new Exception('Error creating sale');
    }
    $sale_id = $conn->insert_id;
    $saleInsertStmt->close();

    // Link the sale to the cashier's open shift when the advanced schema is installed.
    $shiftColumn = query("SHOW COLUMNS FROM sales LIKE 'shift_id'");
    if ($shiftColumn && $shiftColumn->num_rows > 0) {
        $openShiftStmt = $conn->prepare("SELECT id FROM cashier_shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
        $openShiftStmt->bind_param('i', $user_id);
        $openShiftStmt->execute();
        $openShift = $openShiftStmt->get_result();
        $openShiftStmt->close();
        if ($openShift && ($shift = $openShift->fetch_assoc())) {
            $shiftUpdateStmt = $conn->prepare("UPDATE sales SET shift_id = ? WHERE id = ?");
            $shiftUpdateStmt->bind_param('ii', $shift['id'], $sale_id);
            $shiftUpdateStmt->execute();
            $shiftUpdateStmt->close();
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
        $costStmt = $conn->prepare("SELECT cost_price FROM products WHERE id = ?");
        $costStmt->bind_param('i', $product_id);
        $costStmt->execute();
        $costRow = $costStmt->get_result()->fetch_assoc();
        $costStmt->close();
        $cost_price = (float) ($costRow['cost_price'] ?? 0);
        $saleItemStmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, cost_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $saleItemStmt->bind_param('iisiddd', $sale_id, $product_id, $product_name, $quantity, $unit_price, $cost_price, $item_subtotal);
        if (!$saleItemStmt->execute()) {
            $saleItemStmt->close();
            throw new Exception('Error adding sale item');
        }
        $sale_item_id = $conn->insert_id;
        $saleItemStmt->close();

        if (!empty($item['batch_allocations'])) {
            foreach ($item['batch_allocations'] as $allocation) {
                $batch_id = (int) $allocation['id'];
                $batch_quantity = (int) $allocation['quantity'];
                $batchUpdateStmt = $conn->prepare("UPDATE product_batches SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
                $batchUpdateStmt->bind_param('iii', $batch_quantity, $batch_id, $batch_quantity);
                if (!$batchUpdateStmt->execute() || $batchUpdateStmt->affected_rows === 0) {
                    $batchUpdateStmt->close();
                    throw new Exception('Error updating product batch');
                }
                $batchUpdateStmt->close();

                $saleItemBatchStmt = $conn->prepare("INSERT INTO sale_item_batches (sale_item_id, batch_id, quantity) VALUES (?, ?, ?)");
                $saleItemBatchStmt->bind_param('iii', $sale_item_id, $batch_id, $batch_quantity);
                if (!$saleItemBatchStmt->execute()) {
                    $saleItemBatchStmt->close();
                    throw new Exception('Error recording product batch allocation');
                }
                $saleItemBatchStmt->close();
            }
        }
        
        // Update product quantity
        $productQtyStmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
        $productQtyStmt->bind_param('ii', $quantity, $product_id);
        if (!$productQtyStmt->execute()) {
            $productQtyStmt->close();
            throw new Exception('Error updating product quantity');
        }
        $productQtyStmt->close();
    }
    
    // Award loyalty points if customer is selected
    $points_earned = 0;
    if ($customer_id_value !== null && $payment_status === 'paid') {
        $settingsStmt = $conn->prepare("SELECT points_per_amount FROM loyalty_settings WHERE enabled = 1 LIMIT 1");
        $settingsStmt->execute();
        $settingsResult = $settingsStmt->get_result();
        $settingsStmt->close();

        if ($settingsResult && $settingsResult->num_rows > 0) {
            $settings = $settingsResult->fetch_assoc();
            $points_per_amount = max(1, intval($settings['points_per_amount']));

            $points_earned = (int) floor($total / $points_per_amount);

            if ($points_earned > 0) {
                $pointsUpdateStmt = $conn->prepare("UPDATE customers SET loyalty_points = loyalty_points + ? WHERE id = ?");
                $pointsUpdateStmt->bind_param('ii', $points_earned, $customer_id_value);
                $pointsUpdateStmt->execute();
                $pointsUpdateStmt->close();

                $balanceStmt = $conn->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
                $balanceStmt->bind_param('i', $customer_id_value);
                $balanceStmt->execute();
                $balanceResult = $balanceStmt->get_result();
                $new_balance = $balanceResult && $balanceResult->num_rows > 0 ? $balanceResult->fetch_assoc()['loyalty_points'] : 0;
                $balanceStmt->close();

                $description = 'Points earned from sale ' . $invoice_number;
                $historyStmt = $conn->prepare("INSERT INTO customer_points_history (customer_id, type, points, description, balance_after, sale_id) VALUES (?, 'earned', ?, ?, ?, ?)");
                $historyStmt->bind_param('iisii', $customer_id_value, $points_earned, $description, $new_balance, $sale_id);
                $historyStmt->execute();
                $historyStmt->close();
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
