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
        si.product_name,
        p.id as product_id,
        c.name as category_name,
        SUM(si.quantity) as total_quantity,
        SUM(si.subtotal) as total_revenue,
        AVG(si.unit_price) as avg_price
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'
        GROUP BY si.product_name, p.id, c.name
        ORDER BY total_quantity DESC
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
