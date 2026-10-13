<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);

// Check if it's the admin user
$sql = "SELECT username FROM users WHERE id = $id";
$result = query($sql);
$user = $result->fetch_assoc();

if ($user['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete the default admin user']);
    exit();
}

$sql = "UPDATE users SET status = 'inactive' WHERE id = $id";

if (query($sql)) {
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting user']);
}
?>
