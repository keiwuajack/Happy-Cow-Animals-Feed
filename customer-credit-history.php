<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
$customerResult = query("SELECT * FROM customers WHERE id = $id");
$customer = $customerResult ? $customerResult->fetch_assoc() : null;
if (!$customer) { http_response_code(404); exit('Customer not found'); }
$paymentsResult = query("SELECT cp.*, u.full_name AS recorded_by_name FROM credit_payments cp LEFT JOIN users u ON u.id = cp.recorded_by WHERE cp.customer_id = $id ORDER BY cp.payment_date DESC");
?><!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Credit History - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg"><link rel="stylesheet" href="assets/css/style.css?v=20261011-3"></head><body><div class="container"><div class="main-content"><main class="page-content"><div class="page-header"><h1>Credit History</h1><a class="btn" href="credit-sales.php">Back to Credit Sales</a></div><div class="content-box"><h2><?php echo htmlspecialchars($customer['name']); ?></h2><p>Phone: <?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></p><p>Credit limit: KSh <?php echo number_format($customer['credit_limit'], 2); ?> | Balance: KSh <?php echo number_format($customer['credit_balance'], 2); ?></p><div class="table-responsive"><table><thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Reference</th><th>Recorded By</th></tr></thead><tbody><?php while ($payment = $paymentsResult->fetch_assoc()): ?><tr><td><?php echo htmlspecialchars($payment['payment_date']); ?></td><td>KSh <?php echo number_format($payment['amount'], 2); ?></td><td><?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></td><td><?php echo htmlspecialchars($payment['reference_number'] ?: '-'); ?></td><td><?php echo htmlspecialchars($payment['recorded_by_name'] ?: '-'); ?></td></tr><?php endwhile; ?></tbody></table></div></div></main></div></div></body></html>
