<?php
session_start();
require_once '../config/database.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();
    
    // Generate return number
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING(return_number, 4) AS UNSIGNED)) as max_num FROM sales_returns WHERE return_number LIKE 'RET%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $nextNum = ($result['max_num'] ?? 0) + 1;
    $returnNumber = 'RET' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
    
    // Calculate total refund
    $totalRefund = array_reduce($data['items'], function($sum, $item) {
        return $sum + $item['subtotal'];
    }, 0);
    
    // Insert return
    $stmt = $pdo->prepare("
        INSERT INTO sales_returns (return_number, sale_id, customer_id, total_refund, refund_method, reason, processed_by, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $returnNumber,
        $data['sale_id'],
        $data['customer_id'] ?? null,
        $totalRefund,
        $data['refund_method'],
        $data['reason'],
        $_SESSION['user_id']
    ]);
    
    $returnId = $pdo->lastInsertId();
    
    // Insert return items
    $stmt = $pdo->prepare("
        INSERT INTO return_items (return_id, product_id, quantity, unit_price, subtotal, restore_stock)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($data['items'] as $item) {
        $stmt->execute([
            $returnId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal'],
            !empty($data['restore_stock']) ? 1 : 0
        ]);
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, description)
        VALUES (?, 'return_created', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        "Created return {$returnNumber}"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Return created successfully',
        'return_id' => $returnId,
        'return_number' => $returnNumber
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
