<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();
security_require_roles(['admin', 'manager']);

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);

// Instead of deleting, set status to inactive
$sql = "UPDATE products SET status = 'inactive' WHERE id = $id";

if (query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting product']);
}
?>
