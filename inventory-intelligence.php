<?php
session_start();
require_once 'config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Intelligence - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div><p class="eyebrow">Decision support</p><h1><i class="fas fa-brain"></i> Inventory Intelligence</h1><p>Know what to reorder, what may expire, and where your margin is strongest.</p></div>
                <a class="btn btn-primary" href="purchase-orders.php"><i class="fas fa-file-invoice-dollar"></i> Create Purchase Order</a>
            </div>
            <div class="stats-grid" id="inventorySummary"><div class="stat-card"><div class="stat-details"><h3>Loading intelligence...</h3></div></div></div>
            <div class="dashboard-chart-grid">
                <section class="card"><div class="card-header"><h3><i class="fas fa-triangle-exclamation"></i> Reorder Recommendations</h3></div><div class="card-body"><div class="table-responsive"><table><thead><tr><th>Product</th><th>On hand</th><th>Reorder at</th><th>Suggested order</th><th>Stock value</th></tr></thead><tbody id="lowStockRows"><tr><td colspan="5">Loading...</td></tr></tbody></table></div></div></section>
                <section class="card"><div class="card-header"><h3><i class="fas fa-calendar-xmark"></i> Expiry Watch</h3></div><div class="card-body"><div class="table-responsive"><table><thead><tr><th>Product</th><th>Quantity</th><th>Expiry</th><th>Days left</th><th>Value at risk</th></tr></thead><tbody id="expiryRows"><tr><td colspan="5">Loading...</td></tr></tbody></table></div></div></section>
            </div>
            <section class="card"><div class="card-header"><h3><i class="fas fa-chart-line"></i> Highest Margin Opportunities</h3><span>Prioritize products with stock available and strong margin</span></div><div class="card-body"><div class="table-responsive"><table><thead><tr><th>Product</th><th>Stock</th><th>Cost</th><th>Sell price</th><th>Margin</th><th>Margin %</th></tr></thead><tbody id="marginRows"><tr><td colspan="6">Loading...</td></tr></tbody></table></div></div></section>
        </div>
    </div>
</div>
<script src="assets/js/main.js?v=20261012-1"></script><script src="assets/js/inventory-intelligence.js?v=20261011-2"></script>
</body></html>
