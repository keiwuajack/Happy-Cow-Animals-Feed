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
    <title>Stock Adjustments - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-warehouse"></i> Stock Adjustments</h1>
                    <button class="btn btn-primary" id="addAdjustmentBtn">
                        <i class="fas fa-plus"></i> New Adjustment
                    </button>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchAdjustment" placeholder="Search adjustments..." class="search-box">
                        <select id="filterType" class="filter-select">
                            <option value="">All Types</option>
                            <option value="add">Stock Added</option>
                            <option value="remove">Stock Removed</option>
                            <option value="damage">Damaged</option>
                            <option value="expired">Expired</option>
                        </select>
                        <input type="date" id="filterDate" class="filter-select">
                    </div>
                    
                    <div class="table-responsive">
                        <table id="adjustmentsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Previous Stock</th>
                                    <th>New Stock</th>
                                    <th>Reason</th>
                                    <th>Adjusted By</th>
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
    
    <!-- Adjustment Modal -->
    <div class="modal" id="adjustmentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Stock Adjustment</h2>
                <span class="close">&times;</span>
            </div>
            <form id="adjustmentForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Product *</label>
                        <select id="productId" required>
                            <option value="">Select Product</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Current Stock</label>
                            <input type="text" id="currentStock" readonly>
                        </div>
                        <div class="form-group">
                            <label>Adjustment Type *</label>
                            <select id="adjustmentType" required>
                                <option value="add">Add Stock</option>
                                <option value="remove">Remove Stock</option>
                                <option value="damage">Damaged</option>
                                <option value="expired">Expired</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" id="quantity" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>New Stock</label>
                            <input type="text" id="newStock" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea id="reason" rows="3" required placeholder="Enter reason for stock adjustment..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelAdjustment">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Adjustment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/stock-adjustments.js?v=20261011-2"></script>
</body>
</html>
