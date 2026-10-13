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
    <title>Customers - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-users"></i> Customers Management</h1>
                    <button class="btn btn-primary" id="addCustomerBtn">
                        <i class="fas fa-plus"></i> Add Customer
                    </button>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchCustomer" placeholder="Search customers..." class="search-box">
                        <select id="filterCustomerType" class="filter-select">
                            <option value="">All Types</option>
                            <option value="farmer">Farmer</option>
                            <option value="dealer">Dealer</option>
                            <option value="individual">Individual</option>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="customersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Farm Size</th>
                                    <th>Credit Limit</th>
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
    
    <!-- Customer Modal -->
    <div id="customerModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalTitle">Add Customer</h3>
                <span class="close">&times;</span>
            </div>
            <form id="customerForm">
                <div class="modal-body">
                    <input type="hidden" id="customerId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" id="customerName" required>
                        </div>
                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="tel" id="customerPhone" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="customerEmail">
                        </div>
                        <div class="form-group">
                            <label>Customer Type</label>
                            <select id="customerType">
                                <option value="individual">Individual</option>
                                <option value="farmer">Farmer</option>
                                <option value="dealer">Dealer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea id="customerAddress" rows="2"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Farm Size (Acres)</label>
                            <input type="text" id="farmSize" placeholder="e.g., 5 acres">
                        </div>
                        <div class="form-group">
                            <label>Credit Limit</label>
                            <input type="number" id="creditLimit" step="0.01" min="0" value="0">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="customerStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelCustomer">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Customer</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/customers.js?v=20261011-2"></script>
</body>
</html>
