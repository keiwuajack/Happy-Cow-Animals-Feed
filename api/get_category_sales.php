<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get sales by category this month
$sql = "SELECT 
        c.name as category_name,
        COALESCE(SUM(si.subtotal), 0) as total_sales
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        LEFT JOIN sale_items si ON p.id = si.product_id
        LEFT JOIN sales s ON si.sale_id = s.id 
            AND MONTH(s.created_at) = MONTH(CURDATE()) 
            AND YEAR(s.created_at) = YEAR(CURDATE())
        GROUP BY c.id, c.name
        HAVING total_sales > 0
        ORDER BY total_sales DESC";

$result = query($sql);
$labels = [];
$values = [];

while ($row = $result->fetch_assoc()) {
    $labels[] = $row['category_name'];
    $values[] = floatval($row['total_sales']);
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
