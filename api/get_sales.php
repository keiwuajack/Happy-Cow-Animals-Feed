<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$dateFrom = isset($_GET['from']) ? $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

$sql = "SELECT s.*, c.name as customer_name, u.full_name as user_name,
        (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'
        ORDER BY s.created_at DESC";

$result = query($sql);
$sales = [];

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}

echo json_encode([
    'success' => true,
    'sales' => $sales
]);
?>
