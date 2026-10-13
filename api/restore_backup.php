<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();
if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'A valid SQL backup file is required']);
    exit;
}
$extension = strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION));
if ($extension !== 'sql') {
    echo json_encode(['success' => false, 'message' => 'Only SQL backup files are supported']);
    exit;
}
$tempFile = tempnam(sys_get_temp_dir(), 'pos_restore_');
if (!$tempFile || !move_uploaded_file($_FILES['backup_file']['tmp_name'], $tempFile)) {
    echo json_encode(['success' => false, 'message' => 'Unable to read backup file']);
    exit;
}
$mysql = 'C:\\xampp1\\mysql\\bin\\mysql.exe';
$command = escapeshellarg($mysql) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' --host=' . escapeshellarg(DB_HOST) . ' ' . escapeshellarg(DB_NAME) . ' < ' . escapeshellarg($tempFile) . ' 2>&1';
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);
unlink($tempFile);
if ($returnCode !== 0) {
    echo json_encode(['success' => false, 'message' => 'Backup restore failed']);
    exit;
}
echo json_encode(['success' => true, 'message' => 'Backup restored successfully']);
?>
