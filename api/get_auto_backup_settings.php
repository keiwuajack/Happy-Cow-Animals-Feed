<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$settings = ['enabled' => '0', 'frequency' => 'daily', 'keep_backups' => '7'];
$result = query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('auto_backup_enabled', 'backup_frequency', 'keep_backups')");
while ($row = $result->fetch_assoc()) {
    $key = ['auto_backup_enabled' => 'enabled', 'backup_frequency' => 'frequency', 'keep_backups' => 'keep_backups'][$row['setting_key']];
    $settings[$key] = $row['setting_value'];
}
echo json_encode(['success' => true, 'settings' => $settings]);
?>
