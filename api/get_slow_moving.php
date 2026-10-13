<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$start_date = sanitize($_GET['start_date']);
$end_date = sanitize($_GET['end_date']);

$sql = "SELECT 
        p.id,
        p.name,
        p.quantity,
        c.name as category_name,
        COALESCE(SUM(si.quantity), 0) as total_sold,
        MAX(s.created_at) as last_sale_date
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN sale_items si ON p.id = si.product_id
        LEFT JOIN sales s ON si.sale_id = s.id AND DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'
        GROUP BY p.id, p.name, p.quantity, c.name
        HAVING total_sold < 5 OR total_sold IS NULL
        ORDER BY total_sold ASC, last_sale_date ASC
        LIMIT 20";

$result = query($sql);
$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode([
    'success' => true,
    'products' => $products
]);
?>
