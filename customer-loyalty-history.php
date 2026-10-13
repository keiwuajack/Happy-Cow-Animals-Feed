<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
$customerResult = query("SELECT name, loyalty_points FROM customers WHERE id = $id");
$customer = $customerResult ? $customerResult->fetch_assoc() : null;
if (!$customer) { http_response_code(404); exit('Customer not found'); }
$historyResult = query("SELECT * FROM customer_points_history WHERE customer_id = $id ORDER BY created_at DESC");
?><!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Loyalty History - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg"><link rel="stylesheet" href="assets/css/style.css?v=20261011-3"></head><body><div class="container"><div class="main-content"><main class="page-content"><div class="page-header"><h1>Loyalty History</h1><a class="btn" href="loyalty-program.php">Back to Loyalty Program</a></div><div class="content-box"><h2><?php echo htmlspecialchars($customer['name']); ?></h2><p>Current points: <?php echo intval($customer['loyalty_points']); ?></p><div class="table-responsive"><table><thead><tr><th>Date</th><th>Type</th><th>Points</th><th>Balance</th><th>Description</th></tr></thead><tbody><?php while ($entry = $historyResult->fetch_assoc()): ?><tr><td><?php echo htmlspecialchars($entry['created_at']); ?></td><td><?php echo htmlspecialchars(ucfirst($entry['type'])); ?></td><td><?php echo intval($entry['points']); ?></td><td><?php echo intval($entry['balance_after']); ?></td><td><?php echo htmlspecialchars($entry['description'] ?: '-'); ?></td></tr><?php endwhile; ?></tbody></table></div></div></main></div></div></body></html>
