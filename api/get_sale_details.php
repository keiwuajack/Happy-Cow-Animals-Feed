<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$saleId = intval($_GET['id']);

// Get sale details
$sql = "SELECT s.*, c.name as customer_name, u.full_name as user_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = $saleId";

if (strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier') {
    $cashierId = (int) $_SESSION['user_id'];
    $sql .= " AND s.user_id = {$cashierId}";
}

$result = query($sql);
$sale = $result->fetch_assoc();

if (!$sale) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit();
}

// Get sale items
$sql = "SELECT * FROM sale_items WHERE sale_id = $saleId";
$result = query($sql);
$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode([
    'success' => true,
    'sale' => $sale,
    'items' => $items
]);
?>
