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
$stmt = $conn->prepare('SELECT username, role, status FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

if ($user['username'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete the default admin user']);
    exit();
}

if ($id === (int) $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot deactivate your own account']);
    exit();
}

if ($user['role'] === 'admin' && $user['status'] === 'active') {
    $activeAdmins = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'active'")->fetch_assoc();
    if ((int) ($activeAdmins['total'] ?? 0) <= 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Keep at least one active administrator account']);
        exit();
    }
}

$sql = "UPDATE users SET status = 'inactive' WHERE id = $id";

if (query($sql)) {
    echo json_encode(['success' => true, 'message' => 'User account deactivated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting user']);
}
?>
