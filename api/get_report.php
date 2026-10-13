<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$reportType = $_GET['type'] ?? 'sales';
$allowedReportTypes = ['sales', 'products', 'customers', 'profit'];
$dateFrom = $_GET['from'] ?? '';
$dateTo = $_GET['to'] ?? '';

if (!in_array($reportType, $allowedReportTypes, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid report type']);
    exit();
}

foreach (['from' => $dateFrom, 'to' => $dateTo] as $dateName => $dateValue) {
    $date = DateTime::createFromFormat('!Y-m-d', $dateValue);
    if (!$date || $date->format('Y-m-d') !== $dateValue) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Invalid {$dateName} date"]);
        exit();
    }
}

$response = ['success' => true];

// Summary
$sql = "SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total), 0) as total_sales,
        COALESCE(SUM(total) / NULLIF(COUNT(*), 0), 0) as average_sale
        FROM sales 
        WHERE DATE(created_at) BETWEEN '$dateFrom' AND '$dateTo'";
$result = query($sql);
$summary = $result->fetch_assoc();

// Calculate profit
$sql = "SELECT COALESCE(SUM(si.quantity * (si.unit_price - p.cost_price)), 0) as total_profit
        FROM sale_items si
        JOIN sales s ON si.sale_id = s.id
        LEFT JOIN products p ON si.product_id = p.id
        WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
$result = query($sql);
$profitData = $result->fetch_assoc();
$summary['total_profit'] = $profitData['total_profit'];

$response['summary'] = $summary;

// Report data based on type
if ($reportType === 'sales') {
    $sql = "SELECT s.*, c.name as customer_name,
            (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'
            ORDER BY s.created_at DESC";
    $result = query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response['data'] = $data;
    
} elseif ($reportType === 'products') {
    $sql = "SELECT 
            si.product_name,
            SUM(si.quantity) as total_quantity,
            SUM(si.subtotal) as revenue,
            SUM(si.quantity * p.cost_price) as cost,
            SUM(si.quantity * (si.unit_price - p.cost_price)) as profit
            FROM sale_items si
            JOIN sales s ON si.sale_id = s.id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'
            GROUP BY si.product_id, si.product_name
            ORDER BY revenue DESC
            LIMIT 50";
    $result = query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response['data'] = $data;
    
} elseif ($reportType === 'customers') {
    $sql = "SELECT 
            COALESCE(c.name, 'Walk-in') as customer_name,
            COUNT(s.id) as purchase_count,
            SUM(s.total) as total_spent,
            AVG(s.total) as average_order
            FROM sales s
            LEFT JOIN customers c ON s.customer_id = c.id
            WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'
            GROUP BY s.customer_id
            ORDER BY total_spent DESC
            LIMIT 50";
    $result = query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response['data'] = $data;
    
} elseif ($reportType === 'profit') {
    $sql = "SELECT 
            DATE(s.created_at) as period,
            SUM(s.total) as sales,
            SUM(si.quantity * p.cost_price) as cost,
            SUM(si.quantity * (si.unit_price - p.cost_price)) as profit
            FROM sales s
            JOIN sale_items si ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE DATE(s.created_at) BETWEEN '$dateFrom' AND '$dateTo'
            GROUP BY DATE(s.created_at)
            ORDER BY period ASC";
    $result = query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $row['period'] = date('M d, Y', strtotime($row['period']));
        $data[] = $row;
    }
    $response['data'] = $data;
}

echo json_encode($response);
?>
