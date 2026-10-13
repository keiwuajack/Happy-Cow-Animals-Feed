<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Total logs
$sql = "SELECT COUNT(*) as count FROM activity_log";
$result = query($sql);
$total_logs = $result->fetch_assoc()['count'];

// Today's logs
$sql = "SELECT COUNT(*) as count FROM activity_log WHERE DATE(created_at) = CURDATE()";
$result = query($sql);
$today_logs = $result->fetch_assoc()['count'];

// Active users (users who performed actions today)
$sql = "SELECT COUNT(DISTINCT user_id) as count FROM activity_log WHERE DATE(created_at) = CURDATE()";
$result = query($sql);
$active_users = $result->fetch_assoc()['count'];

// Critical actions (deletes, updates to users, etc.)
$sql = "SELECT COUNT(*) as count FROM activity_log 
        WHERE action LIKE '%Delete%' OR module = 'Users'";
$result = query($sql);
$critical_actions = $result->fetch_assoc()['count'];

echo json_encode([
    'success' => true,
    'total_logs' => $total_logs,
    'today_logs' => $today_logs,
    'active_users' => $active_users,
    'critical_actions' => $critical_actions
]);
?>
