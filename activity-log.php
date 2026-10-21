<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/settings.css?v=20261018-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <main class="page-content settings-page audit-page">
                <div class="page-header">
                    <div>
                        <p class="settings-eyebrow">Security and accountability</p>
                        <h1><i class="fas fa-history"></i> Activity Log & Audit Trail</h1>
                        <p>Review staff actions and filter the audit history by date, user, or module.</p>
                    </div>
                    <button class="btn btn-danger" id="clearLogsBtn">
                        <i class="fas fa-trash"></i> Clear Old Logs
                    </button>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd;">
                            <i class="fas fa-list" style="color: #2196f3;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalLogs">0</h3>
                            <p>Total Logs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e8f5e9;">
                            <i class="fas fa-calendar-day" style="color: var(--primary-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="todayLogs">0</h3>
                            <p>Today's Activity</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3e0;">
                            <i class="fas fa-users" style="color: #ff9800;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeUsers">0</h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fce4ec;">
                            <i class="fas fa-exclamation-triangle" style="color: #e91e63;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="criticalActions">0</h3>
                            <p>Critical Actions</p>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-body">
                        <div class="audit-filter-grid">
                            <div class="form-group" style="margin: 0;">
                                <label>Filter by User</label>
                                <select id="filterUser">
                                    <option value="">All Users</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>Filter by Module</label>
                                <select id="filterModule">
                                    <option value="">All Modules</option>
                                    <option value="Sales">Sales</option>
                                    <option value="Products">Products</option>
                                    <option value="Customers">Customers</option>
                                    <option value="Stock Adjustments">Stock Adjustments</option>
                                    <option value="Expenses">Expenses</option>
                                    <option value="Loyalty Points">Loyalty Points</option>
                                    <option value="Users">Users</option>
                                    <option value="Categories">Categories</option>
                                    <option value="Suppliers">Suppliers</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>Start Date</label>
                                <input type="date" id="startDate">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>End Date</label>
                                <input type="date" id="endDate">
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label>Search</label>
                                <input type="text" id="searchLog" placeholder="Search activities...">
                            </div>
                            
                            <div class="audit-filter-actions">
                                <button class="btn btn-primary" id="applyFilters">
                                    <i class="fas fa-filter"></i> Apply
                                </button>
                                <button class="btn" id="resetFilters">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Activity Log Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-list"></i> Activity History</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="activityTable">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Module</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" style="text-align: center;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div id="pagination" style="margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 10px;">
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/activity-log.js?v=20261011-2"></script>
</body>
</html>
