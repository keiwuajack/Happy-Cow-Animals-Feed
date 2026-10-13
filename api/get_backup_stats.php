<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get database size
$db_size_sql = "SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
    FROM information_schema.TABLES 
    WHERE table_schema = '" . DB_NAME . "'";
$result = query($db_size_sql);
$db_size = $result->fetch_assoc()['size_mb'] . ' MB';

// Count backup files
$backupDir = security_backup_dir();
$total_backups = 0;
$backup_size = 0;
$last_backup = 'Never';

if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    $backup_files = [];
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = $backupDir . $file;
            $total_backups++;
            $backup_size += filesize($filepath);
            $backup_files[] = filemtime($filepath);
        }
    }
    
    if (!empty($backup_files)) {
        $latest = max($backup_files);
        $last_backup = date('Y-m-d H:i', $latest);
    }
}

$backup_size_formatted = $backup_size > 0 ? round($backup_size / 1024 / 1024, 2) . ' MB' : '0 MB';

echo json_encode([
    'success' => true,
    'db_size' => $db_size,
    'total_backups' => $total_backups,
    'last_backup' => $last_backup,
    'backup_size' => $backup_size_formatted
]);
?>
