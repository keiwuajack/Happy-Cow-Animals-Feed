<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$result = $conn->query("SELECT id, provider, phone_number_id, from_number, enabled, send_receipts, send_alerts FROM whatsapp_settings LIMIT 1");
if (!$result || !$result->num_rows) {
    echo json_encode(['success' => false, 'message' => 'No settings found']);
    exit;
}
echo json_encode(['success' => true, 'settings' => $result->fetch_assoc()]);
?>
