<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$quotationId = $data['quotation_id'];

try {
    $pdo->beginTransaction();
    
    // Get quotation details
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ? AND status = 'pending'");
    $stmt->execute([$quotationId]);
    $quotation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quotation) {
        throw new Exception('Quotation not found or already converted');
    }
    
    // Get quotation items
    $stmt = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $stmt->execute([$quotationId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        $quotation['total']
    ]);
    
    $saleId = $pdo->lastInsertId();
    
    // Create sale items and update stock
    $stmt = $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, cost_price, subtotal)
        SELECT ?, p.id, p.name, ?, ?, p.cost_price, ? FROM products p WHERE p.id = ?
    ");
    
    $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
    
    foreach ($items as $item) {
        $stmt->execute([
            $saleId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['total']
        ]);
        
        $updateStock->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Update quotation status
    $stmt = $pdo->prepare("UPDATE quotations SET status = 'converted', converted_sale_id = ? WHERE id = ?");
    $stmt->execute([$saleId, $quotationId]);
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, description)
        VALUES (?, 'quotation_converted', ?)
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
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
