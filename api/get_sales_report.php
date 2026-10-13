<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$start_date = sanitize($_GET['start_date']);
$end_date = sanitize($_GET['end_date']);

// Get sales summary
$summary_sql = "SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total), 0) as total_revenue,
                COALESCE(AVG(total), 0) as average_sale,
                COALESCE(SUM((SELECT SUM(quantity) FROM sale_items WHERE sale_id = sales.id)), 0) as total_items
                FROM sales
                WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";

$result = query($summary_sql);
$summary = $result->fetch_assoc();

// Get individual sales
$sales_sql = "SELECT s.*, c.name as customer_name,
              (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.id
              WHERE DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'
              ORDER BY s.created_at DESC";

$sales_sql = str_replace("(SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count", "(SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,\n              (SELECT COALESCE(SUM(si.quantity * COALESCE(si.cost_price, p.cost_price, 0)), 0) FROM sale_items si LEFT JOIN products p ON p.id = si.product_id WHERE si.sale_id = s.id) as cost", $sales_sql);
$result = query($sales_sql);
$sales = [];

while ($row = $result->fetch_assoc()) {
    $row["profit"] = (float) $row["subtotal"] - (float) $row["discount"] - (float) $row["cost"];
    $sales[] = $row;
}

$daily = [];
$total_profit = 0;
$total_cost = 0;
foreach ($sales as $s) {
    $day = substr($s['created_at'], 0, 10);
    if (!isset($daily[$day])) {
        $daily[$day] = ['date' => $day, 'sales' => 0, 'revenue' => 0, 'cost' => 0, 'profit' => 0];
    }
    $daily[$day]['sales']++;
    $daily[$day]['revenue'] += (float) $s['total'];
    $daily[$day]['cost'] += (float) $s['cost'];
    $daily[$day]['profit'] += $s['profit'];
    $total_profit += $s['profit'];
    $total_cost += (float) $s['cost'];
}
$summary['total_profit'] = $total_profit;
$summary['total_cost'] = $total_cost;

echo json_encode([
    'success' => true,
    'summary' => $summary,
    'sales' => $sales,
    'daily' => array_values($daily)
]);
?>
