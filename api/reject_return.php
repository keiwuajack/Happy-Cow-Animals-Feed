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
$reason = trim((string) ($data['reason'] ?? ''));
if ($returnId <= 0 || $reason === '') {
    echo json_encode(['success' => false, 'message' => 'Return and rejection reason are required']);
    exit;
}

$conn->begin_transaction();
try {
    $returnStmt = $conn->prepare("SELECT return_number FROM sales_returns WHERE id = ? AND status = 'pending' FOR UPDATE");
    $returnStmt->bind_param('i', $returnId);
    $returnStmt->execute();
    $returnResult = $returnStmt->get_result();
    $return = $returnResult ? $returnResult->fetch_assoc() : null;
    $returnStmt->close();
    if (!$return) {
        throw new Exception('Return not found or already processed');
    }

    $rejectionNote = ' [Rejected: ' . $reason . ']';
    $updateStmt = $conn->prepare("UPDATE sales_returns SET status = 'rejected', reason = CONCAT(COALESCE(reason, ''), ?) WHERE id = ?");
    $updateStmt->bind_param('si', $rejectionNote, $returnId);
    if (!$updateStmt->execute()) {
        $updateStmt->close();
        throw new Exception('Unable to reject return');
    }
    $updateStmt->close();

    $userId = intval($_SESSION['user_id']);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $description = 'Rejected return ' . $return['return_number'];
    $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, module, description, ip_address) VALUES (?, 'Reject Return', 'Returns', ?, ?)");
    $logStmt->bind_param('iss', $userId, $description, $ip);
    $logStmt->execute();
    $logStmt->close();
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Return rejected successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
