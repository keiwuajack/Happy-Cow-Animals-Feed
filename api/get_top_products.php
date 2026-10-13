<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get top 5 products by quantity sold this month
$sql = "SELECT 
        si.product_name,
        SUM(si.quantity) as total_quantity
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        WHERE MONTH(s.created_at) = MONTH(CURDATE()) 
        AND YEAR(s.created_at) = YEAR(CURDATE())
        GROUP BY si.product_name
        ORDER BY total_quantity DESC
        LIMIT 5";

$result = query($sql);
$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['product_name'];
    $values[] = intval($row['total_quantity']);
}

// If no data, show placeholder
if (empty($labels)) {
    $labels = ['No Sales Yet'];
    $values = [0];
}

echo json_encode([
    'success' => true,
    'labels' => $labels,
    'values' => $values
]);
?>
