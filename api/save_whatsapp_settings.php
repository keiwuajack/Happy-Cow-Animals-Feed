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
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid settings data']);
    exit;
}

$provider = trim((string) ($data['provider'] ?? ''));
$apiKey = trim((string) ($data['api_key'] ?? ''));
$apiSecret = trim((string) ($data['api_secret'] ?? ''));
$phoneNumberId = trim((string) ($data['phone_number_id'] ?? ''));
$fromNumber = trim((string) ($data['from_number'] ?? ''));
$enabled = !empty($data['enabled']) ? 1 : 0;
$sendReceipts = !empty($data['send_receipts']) ? 1 : 0;
$sendAlerts = !empty($data['send_alerts']) ? 1 : 0;

if (!in_array($provider, ['twilio', 'cloud_api'], true)) {
    echo json_encode(['success' => false, 'message' => "Choose Meta WhatsApp Cloud API or Twilio. Africa's Talking is configured as SMS in this POS."]);
    exit;
}

// Check if settings exist
$check = $conn->query("SELECT id, api_key, api_secret FROM whatsapp_settings ORDER BY id LIMIT 1");

if ($check->num_rows > 0) {
    $existing = $check->fetch_assoc();
    $apiKey = $apiKey !== '' ? $apiKey : (string) $existing['api_key'];
    $apiSecret = $apiSecret !== '' ? $apiSecret : (string) ($existing['api_secret'] ?? '');
    if ($enabled && $apiKey === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the provider access token or API key before enabling WhatsApp']);
        exit;
    }
    if ($enabled && $provider === 'cloud_api' && $phoneNumberId === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the Meta Phone Number ID before enabling WhatsApp']);
        exit;
    }
    if ($enabled && $provider === 'twilio' && ($apiSecret === '' || $fromNumber === '')) {
        echo json_encode(['success' => false, 'message' => 'Twilio requires an API secret and its registered WhatsApp sender number']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE whatsapp_settings SET provider = ?, api_key = ?, api_secret = ?, phone_number_id = ?, from_number = ?, enabled = ?, send_receipts = ?, send_alerts = ? WHERE id = ?");
    $stmt->bind_param("sssssiiii", $provider, $apiKey, $apiSecret, $phoneNumberId, $fromNumber, $enabled, $sendReceipts, $sendAlerts, $existing['id']);
} else {
    if ($enabled && $apiKey === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the provider access token or API key before enabling WhatsApp']);
        exit;
    }
    if ($enabled && $provider === 'cloud_api' && $phoneNumberId === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the Meta Phone Number ID before enabling WhatsApp']);
        exit;
    }
    if ($enabled && $provider === 'twilio' && ($apiSecret === '' || $fromNumber === '')) {
        echo json_encode(['success' => false, 'message' => 'Twilio requires an API secret and its registered WhatsApp sender number']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO whatsapp_settings (provider, api_key, api_secret, phone_number_id, from_number, enabled, send_receipts, send_alerts) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssiii", $provider, $apiKey, $apiSecret, $phoneNumberId, $fromNumber, $enabled, $sendReceipts, $sendAlerts);
}

if ($stmt && $stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'WhatsApp settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}
?>
