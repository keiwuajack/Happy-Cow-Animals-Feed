<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$deleted = 0;
foreach (glob(security_backup_dir() . '*.sql') ?: [] as $file) {
    if (filemtime($file) < strtotime('-30 days') && unlink($file)) {
        $deleted++;
    }
}
echo json_encode(['success' => true, 'deleted' => $deleted]);
?>
