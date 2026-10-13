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
        c.id,
        c.name as customer_name,
        c.phone,
        c.loyalty_points,
        COUNT(s.id) as purchase_count,
        COALESCE(SUM(s.total), 0) as total_spent
        FROM customers c
        JOIN sales s ON c.id = s.customer_id
        WHERE DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'
        GROUP BY c.id, c.name, c.phone, c.loyalty_points
        ORDER BY total_spent DESC
        LIMIT 20";

$result = query($sql);
$customers = [];

while ($row = $result->fetch_assoc()) {
    $customers[] = $row;
}

echo json_encode([
    'success' => true,
    'customers' => $customers
]);
?>
