<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$dateFrom = isset($_GET['from']) ? (string) $_GET['from'] : date('Y-m-d', strtotime('-30 days'));
$dateTo = isset($_GET['to']) ? (string) $_GET['to'] : date('Y-m-d');
foreach ([$dateFrom, $dateTo] as $date) {
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid date range']);
        exit();
    }
}

$isCashier = strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
$scope = $isCashier ? ' AND s.user_id = ?' : '';

$sql = "SELECT s.*, c.name as customer_name, u.full_name as user_name,
        (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE DATE(s.created_at) BETWEEN ? AND ?{$scope}
        ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);
if ($isCashier) {
    $cashierId = (int) $_SESSION['user_id'];
    $stmt->bind_param('ssi', $dateFrom, $dateTo, $cashierId);
} else {
    $stmt->bind_param('ss', $dateFrom, $dateTo);
}
$stmt->execute();
$result = $stmt->get_result();
$sales = [];

while ($row = $result->fetch_assoc()) {
    $sales[] = $row;
}

echo json_encode([
    'success' => true,
    'sales' => $sales
]);
?>
