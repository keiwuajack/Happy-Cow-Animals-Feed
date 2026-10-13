 <?php
session_start();
require_once 'config/database.php';

// Redirect to login if not authenticated
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
    <title>Happy Cow - Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20260908-2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
            
            <div class="dashboard">
                <div class="dashboard-heading">
                    <div>
                        <p class="eyebrow">Business overview</p>
                        <h1>Dashboard</h1>
                        <p class="dashboard-intro">Track today's performance, stock health, and recent activity at a glance.</p>
                    </div>
                    <a class="btn btn-primary" href="pos.php"><i class="fas fa-cash-register"></i> Open POS</a>
                </div>

                <div class="dashboard-actions" aria-label="Quick actions">
                    <a class="dashboard-action dashboard-action-primary" href="pos.php"><i class="fas fa-plus"></i><span>New Sale</span></a>
                    <a class="dashboard-action" href="products.php"><i class="fas fa-box-open"></i><span>Manage Products</span></a>
                    <a class="dashboard-action" href="customers.php"><i class="fas fa-user-plus"></i><span>Add Customer</span></a>
                    <a class="dashboard-action" href="credit-sales.php"><i class="fas fa-hand-holding-dollar"></i><span>Record Credit Payment</span></a>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Today's Sales</h3>
                            <span class="stat-context">Revenue collected today</span>
                            <p class="stat-value" id="todaySales">KSh 0.00</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Products</h3>
                            <span class="stat-context">Items in your catalogue</span>
                            <p class="stat-value" id="totalProducts">0</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Customers</h3>
                            <span class="stat-context">Registered customers</span>
                            <p class="stat-value" id="totalCustomers">0</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Low Stock Items</h3>
                            <span class="stat-context">Items needing attention</span>
                            <p class="stat-value" id="lowStockItems">0</p>
                        </div>
                    </div>

                    <div class="stat-card stat-card-credit">
                        <div class="stat-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Credit Balance Due</h3>
                            <span class="stat-context">Money customers owe</span>
                            <p class="stat-value" id="creditOutstanding">KSh 0.00</p>
                        </div>
                    </div>

                    <div class="stat-card stat-card-expiry">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-xmark"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Expiring Soon</h3>
                            <span class="stat-context">Within the next 30 days</span>
                            <p class="stat-value" id="expiringProducts">0</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-attention" id="dashboardAttention" hidden>
                    <i class="fas fa-circle-exclamation"></i>
                    <span id="dashboardAttentionText"></span>
                    <a href="products.php">Review products</a>
                </div>

                <section class="dashboard-needs-attention" id="dashboardNeedsAttention" hidden>
                    <div class="section-heading-row">
                        <div>
                            <h2>Needs Attention</h2>
                            <p>Products that may need reordering or review.</p>
                        </div>
                        <a href="products.php">Manage products <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="attention-list" id="attentionList"></div>
                </section>
                
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
                
                <div class="recent-sales">
                    <div class="section-heading-row">
                        <div>
                            <h2>Recent Transactions</h2>
                            <p>Latest sales recorded in the system.</p>
                        </div>
                        <a href="sales.php">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="dashboard-table-wrap">
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
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/dashboard.js?v=20261011-2"></script>
</body>
</html>
