<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$isCashier = strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
$sql = "SELECT s.*, c.name AS customer_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id";

if ($isCashier) {
    $sql .= " WHERE s.user_id = ?";
}
$sql .= " ORDER BY s.created_at DESC LIMIT 10";

if ($isCashier) {
    $stmt = $conn->prepare($sql);
    $cashierId = (int) $_SESSION['user_id'];
    $stmt->bind_param('i', $cashierId);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = query($sql);
}
$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode([
    'success' => true,
    'transactions' => $transactions
]);
?>
