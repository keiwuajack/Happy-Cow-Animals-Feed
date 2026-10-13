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
                    <h1><i class="fas fa-shopping-cart"></i> Sales History</h1>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchSale" placeholder="Search by invoice number or customer..." class="search-box">
                        <input type="date" id="dateFrom" class="filter-select">
                        <input type="date" id="dateTo" class="filter-select">
                        <select id="filterPaymentStatus" class="filter-select">
                            <option value="">All Payments</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                        <button class="btn btn-primary" id="filterSales">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="salesTable">
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
    <div id="saleDetailsModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Sale Details</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="saleDetailsBody">
                <!-- Details loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button class="btn" id="closeSaleDetails">Close</button>
                <button class="btn btn-primary" id="printSaleReceipt">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/sales.js?v=20261011-2"></script>
</body>
</html>
