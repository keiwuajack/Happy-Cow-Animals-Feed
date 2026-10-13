<?php
require_once '../config/pdo.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();
    
    // Generate quote number
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(quote_number, 3) AS UNSIGNED)) as max_num FROM quotations WHERE quote_number LIKE 'QT%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $quoteNumber = 'QT' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($data['items'] as $item) {
        $subtotal += $item['quantity'] * $item['unit_price'];
    }
    
    $discount = $data['discount'] ?? 0;
    $total = $subtotal - $discount;
    
    // Insert quotation
    $stmt = $pdo->prepare("
        INSERT INTO quotations (quote_number, customer_id, user_id, subtotal, discount, total, valid_until, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $quoteNumber,
        $data['customer_id'] ?: null,
        $_SESSION['user_id'],
        $subtotal,
        $discount,
        $total,
        $data['valid_until'],
        $data['notes'] ?? null
    ]);
    
    $quotationId = $pdo->lastInsertId();
    
    // Insert quotation items
    $stmt = $pdo->prepare("
        INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price, total)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($data['items'] as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $stmt->execute([
            $quotationId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $itemTotal
        ]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, description)
        VALUES (?, 'quotation_created', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        "Created quotation {$quoteNumber}"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Quotation created successfully',
        'quotation_id' => $quotationId,
        'quote_number' => $quoteNumber
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
