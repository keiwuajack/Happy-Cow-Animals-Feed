<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get revenue and expenses for each week of the current month
$labels = [];
$revenue = [];
$expenses = [];

// Get number of weeks in current month
$year = date('Y');
$month = date('m');
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');

// Get data by week
for ($week = 1; $week <= 4; $week++) {
    $weekStart = date('Y-m-d', strtotime($firstDay . " + " . (($week - 1) * 7) . " days"));
    $weekEnd = date('Y-m-d', strtotime($weekStart . " + 6 days"));
    
    // Don't go beyond current month
    if (strtotime($weekEnd) > strtotime($lastDay)) {
        $weekEnd = $lastDay;
    }
    
    $labels[] = "Week $week";
    
    // Get revenue for this week
    $revSql = "SELECT COALESCE(SUM(total), 0) as total 
               FROM sales 
               WHERE DATE(created_at) BETWEEN '$weekStart' AND '$weekEnd'";
    $revResult = query($revSql);
    $revRow = $revResult->fetch_assoc();
    $revenue[] = floatval($revRow['total']);
    
    // Get expenses for this week
    $expSql = "SELECT COALESCE(SUM(amount), 0) as total 
               FROM expenses 
               WHERE DATE(expense_date) BETWEEN '$weekStart' AND '$weekEnd'";
    $expResult = query($expSql);
    $expRow = $expResult->fetch_assoc();
    $expenses[] = floatval($expRow['total']);
    
    // Break if we've reached current date
    if (strtotime($weekEnd) >= strtotime($lastDay)) {
        break;
    }
}

echo json_encode([
    'success' => true,
    'labels' => $labels,
    'revenue' => $revenue,
    'expenses' => $expenses
]);
?>
