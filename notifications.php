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
    <title>Notifications - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notifications-grid {
            display: grid;
            gap: 15px;
        }
        
        .notification-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            gap: 15px;
            align-items: flex-start;
            transition: transform 0.2s;
        }
        
        .notification-card:hover {
            transform: translateX(5px);
        }
        
        .notification-card.critical {
            border-left-color: var(--danger-color);
            background: #fff5f5;
        }
        
        .notification-card.warning {
            border-left-color: var(--warning-color);
            background: #fffbf5;
        }
        
        .notification-card.info {
            border-left-color: var(--info-color);
            background: #f5f9ff;
        }
        
        .notification-icon {
            font-size: 28px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            flex-shrink: 0;
        }
        
        .notification-card.critical .notification-icon {
            background: #fee;
            color: var(--danger-color);
        }
        
        .notification-card.warning .notification-icon {
            background: #ffefd5;
            color: var(--warning-color);
        }
        
        .notification-card.info .notification-icon {
            background: #e3f2fd;
            color: var(--info-color);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .notification-message {
            color: #666;
            margin-bottom: 8px;
        }
        
        .notification-meta {
            display: flex;
            gap: 15px;
            font-size: 13px;
            color: #999;
        }
        
        .notification-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            font-size: 36px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .stat-card.critical .stat-icon {
            background: #fee;
            color: var(--danger-color);
        }
        
        .stat-card.warning .stat-icon {
            background: #ffefd5;
            color: var(--warning-color);
        }
        
        .stat-card.info .stat-icon {
            background: #e3f2fd;
            color: var(--info-color);
        }
        
        .stat-content h3 {
            font-size: 32px;
            margin: 0;
            color: var(--dark-color);
        }
        
        .stat-content p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 20px;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .filter-tab:hover {
            border-color: var(--primary-color);
        }
        
        .filter-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--primary-color);
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <h1><i class="fas fa-bell"></i> Notifications & Alerts</h1>
                    <button class="btn btn-primary" onclick="loadNotifications()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
                
                <div class="stats-summary" id="statsSummary">
                    <!-- Stats will be loaded here -->
                </div>
                
                <div class="content-box">
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterNotifications('all')">All</button>
                        <button class="filter-tab" onclick="filterNotifications('critical')">Critical</button>
                        <button class="filter-tab" onclick="filterNotifications('warning')">Warnings</button>
                        <button class="filter-tab" onclick="filterNotifications('info')">Info</button>
                        <button class="filter-tab" onclick="filterNotifications('low_stock')">Low Stock</button>
                        <button class="filter-tab" onclick="filterNotifications('expired')">Expired</button>
                        <button class="filter-tab" onclick="filterNotifications('expiring_soon')">Expiring Soon</button>
                    </div>
                    
                    <div class="notifications-grid" id="notificationsGrid">
                        <!-- Notifications will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/notifications.js?v=20261011-2"></script>
</body>
</html>
