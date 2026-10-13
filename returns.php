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
                    <h1><i class="fas fa-undo"></i> Returns & Refunds</h1>
                    <button class="btn btn-primary" id="newReturnBtn">
                        <i class="fas fa-plus"></i> Process Return
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalReturns">0</h3>
                            <p>Total Returns</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pendingReturns">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2ecc71;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="approvedReturns">0</h3>
                            <p>Approved</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="refundValue">KSh 0</h3>
                            <p>Total Refunds</p>
                        </div>
                    </div>
                </div>
                
                <!-- Returns Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Returns List</h3>
                        <select id="statusFilter" class="form-control" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="card-body">
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
    <div id="returnModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <span class="close">&times;</span>
            <h2><i class="fas fa-undo"></i> Process Return</h2>
            
            <form id="returnForm">
                <div class="form-group">
                    <label>Search Sale by Invoice Number</label>
                    <input type="text" id="invoiceSearch" class="form-control" placeholder="Enter invoice number..." required>
                </div>
                
                <div id="saleDetails" style="display: none; margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h3>Sale Details</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div><strong>Invoice:</strong> <span id="saleInvoice"></span></div>
                        <div><strong>Date:</strong> <span id="saleDate"></span></div>
                        <div><strong>Customer:</strong> <span id="saleCustomer"></span></div>
                        <div><strong>Total:</strong> <span id="saleTotal"></span></div>
                    </div>
                </div>
                
                <div id="itemsSection" style="display: none;">
                    <h3>Select Items to Return</h3>
                    <table style="width: 100%; margin: 15px 0;">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Product</th>
                                <th>Sold Qty</th>
                                <th>Return Qty</th>
                                <th>Unit Price</th>
                                <th>Refund</th>
                            </tr>
                        </thead>
                        <tbody id="returnItems">
                        </tbody>
                    </table>
                </div>
                
                <div id="returnDetailsSection" style="display: none;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <div class="form-group">
                                <label>Reason for Return</label>
                                <textarea id="returnReason" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Refund Method</label>
                                <select id="refundMethod" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="credit">Store Credit</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="form-group">
                                <label>Total Refund Amount</label>
                                <input type="text" id="totalRefund" class="form-control" readonly>
                            </div>
                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" id="restoreStock" checked>
                                    Restore stock for returned items
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeReturnModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitReturnBtn" style="display: none;">
                        <i class="fas fa-check"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/returns.js?v=20261011-2"></script>
</body>
</html>
