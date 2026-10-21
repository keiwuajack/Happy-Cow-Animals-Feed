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
    <title>Returns & Refunds - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/returns.css?v=20261018-2">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header returns-page-header">
                    <div>
                        <p class="returns-eyebrow">Sales management</p>
                        <h1><i class="fas fa-undo"></i> Returns & Refunds</h1>
                        <p class="returns-page-description">Review returned items, track refunds, and keep stock records up to date.</p>
                    </div>
                    <button class="btn btn-primary returns-new-button" id="newReturnBtn">
                        <i class="fas fa-plus"></i> Process Return
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid returns-stats-grid">
                    <div class="stat-card returns-stat-card">
                        <div class="stat-icon returns-stat-icon returns-stat-icon-total">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalReturns">0</h3>
                            <p>Total Returns</p>
                        </div>
                    </div>
                    <div class="stat-card returns-stat-card">
                        <div class="stat-icon returns-stat-icon returns-stat-icon-pending">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pendingReturns">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card returns-stat-card">
                        <div class="stat-icon returns-stat-icon returns-stat-icon-approved">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="approvedReturns">0</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card returns-stat-card">
                        <div class="stat-icon returns-stat-icon returns-stat-icon-refunds">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="refundValue">KSh 0</h3>
                            <p>Total Refunds</p>
                        </div>
                    </div>
                </div>
                
                <!-- Returns Table -->
                <div class="card returns-list-card">
                    <div class="card-header returns-list-header">
                        <div>
                            <p class="returns-eyebrow">History</p>
                            <h3><i class="fas fa-list"></i> Returns List</h3>
                        </div>
                        <label class="returns-filter-label" for="statusFilter">Filter by status
                        <select id="statusFilter" class="form-control returns-status-filter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        </label>
                    </div>
                    <div class="card-body returns-list-body">
                        <div class="table-responsive">
                            <table id="returnsTable">
                                <thead>
                                    <tr>
                                        <th>Return #</th>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Refund Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="8" style="text-align: center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Return Modal -->
    <div id="returnModal" class="modal returns-modal" aria-hidden="true">
        <div class="modal-content returns-dialog" role="dialog" aria-modal="true" aria-labelledby="returnModalTitle" aria-describedby="returnModalDescription" tabindex="-1">
            <div class="returns-dialog-header">
                <div class="returns-title-icon"><i class="fas fa-undo"></i></div>
                <div class="returns-title-copy">
                    <p class="returns-dialog-eyebrow">Sales adjustment</p>
                    <h2 id="returnModalTitle">Process a return</h2>
                    <p id="returnModalDescription">Find the original invoice, choose the items, and record the refund.</p>
                </div>
                <button type="button" class="close returns-close" aria-label="Close return form"><i class="fas fa-times"></i></button>
            </div>

            <form id="returnForm" class="returns-form">
                <section class="returns-section returns-search-section" aria-labelledby="returnSearchTitle">
                    <div class="returns-section-heading">
                        <span class="returns-step">01</span>
                        <div>
                            <h3 id="returnSearchTitle">Find the sale</h3>
                            <p>Search using the invoice number printed on the receipt.</p>
                        </div>
                    </div>
                    <label class="returns-field-label" for="invoiceSearch">Invoice number <span>Required</span></label>
                    <div class="returns-search-wrap">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input type="search" id="invoiceSearch" class="form-control" placeholder="Start typing an invoice number" autocomplete="off" required>
                        <span class="returns-search-shortcut"><i class="fas fa-receipt"></i> Invoice</span>
                    </div>
                    <p id="invoiceSearchStatus" class="returns-search-status" aria-live="polite">Enter at least 3 characters to search.</p>
                </section>

                <section id="saleDetails" class="returns-sale-card" hidden aria-label="Sale details">
                    <div class="returns-sale-heading">
                        <div class="returns-sale-icon"><i class="fas fa-file-invoice"></i></div>
                        <div><p>Original sale</p><h3 id="saleInvoice"></h3></div>
                        <span class="returns-sale-total"><small>Sale total</small><strong id="saleTotal"></strong></span>
                    </div>
                    <div class="returns-sale-meta">
                        <div><span>Customer</span><strong id="saleCustomer"></strong></div>
                        <div><span>Sale date</span><strong id="saleDate"></strong></div>
                    </div>
                </section>

                <section id="itemsSection" class="returns-section returns-items-section" hidden aria-labelledby="returnItemsTitle">
                    <div class="returns-section-heading">
                        <span class="returns-step">02</span>
                        <div>
                            <h3 id="returnItemsTitle">Choose items to return</h3>
                            <p>Select each item and set the quantity being returned.</p>
                        </div>
                        <span id="returnItemCount" class="returns-item-count"></span>
                    </div>
                    <div class="returns-items-card">
                        <div class="returns-table-wrap">
                            <table class="returns-items-table">
                                <thead>
                                    <tr>
                                        <th class="returns-select-heading">Select</th>
                                        <th>Product</th>
                                        <th>Sold</th>
                                        <th>Return quantity</th>
                                        <th>Unit price</th>
                                        <th class="returns-refund-heading">Refund</th>
                                    </tr>
                                </thead>
                                <tbody id="returnItems"></tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <section id="returnDetailsSection" class="returns-section returns-details-section" hidden aria-labelledby="returnDetailsTitle">
                    <div class="returns-section-heading">
                        <span class="returns-step">03</span>
                        <div>
                            <h3 id="returnDetailsTitle">Refund details</h3>
                            <p>Add a reason and choose how the customer will receive the refund.</p>
                        </div>
                    </div>
                    <div class="returns-refund-grid">
                        <div class="returns-refund-fields">
                            <div class="form-group returns-form-group">
                                <label class="returns-field-label" for="returnReason">Reason for return <span>Required</span></label>
                                <textarea id="returnReason" class="form-control" rows="3" placeholder="Describe why the item is being returned" required></textarea>
                            </div>
                            <div class="form-group returns-form-group">
                                <label class="returns-field-label" for="refundMethod">Refund method</label>
                                <select id="refundMethod" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="credit">Store Credit</option>
                                </select>
                            </div>
                            <label class="returns-stock-option" for="restoreStock">
                                <input type="checkbox" id="restoreStock" checked>
                                <span><strong>Restore returned items to stock</strong><small>Use this when the items can be resold.</small></span>
                            </label>
                        </div>
                        <aside class="returns-refund-summary" aria-live="polite">
                            <div class="returns-refund-summary-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <span>Refund total</span>
                            <strong id="totalRefund">KSh 0.00</strong>
                            <small>Calculated from the selected items and quantities.</small>
                        </aside>
                    </div>
                </section>

                <div class="returns-form-actions">
                    <button type="button" class="btn returns-cancel-button" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary returns-submit-button" id="submitReturnBtn" hidden disabled>
                        <i class="fas fa-check"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script>
        const canManageReturns = <?= json_encode(in_array($_SESSION['role'] ?? '', ['admin', 'manager'], true)) ?>;
    </script>
    <script src="assets/js/returns.js?v=20261018-1"></script>
</body>
</html>
