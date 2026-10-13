<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$expiryColumn = query("SHOW COLUMNS FROM products LIKE 'expiry_date'");
$hasExpiry = $expiryColumn && $expiryColumn->num_rows > 0;
$expiryExpression = $hasExpiry ? 'expiry_date' : 'NULL';
$expiryFilter = $hasExpiry ? " AND expiry_date IS NOT NULL" : '';
$expiringCondition = $hasExpiry ? 'expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)' : '0';
$expiredCondition = $hasExpiry ? 'expiry_date < CURDATE()' : 'FALSE';

$summary = [
    'stock_value' => 0,
    'potential_revenue' => 0,
    'potential_margin' => 0,
    'low_stock_count' => 0,
    'expiring_count' => 0,
    'expired_stock_value' => 0
];

$result = query("SELECT
    COALESCE(SUM(quantity * cost_price), 0) AS stock_value,
    COALESCE(SUM(quantity * selling_price), 0) AS potential_revenue,
    COALESCE(SUM(quantity * (selling_price - cost_price)), 0) AS potential_margin,
    SUM(quantity <= reorder_level) AS low_stock_count,
    SUM($expiringCondition) AS expiring_count,
    COALESCE(SUM(CASE WHEN $expiredCondition THEN quantity * cost_price ELSE 0 END), 0) AS expired_stock_value
    FROM products WHERE status = 'active'");
if ($result) {
    $summary = array_merge($summary, $result->fetch_assoc());
}

$lowStock = [];
$result = query("SELECT id, name, sku, quantity, reorder_level, unit, cost_price, selling_price
    FROM products WHERE status = 'active' AND quantity <= reorder_level
    ORDER BY (reorder_level - quantity) DESC, quantity ASC, name ASC LIMIT 50");
while ($result && $row = $result->fetch_assoc()) $lowStock[] = $row;

$expiring = [];
if ($hasExpiry) {
    $result = query("SELECT id, name, sku, quantity, unit, expiry_date, cost_price,
        DATEDIFF(expiry_date, CURDATE()) AS days_left
        FROM products WHERE status = 'active' AND expiry_date IS NOT NULL
        AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
        ORDER BY expiry_date ASC, quantity DESC LIMIT 50");
    while ($result && $row = $result->fetch_assoc()) $expiring[] = $row;
}

$margin = [];
$result = query("SELECT id, name, sku, quantity, unit, cost_price, selling_price,
    (selling_price - cost_price) AS margin_amount,
    CASE WHEN selling_price > 0 THEN ((selling_price - cost_price) / selling_price) * 100 ELSE 0 END AS margin_percent
    FROM products WHERE status = 'active' AND quantity > 0
    ORDER BY margin_percent DESC, margin_amount DESC LIMIT 20");
while ($result && $row = $result->fetch_assoc()) $margin[] = $row;

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'low_stock' => $lowStock,
    'expiring' => $expiring,
    'margin_opportunities' => $margin,
    'expiry_tracking' => $hasExpiry
]);
?>
