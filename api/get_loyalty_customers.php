<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sql = "SELECT 
        c.id,
        c.name,
        c.phone,
        c.loyalty_points,
        c.created_at,
        COALESCE(SUM(CASE WHEN cph.type = 'earned' THEN cph.points ELSE 0 END), 0) as lifetime_points
        FROM customers c
        LEFT JOIN customer_points_history cph ON c.id = cph.customer_id
        WHERE c.loyalty_points > 0 OR cph.id IS NOT NULL
        GROUP BY c.id, c.name, c.phone, c.loyalty_points, c.created_at
        ORDER BY c.loyalty_points DESC";

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
