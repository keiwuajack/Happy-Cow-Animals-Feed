<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !in_array(strtolower($_SESSION['role']), ['admin', 'manager'], true)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$returnId = intval($data['return_id'] ?? 0);
$reason = sanitize($data['reason'] ?? '');
if ($returnId <= 0 || $reason === '') {
    echo json_encode(['success' => false, 'message' => 'Return and rejection reason are required']);
    exit;
}

$conn->begin_transaction();
try {
    $returnResult = query("SELECT return_number FROM sales_returns WHERE id = $returnId AND status = 'pending' FOR UPDATE");
    $return = $returnResult ? $returnResult->fetch_assoc() : null;
    if (!$return) {
        throw new Exception('Return not found or already processed');
    }

    $safeReason = sanitize($reason);
    if (!query("UPDATE sales_returns SET status = 'rejected', reason = CONCAT(COALESCE(reason, ''), ' [Rejected: $safeReason]') WHERE id = $returnId")) {
        throw new Exception('Unable to reject return');
    }

    $userId = intval($_SESSION['user_id']);
    query("INSERT INTO activity_log (user_id, action, module, description, ip_address) VALUES ($userId, 'Reject Return', 'Returns', 'Rejected return {$return['return_number']}', '" . sanitize($_SERVER['REMOTE_ADDR'] ?? '') . "')");
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Return rejected successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
