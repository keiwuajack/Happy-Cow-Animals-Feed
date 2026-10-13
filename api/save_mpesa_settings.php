<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

// Check if settings exist
$check = $conn->query("SELECT id FROM mpesa_settings LIMIT 1");

if ($check->num_rows > 0) {
    // Update existing settings
    $stmt = $conn->prepare("UPDATE mpesa_settings SET consumer_key = ?, consumer_secret = ?, shortcode = ?, passkey = ?, callback_url = ?, environment = ?, enabled = ?, updated_at = NOW() WHERE id = 1");
    $stmt->bind_param("ssssssi", 
        $data['consumer_key'],
        $data['consumer_secret'],
        $data['shortcode'],
        $data['passkey'],
        $data['callback_url'],
        $data['environment'],
        $data['enabled']
    );
} else {
    // Insert new settings
    $stmt = $conn->prepare("INSERT INTO mpesa_settings (consumer_key, consumer_secret, shortcode, passkey, callback_url, environment, enabled) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssi", 
        $data['consumer_key'],
        $data['consumer_secret'],
        $data['shortcode'],
        $data['passkey'],
        $data['callback_url'],
        $data['environment'],
        $data['enabled']
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}
?>
