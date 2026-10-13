<?php
session_start();
require_once '../config/database.php';
require_once '../config/settings_helper.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit(); }
security_require_same_origin();
$d = json_decode(file_get_contents('php://input'), true) ?: [];
$tax = filter_var($d['tax_rate'] ?? null, FILTER_VALIDATE_FLOAT);
if ($tax === false || $tax < 0 || $tax > 100) { echo json_encode(['success' => false, 'message' => 'Tax rate must be between 0 and 100']); exit(); }
$vals = [
    'tax_rate' => (string) $tax,
    'business_name' => trim($d['business_name'] ?? ''),
    'business_tagline' => trim($d['business_tagline'] ?? ''),
    'business_phone' => trim($d['business_phone'] ?? ''),
    'business_email' => trim($d['business_email'] ?? ''),
    'business_address' => trim($d['business_address'] ?? ''),
    'receipt_note' => trim($d['receipt_note'] ?? ''),
];
if ($vals['business_name'] === '') { echo json_encode(['success' => false, 'message' => 'Business name is required']); exit(); }
try {
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    foreach ($vals as $k => $v) { $stmt->bind_param('ss', $k, $v); $stmt->execute(); }
    echo json_encode(['success' => true, 'message' => 'Settings saved']);
} catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Could not save settings']); }
