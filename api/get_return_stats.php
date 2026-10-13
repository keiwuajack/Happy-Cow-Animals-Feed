<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$stats = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'total_refund' => 0
];

$result = query("SELECT COUNT(*) AS count FROM sales_returns");
$stats['total'] = intval($result->fetch_assoc()['count']);
$result = query("SELECT COUNT(*) AS count FROM sales_returns WHERE status = 'pending'");
$stats['pending'] = intval($result->fetch_assoc()['count']);
$result = query("SELECT COUNT(*) AS count FROM sales_returns WHERE status = 'approved'");
$stats['approved'] = intval($result->fetch_assoc()['count']);
$result = query("SELECT COALESCE(SUM(total_refund), 0) AS total FROM sales_returns WHERE status = 'approved'");
$stats['total_refund'] = $result->fetch_assoc()['total'];

echo json_encode(['success' => true, 'stats' => $stats]);
?>