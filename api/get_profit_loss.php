<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    $gp = query("SELECT COALESCE(SUM(s.subtotal - s.discount), 0) AS net_sales,
        COALESCE(SUM((SELECT SUM(si.quantity * COALESCE(si.cost_price, p.cost_price, 0)) FROM sale_items si LEFT JOIN products p ON p.id = si.product_id WHERE si.sale_id = s.id)), 0) AS cost
        FROM sales s WHERE DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
$cost_of_goods = (float) $gp['cost'];
$gross_profit = (float) $gp['net_sales'] - $cost_of_goods;

echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$start_date = sanitize($_GET['start_date']);
$end_date = sanitize($_GET['end_date']);

// Get revenue from sales
$revenue_sql = "SELECT COALESCE(SUM(total), 0) as total FROM sales 
                WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$result = query($revenue_sql);
$revenue = $result->fetch_assoc()['total'];

// Get expenses
$expenses_sql = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses 
                 WHERE DATE(expense_date) BETWEEN '$start_date' AND '$end_date'";
$result = query($expenses_sql);
$expenses = $result->fetch_assoc()['total'];

// Get sales count
$sales_count_sql = "SELECT COUNT(*) as count FROM sales 
                    WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
$result = query($sales_count_sql);
$total_sales = $result->fetch_assoc()['count'];

// Average sale
$average_sale = $total_sales > 0 ? $revenue / $total_sales : 0;

// Get expense count
$expense_count_sql = "SELECT COUNT(*) as count FROM expenses 
                      WHERE DATE(expense_date) BETWEEN '$start_date' AND '$end_date'";
$result = query($expense_count_sql);
$expense_count = $result->fetch_assoc()['count'];

// Get largest expense
$largest_expense_sql = "SELECT COALESCE(MAX(amount), 0) as max FROM expenses 
                        WHERE DATE(expense_date) BETWEEN '$start_date' AND '$end_date'";
$result = query($largest_expense_sql);
$largest_expense = $result->fetch_assoc()['max'];

$gp = query("SELECT COALESCE(SUM(s.subtotal - s.discount), 0) AS net_sales,
        COALESCE(SUM((SELECT SUM(si.quantity * COALESCE(si.cost_price, p.cost_price, 0)) FROM sale_items si LEFT JOIN products p ON p.id = si.product_id WHERE si.sale_id = s.id)), 0) AS cost
        FROM sales s WHERE DATE(s.created_at) BETWEEN '$start_date' AND '$end_date'")->fetch_assoc();
$cost_of_goods = (float) $gp['cost'];
$gross_profit = (float) $gp['net_sales'] - $cost_of_goods;

echo json_encode([
    'success' => true,
    'revenue' => $revenue,
    'cost_of_goods' => $cost_of_goods,
    'gross_profit' => $gross_profit,
    'expenses' => $expenses,
    'total_sales' => $total_sales,
    'average_sale' => $average_sale,
    'expense_count' => $expense_count,
    'largest_expense' => $largest_expense
]);
?>
