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

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$returnId = $data['return_id'];

try {
    $pdo->beginTransaction();
    
    // Get return details
    $stmt = $pdo->prepare("SELECT * FROM sales_returns WHERE id = ? AND status = 'pending'");
    $stmt->execute([$returnId]);
    $return = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$return) {
        throw new Exception('Return not found or already processed');
    }
    
    // Get return items
    $stmt = $pdo->prepare("SELECT * FROM return_items WHERE return_id = ?");
    $stmt->execute([$returnId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Restore stock if needed
    $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
    
    foreach ($items as $item) {
        if ($item['restore_stock']) {
            $updateStock->execute([$item['quantity'], $item['product_id']]);
        }
    }
    
    // Update customer credit if refund method is credit
    if ($return['refund_method'] === 'credit' && $return['customer_id']) {
        $stmt = $pdo->prepare("UPDATE customers SET credit_balance = credit_balance + ? WHERE id = ?");
        $stmt->execute([$return['total_refund'], $return['customer_id']]);
    }
    
    // Update return status
    $stmt = $pdo->prepare("UPDATE sales_returns SET status = 'approved' WHERE id = ?");
    $stmt->execute([$returnId]);
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_log (user_id, action, module, description)
        VALUES (?, 'return_approved', 'Returns', ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        "Approved return {$return['return_number']}"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Return approved successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
