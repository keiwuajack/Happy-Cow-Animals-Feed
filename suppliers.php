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
    <title>Suppliers - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <h1><i class="fas fa-truck"></i> Suppliers Management</h1>
                    <button class="btn btn-primary" id="addSupplierBtn">
                        <i class="fas fa-plus"></i> Add Supplier
                    </button>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchSupplier" placeholder="Search suppliers..." class="search-box">
                    </div>
                    
                    <div class="table-responsive">
                        <table id="suppliersTable">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Contact Person</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Address</th>
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
    
    <!-- Supplier Modal -->
    <div id="supplierModal" class="modal">
        <div class="modal-content modal-large" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-eyebrow">Supplier records</span>
                    <h3 id="modalTitle">Add Supplier</h3>
                    <p>Enter the supplier’s company and contact information.</p>
                </div>
                <button type="button" class="close" aria-label="Close dialog">&times;</button>
            </div>
            <form id="supplierForm">
                <div class="modal-body">
                    <input type="hidden" id="supplierId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Company Name *</label>
                            <input type="text" id="supplierName" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" id="contactPerson">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone *</label>
                            <input type="tel" id="supplierPhone" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" id="supplierEmail">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea id="supplierAddress" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="supplierStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelSupplier">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/suppliers.js?v=20261011-2"></script>
</body>
</html>
