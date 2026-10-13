<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$customer_id = intval($_GET['customer_id'] ?? 0);
if ($customer_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
    exit();
}

$sql = "SELECT * FROM customer_points_history 
        WHERE customer_id = $customer_id 
        ORDER BY created_at DESC";

$result = query($sql);
$history = [];

while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}

echo json_encode([
    'success' => true,
    'history' => $history
]);
?>
