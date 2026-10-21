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
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/purchase-orders.css?v=20261018-2">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="page-content po-page">
            <div class="page-header po-page-header">
                <div class="po-page-intro">
                    <span class="po-eyebrow">INVENTORY MANAGEMENT</span>
                    <h1><span class="po-page-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></span> Purchase Orders</h1>
                    <p>Create supplier orders and track incoming stock in one place.</p>
                </div>
                <button class="btn btn-primary po-new-button" id="newPoBtn"><i class="fas fa-plus" aria-hidden="true"></i> New Purchase Order</button>
            </div>
            <div class="content-box po-list-card">
                <div class="po-list-heading">
                    <div>
                        <h2>Order history</h2>
                        <p>Review purchase orders and receive outstanding stock.</p>
                    </div>
                </div>
                <div class="table-controls po-table-controls">
                    <label class="po-search-wrap" for="poSearch"><i class="fas fa-search" aria-hidden="true"></i><input id="poSearch" type="search" placeholder="Search by PO number or supplier"></label>
                    <label class="po-filter-wrap" for="poStatus"><span>Status</span><select id="poStatus" class="filter-select">
                        <option value="">All statuses</option><option value="draft">Draft</option><option value="sent">Sent</option>
                        <option value="partial">Partial</option><option value="received">Received</option><option value="cancelled">Cancelled</option>
                    </select></label>
                </div>
                <div class="table-responsive po-table-wrap"><table id="purchaseOrdersTable" class="po-orders-table"><thead><tr>
                    <th>PO Number</th><th>Supplier</th><th>Total</th><th>Status</th><th>Expected</th><th>Created</th><th>Actions</th>
                </tr></thead><tbody><tr><td colspan="7">Loading...</td></tr></tbody></table></div>
            </div>
        </div>
    </div>
</div>
<div class="modal po-modal" id="poModal">
    <section class="modal-content po-modal-content" role="dialog" aria-modal="true" aria-labelledby="poModalTitle">
        <header class="po-modal-header">
            <div class="po-modal-title">
                <span class="po-modal-icon"><i class="fas fa-file-circle-plus" aria-hidden="true"></i></span>
                <div>
                    <span class="po-eyebrow">SUPPLIER PROCUREMENT</span>
                    <h2 id="poModalTitle">New Purchase Order</h2>
                    <p>Choose a supplier and add the products you need.</p>
                </div>
            </div>
            <button type="button" class="po-close" id="closePo" aria-label="Close purchase order form"><i class="fas fa-times" aria-hidden="true"></i></button>
        </header>
        <form id="poForm" class="po-form">
            <div class="modal-body po-modal-body">
                <section class="po-form-section" aria-labelledby="poDetailsTitle">
                    <div class="po-section-heading">
                        <span class="po-step">01</span>
                        <div><h3 id="poDetailsTitle">Order details</h3><p>Set the supplier and planned delivery date.</p></div>
                    </div>
                    <div class="po-details-grid">
                        <div class="form-group po-field">
                            <label for="poSupplier">Supplier <span class="po-required">Required</span></label>
                            <select id="poSupplier" required></select>
                            <small>Choose the supplier you are ordering from.</small>
                        </div>
                        <div class="form-group po-field">
                            <label for="poExpected">Expected delivery <span class="po-optional">Optional</span></label>
                            <input id="poExpected" type="date">
                            <small>Leave blank if the date is not confirmed.</small>
                        </div>
                        <div class="form-group po-field po-notes-field">
                            <label for="poNotes">Order notes <span class="po-optional">Optional</span></label>
                            <textarea id="poNotes" rows="3" placeholder="Add delivery instructions or other details"></textarea>
                        </div>
                    </div>
                </section>

                <section class="po-form-section po-products-section" aria-labelledby="poProductsTitle">
                    <div class="po-items-heading">
                        <div class="po-section-heading">
                            <span class="po-step">02</span>
                            <div><h3 id="poProductsTitle">Products to order</h3><p>Add each product and its quantity and unit cost.</p></div>
                        </div>
                        <button type="button" class="po-add-button" id="addPoItem"><i class="fas fa-plus" aria-hidden="true"></i> Add product</button>
                    </div>
                    <div class="table-responsive po-items-wrap">
                        <table class="po-items-table">
                            <thead><tr><th>Product</th><th>Quantity</th><th>Unit cost (KSh)</th><th><span class="po-sr-only">Remove</span></th></tr></thead>
                            <tbody id="poItems"></tbody>
                        </table>
                    </div>
                    <div class="po-total-card">
                        <span class="po-total-icon"><i class="fas fa-calculator" aria-hidden="true"></i></span>
                        <div class="po-total-label"><span>Estimated order total</span><small>Based on the quantities and unit costs above</small></div>
                        <strong>KSh <span id="poTotal">0.00</span></strong>
                    </div>
                </section>
            </div>
            <footer class="modal-footer po-modal-footer">
                <span class="po-save-hint"><i class="fas fa-circle-info" aria-hidden="true"></i> This order will be saved as a draft.</span>
                <div class="po-footer-actions">
                    <button type="button" class="btn po-cancel-button" id="cancelPo">Cancel</button>
                    <button type="submit" class="btn btn-primary po-save-button"><i class="fas fa-floppy-disk" aria-hidden="true"></i> Save draft</button>
                </div>
            </footer>
        </form>
    </section>
</div>
<script src="assets/js/main.js?v=20261021-1"></script><script src="assets/js/purchase-orders.js?v=20261018-1"></script>
</body></html>
