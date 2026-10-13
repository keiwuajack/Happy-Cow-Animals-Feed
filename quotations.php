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
    <title>Quotations - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-file-invoice"></i> Quotations</h1>
                    <button class="btn btn-primary" id="newQuoteBtn">
                        <i class="fas fa-plus"></i> New Quotation
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="totalQuotes">0</h3>
                            <p>Total Quotes</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="pendingQuotes">0</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2ecc71;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="convertedQuotes">0</h3>
                            <p>Converted</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9b59b6;">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3 id="quoteValue">KSh 0</h3>
                            <p>Total Value</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quotations Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Quotations List</h3>
                        <div class="filter-group" style="display: flex; gap: 10px;">
                            <select id="statusFilter" class="form-control" style="width: 150px;">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="accepted">Accepted</option>
                                <option value="rejected">Rejected</option>
                                <option value="converted">Converted</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="quotationsTable">
                                <thead>
                                    <tr>
                                        <th>Quote #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Valid Until</th>
                                        <th>Total</th>
                                        <th>Status</th>
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
            </div>
        </div>
    </div>
    
    <!-- New Quotation Modal -->
    <div id="quoteModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <span class="close">&times;</span>
            <h2><i class="fas fa-file-invoice"></i> <span id="modalTitle">New Quotation</span></h2>
            
            <form id="quoteForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div class="form-group">
                        <label>Customer</label>
                        <select id="quoteCustomer" class="form-control" required>
                            <option value="">Select Customer</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Valid Until</label>
                        <input type="date" id="validUntil" class="form-control" required>
                    </div>
                </div>
                
                <h3>Items</h3>
                <div style="margin-bottom: 15px;">
                    <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
                </div>
                
                <table id="quoteItemsTable" style="width: 100%; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="quoteItems">
                        <tr><td colspan="5" style="text-align: center;">No items added</td></tr>
                    </tbody>
                </table>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea id="quoteNotes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                    <div>
                        <div class="form-group">
                            <label>Subtotal</label>
                            <input type="text" id="quoteSubtotal" class="form-control" readonly value="KSh 0.00">
                        </div>
                        <div class="form-group">
                            <label>Discount (KSh)</label>
                            <input type="number" id="quoteDiscount" class="form-control" value="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Total</label>
                            <input type="text" id="quoteTotal" class="form-control" readonly value="KSh 0.00">
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeQuoteModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Quotation
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/quotations.js?v=20261011-2"></script>
</body>
</html>
