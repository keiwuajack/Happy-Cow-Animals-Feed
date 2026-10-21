<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$isCashier = strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
$canViewProducts = security_has_permission('products.lookup') || security_has_permission('products.manage');
$canViewCustomers = security_has_permission('customers.lookup') || security_has_permission('customers.manage');
$canViewInventory = security_has_permission('inventory.view') || security_has_permission('inventory.manage');
$canViewCredit = security_has_permission('credit.manage');
$stats = ['success' => true];

// Cashiers see their own active shift totals from shift_status.php, not store-wide sales.
if (!$isCashier && security_has_permission('sales.view')) {
    $salesResult = query("SELECT COALESCE(SUM(total), 0) AS total FROM sales WHERE DATE(created_at) = CURDATE()");
    $stats['todaySales'] = $salesResult ? $salesResult->fetch_assoc()['total'] : 0;
}

if (!$isCashier && $canViewProducts) {
    $productsResult = query("SELECT COUNT(*) AS total FROM products WHERE status = 'active'");
    $stats['totalProducts'] = $productsResult ? $productsResult->fetch_assoc()['total'] : 0;
}

if (!$isCashier && $canViewCustomers) {
    $customersResult = query("SELECT COUNT(*) AS total FROM customers WHERE status = 'active'");
    $stats['totalCustomers'] = $customersResult ? $customersResult->fetch_assoc()['total'] : 0;
}

if ($canViewInventory) {
    $lowStockResult = query("SELECT COUNT(*) AS total FROM products WHERE quantity <= reorder_level AND status = 'active'");
    $stats['lowStockItems'] = $lowStockResult ? $lowStockResult->fetch_assoc()['total'] : 0;
    $stats['lowStockProducts'] = [];
    $lowStockProducts = query("SELECT name, quantity, unit, reorder_level FROM products WHERE quantity <= reorder_level AND status = 'active' ORDER BY quantity ASC, name ASC LIMIT 6");
    if ($lowStockProducts) {
        while ($row = $lowStockProducts->fetch_assoc()) {
            $stats['lowStockProducts'][] = $row;
        }
    }

    $expiryResult = query("SELECT COUNT(*) AS total FROM products WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    $stats['expiringProducts'] = $expiryResult ? $expiryResult->fetch_assoc()['total'] : 0;
    $stats['expiringProductList'] = [];
    $expiringProducts = query("SELECT name, expiry_date, quantity, unit FROM products WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY expiry_date ASC LIMIT 6");
    if ($expiringProducts) {
        while ($row = $expiringProducts->fetch_assoc()) {
            $stats['expiringProductList'][] = $row;
        }
    }
}

if ($canViewCredit) {
    $creditResult = query("SELECT COALESCE(SUM(credit_balance), 0) AS total FROM customers WHERE status = 'active'");
    $stats['creditOutstanding'] = $creditResult ? $creditResult->fetch_assoc()['total'] : 0;
}

echo json_encode($stats);
?>
