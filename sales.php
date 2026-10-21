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
    <title>Sales History - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/sales.css?v=20261018-2">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content sales-page">
                <div class="page-header sales-page-header">
                    <div>
                        <span class="sales-eyebrow">TRANSACTION RECORDS</span>
                        <h1><span class="sales-page-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span> Sales History</h1>
                        <p>Find completed sales, review payment details, and print customer receipts.</p>
                    </div>
                </div>
                
                <div class="content-box sales-history-card">
                    <div class="sales-history-heading">
                        <div><h2>Sales records</h2><p>Use the filters to find a sale by invoice, customer, date, or payment status.</p></div>
                    </div>
                    <div class="table-controls sales-filter-panel">
                        <label class="sales-search-field" for="searchSale">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" id="searchSale" placeholder="Search invoice or customer">
                        </label>
                        <label class="sales-date-field" for="dateFrom"><span>From</span><input type="date" id="dateFrom"></label>
                        <label class="sales-date-field" for="dateTo"><span>To</span><input type="date" id="dateTo"></label>
                        <label class="sales-status-field" for="filterPaymentStatus"><span>Payment status</span><select id="filterPaymentStatus">
                            <option value="">All Payments</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="unpaid">Unpaid</option>
                        </select></label>
                        <button class="btn btn-primary sales-filter-button" id="filterSales">
                            <i class="fas fa-filter" aria-hidden="true"></i> Apply filters
                        </button>
                    </div>
                    
                    <div class="table-responsive sales-table-wrap">
                        <table id="salesTable" class="sales-history-table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Subtotal</th>
                                    <th>Discount</th>
                                    <th>Tax</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sale Details Modal -->
    <div id="saleDetailsModal" class="modal sales-details-modal">
        <section class="modal-content sales-details-dialog" role="dialog" aria-modal="true" aria-labelledby="saleDetailsTitle">
            <header class="sales-details-header">
                <div>
                    <span class="sales-eyebrow">TRANSACTION SUMMARY</span>
                    <h2 id="saleDetailsTitle">Sale Details</h2>
                    <p>Review the items, totals, and payment for this sale.</p>
                </div>
                <button type="button" class="close sales-details-close" aria-label="Close sale details"><i class="fas fa-times" aria-hidden="true"></i></button>
            </header>
            <div class="modal-body sales-details-body" id="saleDetailsBody">
                <!-- Details loaded dynamically -->
            </div>
            <footer class="modal-footer sales-details-footer">
                <span class="sales-print-hint"><i class="fas fa-circle-info" aria-hidden="true"></i> The receipt opens in a print-friendly page.</span>
                <div class="sales-details-actions">
                    <button type="button" class="btn sales-close-button" id="closeSaleDetails">Close</button>
                    <button type="button" class="btn btn-primary sales-print-button" id="printSaleReceipt">
                    <i class="fas fa-print" aria-hidden="true"></i> Print Receipt
                </button>
                </div>
            </footer>
        </section>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/sales.js?v=20261018-1"></script>
</body>
</html>
