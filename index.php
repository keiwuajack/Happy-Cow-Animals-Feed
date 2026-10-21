 <?php
session_start();
require_once 'config/database.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$dashboardRole = strtolower((string) ($_SESSION['role'] ?? ''));
$isCashierDashboard = $dashboardRole === 'cashier';
$canViewShifts = security_has_permission('shifts.view');
$canViewSales = security_has_permission('sales.view');
$canViewProducts = security_has_permission('products.lookup') || security_has_permission('products.manage');
$canViewCustomers = security_has_permission('customers.lookup') || security_has_permission('customers.manage');
$canViewInventory = security_has_permission('inventory.view') || security_has_permission('inventory.manage');
$canViewCredit = security_has_permission('credit.manage');
$canViewAnalytics = security_has_permission('reports.view');
$inventoryReviewUrl = security_has_permission('products.manage') ? 'products.php' : 'inventory-intelligence.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Happy Cow - Dashboard</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20261018-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <?php if ($canViewAnalytics): ?><script src="assets/vendor/chartjs/chart.umd.min.js"></script><?php endif; ?>
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()" style="display:none;">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="dashboard dashboard-modern" data-is-cashier="<?php echo $isCashierDashboard ? 'true' : 'false'; ?>" data-can-view-shifts="<?php echo $canViewShifts ? 'true' : 'false'; ?>" data-can-view-inventory="<?php echo $canViewInventory ? 'true' : 'false'; ?>" data-can-view-analytics="<?php echo $canViewAnalytics ? 'true' : 'false'; ?>" data-can-view-sales="<?php echo $canViewSales ? 'true' : 'false'; ?>" data-inventory-review-url="<?php echo htmlspecialchars($inventoryReviewUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="dashboard-heading">
                    <div>
                    <p class="eyebrow"><?php echo $isCashierDashboard ? 'CASHIER WORKSPACE' : 'HAPPY COW / BUSINESS OVERVIEW'; ?></p>
                        <h1><?php echo $isCashierDashboard ? 'Your workday at a glance' : 'Dashboard'; ?></h1>
                    <p class="dashboard-intro"><?php echo $isCashierDashboard ? 'Check your shift, start a sale, and keep track of today\'s takings.' : 'A clear view of today\'s sales, stock health, and business activity.'; ?></p>
                    </div>
                    <?php if (security_has_permission('pos.use')): ?><a class="btn btn-primary dashboard-hero-action" href="pos.php"><i class="fas fa-cash-register" aria-hidden="true"></i> Start a sale</a><?php endif; ?>
                </div>

                <?php if ($canViewShifts): ?>
                <section class="dashboard-shift-overview" id="dashboardShiftOverview" aria-live="polite" aria-label="Current cashier shift">
                    <div class="dashboard-shift-status">
                        <span class="dashboard-shift-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></span>
                        <div><span class="dashboard-shift-kicker">CASHIER SHIFT</span><h2 id="dashboardShiftTitle">Checking shift...</h2><p id="dashboardShiftMessage">Loading your shift status.</p></div>
                    </div>
                    <div class="dashboard-shift-metrics" id="dashboardShiftMetrics"></div>
                    <a class="dashboard-shift-link" href="cashier-shifts.php" id="dashboardShiftLink"><span id="dashboardShiftLinkText">Open shift page</span><i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                </section>
                <?php endif; ?>

                <div class="dashboard-actions" aria-label="Quick actions">
                    <?php if (security_has_permission('pos.use')): ?>
                    <a class="dashboard-action dashboard-action-primary" href="pos.php"><span class="dashboard-action-icon"><i class="fas fa-plus" aria-hidden="true"></i></span><span><strong>New sale</strong><small>Ring up a customer</small></span></a>
                    <?php endif; ?>
                    <?php if ($canViewShifts): ?>
                    <a class="dashboard-action" href="cashier-shifts.php"><span class="dashboard-action-icon"><i class="fas fa-clock" aria-hidden="true"></i></span><span><strong>Cashier shift</strong><small>Open or close your drawer</small></span></a>
                    <?php endif; ?>
                    <?php if ($canViewSales): ?>
                    <a class="dashboard-action" href="sales.php"><span class="dashboard-action-icon"><i class="fas fa-receipt" aria-hidden="true"></i></span><span><strong>Sales history</strong><small>Find a receipt or sale</small></span></a>
                    <?php endif; ?>
                    <?php if (security_has_permission('products.manage')): ?>
                    <a class="dashboard-action" href="products.php"><span class="dashboard-action-icon"><i class="fas fa-box-open" aria-hidden="true"></i></span><span><strong>Products</strong><small>Update your catalogue</small></span></a>
                    <?php endif; ?>
                    <?php if (security_has_permission('customers.manage')): ?>
                    <a class="dashboard-action" href="customers.php?action=add"><span class="dashboard-action-icon"><i class="fas fa-user-plus" aria-hidden="true"></i></span><span><strong>Add customer</strong><small>Save a new customer</small></span></a>
                    <?php endif; ?>
                    <?php if ($canViewCredit): ?>
                    <a class="dashboard-action" href="credit-sales.php"><span class="dashboard-action-icon"><i class="fas fa-hand-holding-dollar" aria-hidden="true"></i></span><span><strong>Credit sales</strong><small>Review balances and payments</small></span></a>
                    <?php endif; ?>
                </div>
                
                <div class="stats-grid">
                    <?php if ($canViewSales): ?>
                    <div class="stat-card stat-card-sales">
                        <div class="stat-icon"><i class="fas fa-cash-register" aria-hidden="true"></i></div>
                        <div class="stat-details">
                            <h3><?php echo $isCashierDashboard ? 'Your shift sales' : 'Today\'s sales'; ?></h3>
                            <span class="stat-context"><?php echo $isCashierDashboard ? 'Payments received in your active shift' : 'Revenue collected today'; ?></span>
                            <p class="stat-value" id="todaySales">KSh 0.00</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewProducts && !$isCashierDashboard): ?>
                    <div class="stat-card stat-card-products">
                        <div class="stat-icon"><i class="fas fa-boxes-stacked" aria-hidden="true"></i></div>
                        <div class="stat-details"><h3>Products</h3><span class="stat-context">Items in the catalogue</span><p class="stat-value" id="totalProducts">0</p></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewCustomers && !$isCashierDashboard): ?>
                    <div class="stat-card stat-card-customers">
                        <div class="stat-icon"><i class="fas fa-users" aria-hidden="true"></i></div>
                        <div class="stat-details"><h3>Customers</h3><span class="stat-context">Registered customers</span><p class="stat-value" id="totalCustomers">0</p></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewInventory): ?>
                    <div class="stat-card stat-card-stock">
                        <div class="stat-icon"><i class="fas fa-triangle-exclamation" aria-hidden="true"></i></div>
                        <div class="stat-details"><h3>Low stock</h3><span class="stat-context">Items at or below reorder level</span><p class="stat-value" id="lowStockItems">0</p></div>
                    </div>
                    <div class="stat-card stat-card-expiry">
                        <div class="stat-icon"><i class="fas fa-calendar-xmark" aria-hidden="true"></i></div>
                        <div class="stat-details"><h3>Expiring soon</h3><span class="stat-context">Within the next 30 days</span><p class="stat-value" id="expiringProducts">0</p></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($canViewCredit): ?>
                    <div class="stat-card stat-card-credit">
                        <div class="stat-icon"><i class="fas fa-file-invoice-dollar" aria-hidden="true"></i></div>
                        <div class="stat-details"><h3>Credit balance due</h3><span class="stat-context">Money customers owe</span><p class="stat-value" id="creditOutstanding">KSh 0.00</p></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($canViewInventory): ?>
                <div class="dashboard-attention" id="dashboardAttention" hidden>
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span id="dashboardAttentionText"></span>
                    <a href="<?php echo htmlspecialchars($inventoryReviewUrl, ENT_QUOTES, 'UTF-8'); ?>">Review stock</a>
                </div>

                <section class="dashboard-needs-attention" id="dashboardNeedsAttention" hidden>
                    <div class="section-heading-row">
                        <div>
                            <h2>Needs Attention</h2>
                            <p>Products that may need reordering or review.</p>
                        </div>
                        <a href="<?php echo htmlspecialchars($inventoryReviewUrl, ENT_QUOTES, 'UTF-8'); ?>">Review stock <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="attention-list" id="attentionList"></div>
                </section>
                <?php endif; ?>
                
                <?php if ($canViewAnalytics): ?>
                <div class="dashboard-section-heading">
                    <div>
                        <h2>Performance insights</h2>
                        <p>Use these trends to make faster stock and sales decisions.</p>
                    </div>
                </div>

                <div class="dashboard-chart-grid">
                    <!-- Sales Trend Chart -->
                    <div class="card dashboard-chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-line"></i> Sales Trend (Last 7 Days)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesTrendChart" height="250"></canvas>
                        </div>
                    </div>
                    
                    <!-- Top Products Chart -->
                    <div class="card dashboard-chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-pie"></i> Top 5 Products</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="topProductsChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-chart-grid">
                    <!-- Category Breakdown Chart -->
                    <div class="card dashboard-chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-chart-bar"></i> Sales by Category</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart" height="250"></canvas>
                        </div>
                    </div>
                    
                    <!-- Revenue vs Expenses Chart -->
                    <div class="card dashboard-chart-card">
                        <div class="card-header">
                            <h3><i class="fas fa-money-bill-wave"></i> Revenue vs Expenses (This Month)</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueExpensesChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($canViewSales): ?>
                <div class="recent-sales">
                    <div class="section-heading-row">
                        <div>
                            <h2>Recent Transactions</h2>
                            <p><?php echo $isCashierDashboard ? 'Latest sales recorded by you.' : 'Latest sales recorded in the system.'; ?></p>
                        </div>
                        <a href="sales.php">View sales <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="dashboard-table-wrap" data-collapsible-list="always" data-list-title="Recent transactions">
                        <table id="recentTransactions">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic content will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/dashboard.js?v=20261021-1"></script>
</body>
</html>
