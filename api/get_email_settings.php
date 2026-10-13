<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$result = $conn->query("SELECT * FROM email_settings LIMIT 1");

if ($result->num_rows > 0) {
    $settings = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No settings found'
    ]);
}
?>
