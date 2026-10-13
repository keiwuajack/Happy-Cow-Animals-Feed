<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credit Sales - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-credit-card"></i> Credit Sales Management</h1>
                    <button class="btn btn-primary" id="recordPaymentBtn">
                        <i class="fas fa-money-bill"></i> Record Payment
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalCreditCustomers">0</h3>
                            <p>Credit Customers</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalOutstanding">KSh 0</h3>
                            <p>Total Outstanding</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalCreditLimit">KSh 0</h3>
                            <p>Total Credit Limit</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2ecc71;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="paymentsToday">KSh 0</h3>
                            <p>Payments Today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Credit Customers Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Credit Customers</h3>
                        <input type="text" id="customerSearch" class="form-control" placeholder="Search customers..." style="width: 250px;">
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="creditCustomersTable">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Credit Limit</th>
                                        <th>Outstanding Balance</th>
                                        <th>Available Credit</th>
                                        <th>Last Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" style="text-align: center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Payment History -->
                <div class="card" style="margin-top: 20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Payments</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="paymentsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" style="text-align: center;">Loading...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Record Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-money-bill"></i> Record Credit Payment</h2>
            
            <form id="paymentForm">
                <div class="form-group">
                    <label>Customer *</label>
                    <select id="paymentCustomer" class="form-control" required>
                        <option value="">Select Customer</option>
                    </select>
                </div>
                
                <div id="customerBalance" style="display:none; padding: 15px; background: #f8f9fa; border-radius: 5px; margin: 15px 0;">
                    <h3>Current Balance</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div><strong>Outstanding:</strong> <span id="balanceAmount" style="color: #e74c3c; font-size: 18px;"></span></div>
                        <div><strong>Credit Limit:</strong> <span id="limitAmount"></span></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Payment Amount (KSh) *</label>
                    <input type="number" id="paymentAmount" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select id="paymentMethodSelect" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="bank">Bank Transfer</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Reference Number</label>
                    <input type="text" id="paymentReference" class="form-control" placeholder="Optional">
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="paymentNotes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/credit-sales.js?v=20261011-2"></script>
</body>
</html>
