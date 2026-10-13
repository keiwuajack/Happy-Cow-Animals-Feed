<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$showAll = isset($_GET['all']) && $_GET['all'] == 1;

if ($showAll) {
    $sql = "SELECT * FROM customers ORDER BY name ASC";
} else {
    $sql = "SELECT * FROM customers WHERE status = 'active' ORDER BY name ASC";
}

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
