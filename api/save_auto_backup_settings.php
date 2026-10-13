<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$enabled = !empty($data['enabled']) ? '1' : '0';
$frequency = in_array($data['frequency'] ?? '', ['daily', 'weekly', 'monthly'], true) ? $data['frequency'] : 'daily';
$keep = max(1, min(30, intval($data['keep_backups'] ?? 7)));
foreach ([
    'auto_backup_enabled' => $enabled,
    'backup_frequency' => $frequency,
    'keep_backups' => (string) $keep
] as $key => $value) {
    $safeKey = sanitize($key);
    $safeValue = sanitize($value);
    query("INSERT INTO settings (setting_key, setting_value) VALUES ('$safeKey', '$safeValue') ON DUPLICATE KEY UPDATE setting_value = '$safeValue'");
}
echo json_encode(['success' => true, 'message' => 'Automatic backup settings saved']);
?>
