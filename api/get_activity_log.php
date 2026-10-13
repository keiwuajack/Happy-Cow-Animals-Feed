<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : null;
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : null;

$sql = "SELECT a.*, u.full_name as user_name
        FROM activity_log a
        JOIN users u ON a.user_id = u.id";

$where = [];
if ($start_date && $end_date) {
    $where[] = "DATE(a.created_at) BETWEEN '$start_date' AND '$end_date'";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY a.created_at DESC LIMIT 1000";

$result = query($sql);
$activities = [];

while ($row = $result->fetch_assoc()) {
    $activities[] = $row;
}

echo json_encode([
    'success' => true,
    'activities' => $activities
]);
?>
