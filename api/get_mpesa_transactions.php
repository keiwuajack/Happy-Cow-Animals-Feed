<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$status = isset($_GET['status']) ? $_GET['status'] : null;
$checkoutRequest = trim((string) ($_GET['checkout_request'] ?? ''));
$isCashier = strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';

if ($isCashier && $checkoutRequest === '') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'A checkout request is required']);
    exit();
}

$sql = "SELECT * FROM mpesa_transactions WHERE 1=1";

if ($checkoutRequest !== '') {
    $sql .= " AND checkout_request_id = '" . $conn->real_escape_string($checkoutRequest) . "'";
}

if ($status) {
    $sql .= " AND status = '" . $conn->real_escape_string($status) . "'";
}

$sql .= " ORDER BY created_at DESC LIMIT $limit";

$result = $conn->query($sql);

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

echo json_encode([
    'success' => true,
    'transactions' => $transactions,
    'count' => count($transactions)
]);
?>
