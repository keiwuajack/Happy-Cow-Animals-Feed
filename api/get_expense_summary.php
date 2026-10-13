<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Today's expenses
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) = CURDATE()";
$result = query($sql);
$today = $result->fetch_assoc()['total'];

// This week's expenses
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE YEARWEEK(expense_date) = YEARWEEK(CURDATE())";
$result = query($sql);
$week = $result->fetch_assoc()['total'];

// This month's expenses
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
$result = query($sql);
$month = $result->fetch_assoc()['total'];

// Total expenses
$sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses";
$result = query($sql);
$total = $result->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'today' => $today,
    'week' => $week,
    'month' => $month,
    'total' => $total
]);
?>
