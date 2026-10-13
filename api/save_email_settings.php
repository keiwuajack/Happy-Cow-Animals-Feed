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
$check = $conn->query("SELECT id FROM email_settings LIMIT 1");

if ($check->num_rows > 0) {
    // Update existing settings
    $stmt = $conn->prepare("UPDATE email_settings SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_encryption = ?, from_email = ?, from_name = ?, enabled = ?, send_receipts = ?, send_quotations = ? WHERE id = 1");
    $stmt->bind_param("sisssssiii", 
        $data['smtp_host'],
        $data['smtp_port'],
        $data['smtp_username'],
        $data['smtp_password'],
        $data['smtp_encryption'],
        $data['from_email'],
        $data['from_name'],
        $data['enabled'],
        $data['send_receipts'],
        $data['send_quotations']
    );
} else {
    // Insert new settings
    $stmt = $conn->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, enabled, send_receipts, send_quotations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssiii", 
        $data['smtp_host'],
        $data['smtp_port'],
        $data['smtp_username'],
        $data['smtp_password'],
        $data['smtp_encryption'],
        $data['from_email'],
        $data['from_name'],
        $data['enabled'],
        $data['send_receipts'],
        $data['send_quotations']
    );
}

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Email settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}
?>
