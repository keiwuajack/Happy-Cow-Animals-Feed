<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Happy Cow Animal Feeds</title>
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
                <h1><i class="fas fa-file-invoice-dollar"></i> Purchase Orders</h1>
                <button class="btn btn-primary" id="newPoBtn"><i class="fas fa-plus"></i> New Purchase Order</button>
            </div>
            <div class="content-box">
                <div class="table-controls">
                    <input id="poSearch" class="search-box" type="search" placeholder="Search PO number or supplier...">
                    <select id="poStatus" class="filter-select">
                        <option value="">All statuses</option><option value="draft">Draft</option><option value="sent">Sent</option>
                        <option value="partial">Partial</option><option value="received">Received</option><option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="table-responsive"><table id="purchaseOrdersTable"><thead><tr>
                    <th>PO Number</th><th>Supplier</th><th>Total</th><th>Status</th><th>Expected</th><th>Created</th><th>Actions</th>
                </tr></thead><tbody><tr><td colspan="7">Loading...</td></tr></tbody></table></div>
            </div>
        </div>
    </div>
</div>
<div class="modal" id="poModal"><div class="modal-content modal-large">
    <div class="modal-header"><h2>New Purchase Order</h2><span class="close" id="closePo">&times;</span></div>
    <form id="poForm"><div class="modal-body">
        <div class="form-row"><div class="form-group"><label>Supplier *</label><select id="poSupplier" required></select></div>
        <div class="form-group"><label>Expected delivery</label><input id="poExpected" type="date"></div></div>
        <div class="form-group"><label>Notes</label><textarea id="poNotes" rows="2"></textarea></div>
        <div class="table-responsive"><table><thead><tr><th>Product</th><th>Quantity</th><th>Unit cost</th><th></th></tr></thead>
        <tbody id="poItems"></tbody></table></div>
        <button type="button" class="btn" id="addPoItem"><i class="fas fa-plus"></i> Add product</button>
        <p style="text-align:right;font-size:1.1em"><strong>Total: KSh <span id="poTotal">0.00</span></strong></p>
    </div><div class="modal-footer"><button type="button" class="btn" id="cancelPo">Cancel</button><button class="btn btn-primary">Save draft</button></div></form>
</div></div>
<script src="assets/js/main.js?v=20261012-1"></script><script src="assets/js/purchase-orders.js?v=20261011-2"></script>
</body></html>
