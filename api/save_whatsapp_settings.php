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
$check = $conn->query("SELECT id FROM whatsapp_settings LIMIT 1");

if ($check->num_rows > 0) {
    // Update existing settings
    $stmt = $conn->prepare("UPDATE whatsapp_settings SET provider = ?, api_key = ?, api_secret = ?, phone_number_id = ?, from_number = ?, enabled = ?, send_receipts = ?, send_alerts = ? WHERE id = 1");
    $stmt->bind_param("ssssssii", 
        $data['provider'],
        $data['api_key'],
        $data['api_secret'],
        $data['phone_number_id'],
        $data['from_number'],
        $data['enabled'],
        $data['send_receipts'],
        $data['send_alerts']
    );
} else {
    // Insert new settings
    $stmt = $conn->prepare("INSERT INTO whatsapp_settings (provider, api_key, api_secret, phone_number_id, from_number, enabled, send_receipts, send_alerts) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssii", 
        $data['provider'],
        $data['api_key'],
        $data['api_secret'],
        $data['phone_number_id'],
        $data['from_number'],
        $data['enabled'],
        $data['send_receipts'],
        $data['send_alerts']
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'WhatsApp settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}
?>
