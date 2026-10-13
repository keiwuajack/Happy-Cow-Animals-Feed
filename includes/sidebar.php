<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand"><span class="brand-mark" aria-hidden="true"><i class="fas fa-cow"></i></span><span><strong>Happy Cow</strong><small>Animal Feeds</small></span></div>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="pos.php" class="nav-item nav-item-pos">
            <i class="fas fa-cash-register"></i>
            <span class="nav-item-label">Point of Sale</span>
            <span class="nav-item-badge">SELL</span>
        </a>
        
        <a href="products.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        
        <a href="stock-adjustments.php" class="nav-item">
            <i class="fas fa-warehouse"></i>
            <span>Stock Adjustments</span>
        </a>

        <a href="inventory-intelligence.php" class="nav-item">
            <i class="fas fa-brain"></i>
            <span>Inventory Intelligence</span>
        </a>

        <a href="product-batches.php" class="nav-item">
            <i class="fas fa-layer-group"></i>
            <span>Batch & Lot Tracking</span>
        </a>

        <a href="cashier-shifts.php" class="nav-item">
            <i class="fas fa-clock"></i>
            <span>Cashier Shifts</span>
        </a>
        
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-wallet"></i>
            <span>Expenses</span>
        </a>
        
        <a href="categories.php" class="nav-item">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        
        <a href="customers.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        
        <a href="loyalty-points.php" class="nav-item">
            <i class="fas fa-gift"></i>
            <span>Loyalty Points</span>
        </a>
        
        <a href="loyalty-program.php" class="nav-item">
            <i class="fas fa-star"></i>
            <span>Loyalty Program</span>
        </a>
        
        <a href="sales.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Sales History</span>
        </a>
        
        <a href="suppliers.php" class="nav-item">
            <i class="fas fa-truck"></i>
            <span>Suppliers</span>
        </a>

        <a href="purchase-orders.php" class="nav-item">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Purchase Orders</span>
        </a>
        
        <a href="quotations.php" class="nav-item">
            <i class="fas fa-file-invoice"></i>
            <span>Quotations</span>
        </a>
        
        <a href="returns.php" class="nav-item">
            <i class="fas fa-undo"></i>
            <span>Returns</span>
        </a>
        
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        
        <?php if (strtolower($_SESSION['role']) === 'admin'): ?>
        <div class="nav-divider">
            <span>System Settings</span>
        </div>
        
        <a href="business-settings.php" class="nav-item">
            <i class="fas fa-store"></i>
            <span>Business and Tax</span>
        </a>

        <a href="mpesa-settings.php" class="nav-item">
            <i class="fas fa-mobile-alt"></i>
            <span>M-Pesa Settings</span>
        </a>
        
        <a href="whatsapp-settings.php" class="nav-item">
            <i class="fab fa-whatsapp"></i>
            <span>WhatsApp Settings</span>
        </a>
        
        <a href="email-settings.php" class="nav-item">
            <i class="fas fa-envelope"></i>
            <span>Email Settings</span>
        </a>
        <a href="credit-sales.php" class="nav-item">
            <i class="fas fa-credit-card"></i>
            <span>Credit Sales</span>
        </a>
        
        <a href="activity-log.php" class="nav-item">
            <i class="fas fa-history"></i>
            <span>Activity Log</span>
        </a>
        
        <a href="backup.php" class="nav-item">
            <i class="fas fa-database"></i>
            <span>Backup</span>
        </a>
        
        <a href="users.php" class="nav-item">
            <i class="fas fa-user-shield"></i>
            <span>Users</span>
        </a>
        <?php endif; ?>
    </nav>
    <div class="powered-by">Powered by Vision Data Company</div>
</aside>
