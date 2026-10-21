<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true) ?: [];

// points_rate = points earned per KSh 100 spent; stored as "KSh spent per point"
$points_rate = max(0.01, (float) ($data['points_rate'] ?? 1));
$points_per_amount = max(1, (int) round(100 / $points_rate));
$redemption_rate = max(0, (float) ($data['redemption_rate'] ?? 1));
$expiry_days = max(0, (int) ($data['expiry_days'] ?? 0));
$welcome_bonus = max(0, (int) ($data['welcome_bonus'] ?? 0));
$auto_enroll = !empty($data['auto_enroll']) ? 1 : 0;
$enabled = !empty($data['enabled']) ? 1 : 0;

$existing = $conn->query("SELECT id FROM loyalty_settings LIMIT 1");
if ($existing && $existing->num_rows > 0) {
    $id = (int) $existing->fetch_assoc()['id'];
    $stmt = $conn->prepare("UPDATE loyalty_settings SET points_per_amount = ?, money_value = ?, expiry_days = ?, welcome_bonus = ?, auto_enroll = ?, enabled = ? WHERE id = ?");
    $stmt->bind_param('idiiiii', $points_per_amount, $redemption_rate, $expiry_days, $welcome_bonus, $auto_enroll, $enabled, $id);
} else {
    $stmt = $conn->prepare("INSERT INTO loyalty_settings (points_per_amount, money_value, expiry_days, welcome_bonus, auto_enroll, enabled) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('idiiii', $points_per_amount, $redemption_rate, $expiry_days, $welcome_bonus, $auto_enroll, $enabled);
}

if ($stmt->execute()) {
    $user_id = (int) $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, module, description, ip_address) VALUES (?, 'Update Settings', 'Loyalty Points', 'Updated loyalty program settings', ?)");
    $logStmt->bind_param('is', $user_id, $ip);
    $logStmt->execute();
    $logStmt->close();
    echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating settings']);
}
