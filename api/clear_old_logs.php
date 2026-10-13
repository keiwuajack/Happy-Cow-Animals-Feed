<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Delete logs older than 90 days
$sql = "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";

if (query($sql)) {
    $deleted = $GLOBALS['conn']->affected_rows;
    
    // Log this action
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                VALUES ($user_id, 'Clear Logs', 'System', 'Cleared $deleted old activity logs (>90 days)', '$ip')";
    query($log_sql);
    
    echo json_encode(['success' => true, 'message' => 'Old logs cleared', 'deleted' => $deleted]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error clearing logs']);
}
?>
