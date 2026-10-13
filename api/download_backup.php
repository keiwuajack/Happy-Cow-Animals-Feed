<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    die('Unauthorized');
}

$filename = isset($_GET['file']) ? basename($_GET['file']) : '';
$filepath = security_backup_dir() . $filename;

if (file_exists($filepath)) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    readfile($filepath);
    exit;
} else {
    die('File not found');
}
?>
