<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sql = "SELECT sa.*, p.name as product_name, p.unit, u.full_name as adjusted_by_name
        FROM stock_adjustments sa
        JOIN products p ON sa.product_id = p.id
        JOIN users u ON sa.adjusted_by = u.id
        ORDER BY sa.created_at DESC";

$result = query($sql);
$adjustments = [];

while ($row = $result->fetch_assoc()) {
    $adjustments[] = $row;
}

echo json_encode([
    'success' => true,
    'adjustments' => $adjustments
]);
?>
