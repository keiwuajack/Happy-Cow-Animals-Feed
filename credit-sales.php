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
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/credit-sales.css?v=20261018-2">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header credit-page-header">
                    <div>
                        <p class="credit-eyebrow">Accounts receivable</p>
                        <h1><i class="fas fa-credit-card"></i> Credit Sales</h1>
                        <p class="credit-page-description">Track customer balances and record repayments against outstanding credit.</p>
                    </div>
                    <button class="btn btn-primary credit-record-button" id="recordPaymentBtn">
                        <i class="fas fa-money-bill"></i> Record Payment
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid credit-stats-grid">
                    <div class="stat-card credit-stat-card">
                        <div class="stat-icon credit-stat-icon credit-stat-icon-customers">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalCreditCustomers">0</h3>
                            <p>Credit Customers</p>
                        </div>
                    </div>
                    <div class="stat-card credit-stat-card">
                        <div class="stat-icon credit-stat-icon credit-stat-icon-outstanding">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalOutstanding">KSh 0</h3>
                            <p>Total Outstanding</p>
                        </div>
                    </div>
                    <div class="stat-card credit-stat-card">
                        <div class="stat-icon credit-stat-icon credit-stat-icon-limit">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalCreditLimit">KSh 0</h3>
                            <p>Total Credit Limit</p>
                        </div>
                    </div>
                    <div class="stat-card credit-stat-card">
                        <div class="stat-icon credit-stat-icon credit-stat-icon-payments">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="paymentsToday">KSh 0</h3>
                            <p>Payments Today</p>
                        </div>
                    </div>
                </div>
                
                <!-- Credit Customers Table -->
                <div class="card credit-list-card">
                    <div class="card-header credit-list-header">
                        <div>
                            <p class="credit-eyebrow">Customer accounts</p>
                            <h3><i class="fas fa-users"></i> Credit Customers</h3>
                        </div>
                        <label class="credit-search-wrap" for="customerSearch">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span class="sr-only">Search credit customers</span>
                            <input type="search" id="customerSearch" class="form-control" placeholder="Search by name or phone..." autocomplete="off">
                        </label>
                    </div>
                    <div class="card-body credit-table-body">
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
                <div class="card credit-list-card credit-payments-card">
                    <div class="card-header credit-list-header">
                        <div>
                            <p class="credit-eyebrow">Latest activity</p>
                            <h3><i class="fas fa-history"></i> Recent Payments</h3>
                        </div>
                        <span class="credit-history-note">Showing the latest 20 records</span>
                    </div>
                    <div class="card-body credit-table-body">
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
    <div id="paymentModal" class="modal credit-payment-modal" aria-hidden="true">
        <div class="modal-content credit-payment-dialog" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle" aria-describedby="paymentModalDescription" tabindex="-1">
            <div class="credit-dialog-header">
                <div class="credit-title-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="credit-title-copy">
                    <p class="credit-dialog-eyebrow">Accounts receivable</p>
                    <h2 id="paymentModalTitle">Record credit payment</h2>
                    <p id="paymentModalDescription">Apply a customer payment to their outstanding balance.</p>
                </div>
                <button type="button" class="close credit-close" aria-label="Close payment form"><i class="fas fa-times"></i></button>
            </div>

            <form id="paymentForm" class="credit-payment-form">
                <section class="credit-form-section" aria-labelledby="paymentCustomerTitle">
                    <div class="credit-section-heading">
                        <span class="credit-step">01</span>
                        <div>
                            <h3 id="paymentCustomerTitle">Select customer</h3>
                            <p>Choose the account that is making this payment.</p>
                        </div>
                    </div>
                    <div class="form-group credit-form-group">
                        <label class="credit-field-label" for="paymentCustomer">Customer <span>Required</span></label>
                        <div class="credit-select-wrap">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <select id="paymentCustomer" class="form-control" required>
                                <option value="">Select a customer with an outstanding balance</option>
                            </select>
                        </div>
                    </div>
                    <div id="customerBalance" class="credit-balance-panel" hidden aria-live="polite">
                        <div class="credit-balance-heading"><i class="fas fa-chart-pie"></i><span>Account balance</span></div>
                        <div class="credit-balance-grid">
                            <div><span>Outstanding</span><strong id="balanceAmount"></strong></div>
                            <div><span>Credit limit</span><strong id="limitAmount"></strong></div>
                            <div class="credit-remaining-balance"><span>After payment</span><strong id="remainingBalance">—</strong></div>
                        </div>
                    </div>
                </section>

                <section class="credit-form-section" aria-labelledby="paymentDetailsTitle">
                    <div class="credit-section-heading">
                        <span class="credit-step">02</span>
                        <div>
                            <h3 id="paymentDetailsTitle">Payment details</h3>
                            <p>Enter the amount and how it was received.</p>
                        </div>
                    </div>
                    <div class="credit-payment-fields">
                        <div class="form-group credit-form-group">
                            <label class="credit-field-label" for="paymentAmount">Payment amount <span>Required</span></label>
                            <div class="credit-amount-wrap"><span>KSh</span><input type="number" id="paymentAmount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required></div>
                            <small class="credit-field-hint">The payment cannot exceed the outstanding balance.</small>
                        </div>
                        <div class="form-group credit-form-group">
                            <label class="credit-field-label" for="paymentMethodSelect">Payment method <span>Required</span></label>
                            <div class="credit-select-wrap">
                                <i class="fas fa-wallet" aria-hidden="true"></i>
                                <select id="paymentMethodSelect" class="form-control" required>
                                    <option value="cash">Cash</option>
                                    <option value="mpesa">M-Pesa</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group credit-form-group">
                            <label class="credit-field-label" for="paymentReference">Reference number <span class="credit-optional">Optional</span></label>
                            <div class="credit-input-wrap"><i class="fas fa-hashtag" aria-hidden="true"></i><input type="text" id="paymentReference" class="form-control" placeholder="M-Pesa code or bank reference"></div>
                        </div>
                        <div class="form-group credit-form-group credit-notes-group">
                            <label class="credit-field-label" for="paymentNotes">Notes <span class="credit-optional">Optional</span></label>
                            <textarea id="paymentNotes" class="form-control" rows="3" placeholder="Add any useful payment details"></textarea>
                        </div>
                    </div>
                </section>

                <div class="credit-form-actions">
                    <button type="button" class="btn credit-cancel-button" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary credit-submit-button" id="submitPaymentBtn">
                        <i class="fas fa-check"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/credit-sales.js?v=20261018-1"></script>
</body>
</html>
