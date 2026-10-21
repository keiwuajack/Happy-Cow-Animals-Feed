<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in again']);
    exit();
}
security_require_same_origin();
security_require_roles(['admin', 'manager']);

$data = json_decode(file_get_contents('php://input'), true);
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit();
}

$stmt = $conn->prepare("UPDATE products SET status = 'inactive' WHERE id = ? AND status <> 'inactive'");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not archive product']);
    exit();
}
$stmt->bind_param('i', $id);
$success = $stmt->execute();
$changed = $stmt->affected_rows;
$stmt->close();

if (!$success) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not archive product']);
    exit();
}

if ($changed > 0) {
    echo json_encode(['success' => true, 'message' => 'Product archived. Past sales and inventory history were kept.']);
    exit();
}

$check = $conn->prepare('SELECT status FROM products WHERE id = ?');
$check->bind_param('i', $id);
$check->execute();
$existing = $check->get_result()->fetch_assoc();
$check->close();

if (!$existing) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

echo json_encode(['success' => true, 'message' => 'Product is already archived']);
?>
