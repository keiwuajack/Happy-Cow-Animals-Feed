<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$backupDir = security_backup_dir();
$files = [];

if (is_dir($backupDir)) {
    $fileList = scandir($backupDir);
    
    foreach ($fileList as $file) {
        if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql') {
            $filepath = $backupDir . $file;
            $files[] = [
                'name' => $file,
                'size' => formatBytes(filesize($filepath)),
                'date' => date('Y-m-d H:i:s', filemtime($filepath))
            ];
        }
    }
    
    // Sort by date descending
    usort($files, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

echo json_encode([
    'success' => true,
    'files' => $files
]);
?>
