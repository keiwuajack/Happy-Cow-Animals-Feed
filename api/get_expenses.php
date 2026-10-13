<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$sql = "SELECT e.*, u.full_name as added_by_name
        FROM expenses e
        JOIN users u ON e.added_by = u.id
        ORDER BY e.expense_date DESC, e.created_at DESC";

$result = query($sql);
$expenses = [];

while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

echo json_encode([
    'success' => true,
    'expenses' => $expenses
]);
?>
