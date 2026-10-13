<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get last 7 days of sales
$labels = [];
$values = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('M d', strtotime("-$i days"));
    
    $sql = "SELECT COALESCE(SUM(total), 0) as total 
            FROM sales 
            WHERE DATE(created_at) = '$date'";
    
    $result = query($sql);
    $row = $result->fetch_assoc();
    
    $labels[] = $label;
    $values[] = floatval($row['total']);
}

echo json_encode([
    'success' => true,
    'labels' => $labels,
    'values' => $values
]);
?>
