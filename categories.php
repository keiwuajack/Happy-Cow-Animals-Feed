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
    <title>Categories - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-tags"></i> Categories Management</h1>
                    <button class="btn btn-primary" id="addCategoryBtn">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>
                
                <div class="content-box">
                    <div class="categories-grid" id="categoriesGrid">
                        <!-- Categories loaded via JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <div class="modal-header">
                <div class="modal-heading">
                    <span class="modal-eyebrow">Product setup</span>
                    <h3 id="modalTitle">Add Category</h3>
                    <p>Group products so they are easier to find.</p>
                </div>
                <button type="button" class="close" aria-label="Close dialog">&times;</button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" id="categoryId">
                    
                    <div class="form-group">
                        <label>Category Name *</label>
                        <input type="text" id="categoryName" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="categoryDescription" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="categoryStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelCategory">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/categories.js?v=20261011-2"></script>
</body>
</html>
