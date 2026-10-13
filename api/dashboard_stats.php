<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get today's sales
$today = date('Y-m-d');
$sql = "SELECT COALESCE(SUM(total), 0) as total FROM sales WHERE DATE(created_at) = '$today'";
$result = query($sql);
$todaySales = $result->fetch_assoc()['total'];

// Get total products
$sql = "SELECT COUNT(*) as total FROM products WHERE status = 'active'";
$result = query($sql);
$totalProducts = $result->fetch_assoc()['total'];

// Get total customers
$sql = "SELECT COUNT(*) as total FROM customers WHERE status = 'active'";
$result = query($sql);
$totalCustomers = $result->fetch_assoc()['total'];

// Get low stock items
$sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= reorder_level AND status = 'active'";
$result = query($sql);
$lowStockItems = $result->fetch_assoc()['total'];

$lowStockProducts = [];
$lowStockResult = query("SELECT name, quantity, unit, reorder_level FROM products WHERE quantity <= reorder_level AND status = 'active' ORDER BY quantity ASC, name ASC LIMIT 6");
if ($lowStockResult) {
    while ($row = $lowStockResult->fetch_assoc()) {
        $lowStockProducts[] = $row;
    }
}

// Get outstanding customer credit when the advanced customer fields are installed.
$creditOutstanding = 0;
$creditResult = query("SELECT COALESCE(SUM(credit_balance), 0) as total FROM customers WHERE status = 'active'");
if ($creditResult) {
    $creditOutstanding = $creditResult->fetch_assoc()['total'];
}

// Count products that expire in the next 30 days.
$expiringProducts = 0;
$expiryResult = query("SELECT COUNT(*) as total FROM products WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
if ($expiryResult) {
    $expiringProducts = $expiryResult->fetch_assoc()['total'];
}

$expiringProductList = [];
$expiringListResult = query("SELECT name, expiry_date, quantity, unit FROM products WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC LIMIT 6");
if ($expiringListResult) {
    while ($row = $expiringListResult->fetch_assoc()) {
        $expiringProductList[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'todaySales' => $todaySales,
    'totalProducts' => $totalProducts,
    'totalCustomers' => $totalCustomers,
    'lowStockItems' => $lowStockItems,
    'creditOutstanding' => $creditOutstanding,
    'expiringProducts' => $expiringProducts,
    'lowStockProducts' => $lowStockProducts,
    'expiringProductList' => $expiringProductList
]);
?>
