<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$quotationId = filter_var($data['quotation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$quotationId || $quotationId < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quotation']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Lock the quotation so two requests cannot convert it at the same time.
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND status = 'pending' FOR UPDATE");
    $stmt->execute([$quotationId]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quotation) {
        throw new Exception('Quotation not found or already converted');
    }
    
    // Get quotation items
    $stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $stmt->execute([$quotationId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$items) {
        throw new Exception('This quotation has no items');
    }
    
    // Generate invoice number
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(invoice_number, 4) AS UNSIGNED)) as max_num FROM sales WHERE invoice_number LIKE 'INV%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $invoiceNumber = 'INV' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    
    // Create sale
    $stmt = $pdo->prepare("
        INSERT INTO sales (invoice_number, customer_id, user_id, subtotal, discount, tax, total, amount_paid, payment_method, payment_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cash', 'paid')
    ");
    
    $stmt->execute([
        $invoiceNumber,
        $quotation['customer_id'],
        $_SESSION['user_id'],
        $quotation['subtotal'],
        $quotation['discount'],
        $quotation['tax'] ?? 0,
        $quotation['total'],
        $quotation['total']
    ]);
    
    $saleId = $pdo->lastInsertId();
    
    // Lock each product, verify stock, insert the quoted line, and decrement stock.
    $productStmt = $pdo->prepare("SELECT id, name, cost_price, quantity, status FROM products WHERE id = ? FOR UPDATE");
    $saleItemStmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, cost_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
    
    foreach ($items as $item) {
        $productId = (int) ($item['product_id'] ?? 0);
        $quantity = (int) ($item['quantity'] ?? 0);
        if ($productId < 1 || $quantity < 1 || !is_numeric($item['unit_price'] ?? null) || !is_numeric($item['total'] ?? null)) {
            throw new Exception('This quotation contains an invalid item');
        }

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product || $product['status'] !== 'active') {
            throw new Exception('A product in this quotation is unavailable');
        }
        if ($quantity > (int) $product['quantity']) {
            throw new Exception('Insufficient stock for ' . $product['name']);
        }

        $saleItemStmt->execute([
            $saleId,
            $productId,
            $product['name'],
            $quantity,
            $item['unit_price'],
            $product['cost_price'],
            $item['total']
        ]);

        $updateStock->execute([$quantity, $productId, $quantity]);
        if ($updateStock->rowCount() !== 1) {
            throw new Exception('Unable to update stock for ' . $product['name']);
        }
    }
    
    // Update quotation status
    $stmt = $pdo->prepare("UPDATE quotations SET status = 'converted', converted_sale_id = ? WHERE id = ? AND status = 'pending'");
    $stmt->execute([$saleId, $quotationId]);
    if ($stmt->rowCount() !== 1) {
        throw new Exception('Quotation was already converted');
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, module, description)
        VALUES (?, 'quotation_converted', 'Quotations', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        "Converted quotation {$quotation['quote_number']} to sale {$invoiceNumber}"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Quotation converted to sale successfully',
        'sale_id' => $saleId,
        'invoice_number' => $invoiceNumber
    ]);
    
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
