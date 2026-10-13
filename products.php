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
    <title>Products - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-boxes"></i> Products Management</h1>
                    <button class="btn btn-primary" id="addProductBtn">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchProduct" placeholder="Search products..." class="search-box">
                        <select id="filterCategory" class="filter-select">
                            <option value="">All Categories</option>
                        </select>
                        <select id="filterStatus" class="filter-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="productsTable">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Stock</th>
                                    <th>Unit</th>
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
    
    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3 id="modalTitle">Add Product</h3>
                <span class="close">&times;</span>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" id="productId">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" id="productName" required>
                        </div>
                        <div class="form-group">
                            <label>SKU</label>
                            <input type="text" id="productSku">
                        </div>
                        <div class="form-group">
                            <label>Barcode <span style="font-weight:400;color:#89939a">(optional)</span></label>
                            <input type="text" id="productBarcode" maxlength="50" autocomplete="off">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category *</label>
                            <select id="productCategory" required>
                                <option value="">Select Category</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <select id="productUnit">
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="ltr">Liters</option>
                                <option value="bag">Bags</option>
                                <option value="box">Boxes</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost Price *</label>
                            <input type="number" id="costPrice" step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Selling Price *</label>
                            <input type="number" id="sellingPrice" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Initial Stock</label>
                            <input type="number" id="productStock" min="0" value="0">
                        </div>
                        <div class="form-group">
                            <label>Reorder Level</label>
                            <input type="number" id="reorderLevel" min="0" value="10">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="productDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expiry Date (Optional)</label>
                            <input type="date" id="productExpiry">
                        </div>
                        <div class="form-group">
                            <label>Product Photo <span style="font-weight:400;color:#89939a">(optional)</span></label>
                            <input type="file" id="productImage" accept="image/jpeg,image/png,image/webp">
                            <img id="productImagePreview" alt="" style="display:none;max-width:90px;max-height:90px;margin-top:6px;border-radius:8px;border:1px solid #dde3e6">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Status</label>
                            <select id="productStatus">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <!-- Empty for grid alignment -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelProduct">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/products.js?v=20261012-2"></script>
</body>
</html>

