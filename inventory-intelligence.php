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
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/inventory-intelligence.css?v=20261021-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <main class="page-content inventory-page">
            <header class="inventory-hero">
                <div>
                    <p class="inventory-eyebrow">INVENTORY OVERVIEW</p>
                    <h1><i class="fas fa-boxes-stacked" aria-hidden="true"></i> Stock at a glance</h1>
                    <p>Review items that need attention first, then check stock value and product margins.</p>
                </div>
                <a class="btn btn-primary" href="purchase-orders.php"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i> Create purchase order</a>
            </header>

            <section class="inventory-section" aria-labelledby="inventoryAttentionTitle">
                <div class="inventory-section-heading">
                    <div><span class="inventory-step">01 · TAKE ACTION</span><h2 id="inventoryAttentionTitle">Needs your attention</h2><p>Products to reorder, or stock that may expire soon.</p></div>
                    <a class="inventory-text-link" href="products.php">Manage products <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </div>
                <div class="inventory-summary" id="inventorySummary" aria-live="polite">
                    <div class="inventory-loading"><i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Loading stock overview...</div>
                </div>
            </section>

            <div class="inventory-action-grid">
                <details class="inventory-panel inventory-accordion" aria-labelledby="reorderTitle">
                    <summary class="inventory-panel-heading">
                        <div><span class="inventory-step">REORDER</span><h2 id="reorderTitle">Products below their reorder point</h2><p>These items are at or below the level you set in Products.</p></div>
                        <span class="inventory-accordion-side"><span class="inventory-list-count" id="reorderListCount">Loading</span><span class="inventory-panel-icon reorder-icon"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></span><i class="fas fa-chevron-down inventory-accordion-chevron" aria-hidden="true"></i></span>
                    </summary>
                    <div class="inventory-accordion-body">
                        <p class="inventory-table-note">Top-up estimate aims to bring stock to twice its reorder point. Most understocked items appear first; review quantities before ordering.</p>
                        <div class="inventory-table-wrap"><table>
                            <thead><tr><th>Product</th><th>Current stock</th><th>Reorder point</th><th>Top-up estimate</th><th>Current cost value</th></tr></thead>
                            <tbody id="lowStockRows"><tr><td colspan="5">Loading reorder list...</td></tr></tbody>
                        </table></div>
                    </div>
                </details>

                <details class="inventory-panel inventory-accordion" aria-labelledby="expiryTitle">
                    <summary class="inventory-panel-heading">
                        <div><span class="inventory-step">EXPIRY WATCH</span><h2 id="expiryTitle">Expiring and expired stock</h2><p>Dates within the next 90 days and any expired stock.</p></div>
                        <span class="inventory-accordion-side"><span class="inventory-list-count" id="expiryListCount">Loading</span><span class="inventory-panel-icon expiry-icon"><i class="fas fa-calendar-xmark" aria-hidden="true"></i></span><i class="fas fa-chevron-down inventory-accordion-chevron" aria-hidden="true"></i></span>
                    </summary>
                    <div class="inventory-accordion-body">
                        <p class="inventory-table-note">Nearest expiry dates appear first. The list shows up to 50 products.</p>
                        <div class="inventory-table-wrap"><table>
                            <thead><tr><th>Product</th><th>Quantity</th><th>Expiry date</th><th>Status</th><th>Cost value</th></tr></thead>
                            <tbody id="expiryRows"><tr><td colspan="5">Loading expiry list...</td></tr></tbody>
                        </table></div>
                    </div>
                </details>
            </div>

            <details class="inventory-panel inventory-accordion inventory-financial-panel" aria-labelledby="marginTitle">
                <summary class="inventory-panel-heading">
                    <div><span class="inventory-step">02 · REVIEW PERFORMANCE</span><h2 id="marginTitle">Product gross margins</h2><p>Compare cost and selling price for products that currently have stock.</p></div>
                    <span class="inventory-accordion-side"><span class="inventory-list-count" id="marginListCount">Loading</span><span class="inventory-panel-icon margin-icon"><i class="fas fa-chart-line" aria-hidden="true"></i></span><i class="fas fa-chevron-down inventory-accordion-chevron" aria-hidden="true"></i></span>
                </summary>
                <div class="inventory-accordion-body">
                    <p class="inventory-table-note">Gross profit per unit is selling price minus cost price. Gross margin is the share of selling price left before overheads and tax. Highest margins appear first; up to 20 products are shown.</p>
                    <div class="inventory-table-wrap"><table>
                        <thead><tr><th>Product</th><th>Stock</th><th>Cost per unit</th><th>Selling price</th><th>Gross profit per unit</th><th>Gross margin</th></tr></thead>
                        <tbody id="marginRows"><tr><td colspan="6">Loading margin list...</td></tr></tbody>
                    </table></div>
                </div>
            </details>
        </main>
    </div>
</div>
<script src="assets/js/main.js?v=20261021-1"></script><script src="assets/js/inventory-intelligence.js?v=20261021-1"></script>
</body></html>
