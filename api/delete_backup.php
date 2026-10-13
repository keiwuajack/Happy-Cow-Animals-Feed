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
$filename = basename($data['filename']);
$filepath = security_backup_dir() . $filename;

if (file_exists($filepath)) {
    if (unlink($filepath)) {
        // Log activity
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                    VALUES ($user_id, 'Delete Backup', 'System', 'Deleted backup file: $filename', '$ip')";
        query($log_sql);
        
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting backup']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Backup file not found']);
}
?>
