<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand"><span class="brand-mark" aria-hidden="true"><i class="fas fa-cow"></i></span><span><strong>Happy Cow</strong><small>Animal Feeds</small></span></div>
    </div>
    
    <nav class="sidebar-nav">
        <?php if (security_has_permission('dashboard.view')): ?>
        <a href="index.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('pos.use')): ?>
        <a href="pos.php" class="nav-item nav-item-pos">
            <i class="fas fa-cash-register"></i>
            <span class="nav-item-label">Point of Sale</span>
            <span class="nav-item-badge">SELL</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('products.manage')): ?>
        <a href="products.php" class="nav-item">
            <i class="fas fa-boxes"></i>
            <span>Products</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('inventory.manage')): ?>
        <a href="stock-adjustments.php" class="nav-item">
            <i class="fas fa-warehouse"></i>
            <span>Stock Adjustments</span>
        </a>
        <?php endif; ?>

        <?php if (security_has_permission('inventory.view')): ?>
        <a href="inventory-intelligence.php" class="nav-item">
            <i class="fas fa-brain"></i>
            <span>Inventory Intelligence</span>
        </a>
        <?php endif; ?>

        <?php if (security_has_permission('inventory.manage')): ?>
        <a href="product-batches.php" class="nav-item">
            <i class="fas fa-layer-group"></i>
            <span>Batch & Lot Tracking</span>
        </a>
        <?php endif; ?>

        <?php if (security_has_permission('shifts.view')): ?>
        <a href="cashier-shifts.php" class="nav-item">
            <i class="fas fa-clock"></i>
            <span>Cashier Shifts</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('expenses.manage')): ?>
        <a href="expenses.php" class="nav-item">
            <i class="fas fa-wallet"></i>
            <span>Expenses</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('products.manage')): ?>
        <a href="categories.php" class="nav-item">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('customers.manage')): ?>
        <a href="customers.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('loyalty.manage')): ?>
        <a href="loyalty-points.php" class="nav-item">
            <i class="fas fa-gift"></i>
            <span>Loyalty Points</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('loyalty.settings')): ?>
        <a href="loyalty-program.php" class="nav-item">
            <i class="fas fa-star"></i>
            <span>Loyalty Program</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('sales.view')): ?>
        <a href="sales.php" class="nav-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Sales History</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('suppliers.manage')): ?>
        <a href="suppliers.php" class="nav-item">
            <i class="fas fa-truck"></i>
            <span>Suppliers</span>
        </a>
        <?php endif; ?>

        <?php if (security_has_permission('purchasing.manage')): ?>
        <a href="purchase-orders.php" class="nav-item">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>Purchase Orders</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('quotations.manage')): ?>
        <a href="quotations.php" class="nav-item">
            <i class="fas fa-file-invoice"></i>
            <span>Quotations</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('returns.manage')): ?>
        <a href="returns.php" class="nav-item">
            <i class="fas fa-undo"></i>
            <span>Returns</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('reports.view')): ?>
        <a href="reports.php" class="nav-item">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <?php endif; ?>
        
        <?php if (security_has_permission('settings.manage') || security_has_permission('credit.manage') || security_has_permission('activity.view') || security_has_permission('backup.manage') || security_has_permission('users.manage')): ?>
        <div class="nav-divider">
            <span>System Settings</span>
        </div>
        <?php if (security_has_permission('settings.manage')): ?>
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
        <?php endif; ?>
        <?php if (security_has_permission('credit.manage')): ?>
        <a href="credit-sales.php" class="nav-item">
            <i class="fas fa-credit-card"></i>
            <span>Credit Sales</span>
        </a>
        <?php endif; ?>
        <?php if (security_has_permission('activity.view')): ?>
        <a href="activity-log.php" class="nav-item">
            <i class="fas fa-history"></i>
            <span>Activity Log</span>
        </a>
        <?php endif; ?>
        <?php if (security_has_permission('backup.manage')): ?>
        <a href="backup.php" class="nav-item">
            <i class="fas fa-database"></i>
            <span>Backup</span>
        </a>
        <?php endif; ?>
        <?php if (security_has_permission('users.manage')): ?>
        <a href="users.php" class="nav-item">
            <i class="fas fa-user-shield"></i>
            <span>Users</span>
        </a>
        <?php endif; ?>
        <?php endif; ?>
    </nav>
    <div class="powered-by">Powered by Vision Data Company</div>
</aside>
