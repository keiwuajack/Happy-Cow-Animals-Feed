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
    <title>Loyalty Points - Happy Cow Animal Feeds</title>
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
            
            <div class="page-content settings-page">
                <div class="page-header">
                    <h1><i class="fas fa-gift"></i> Loyalty Points Program</h1>
                    <button class="btn btn-primary" id="settingsBtn">
                        <i class="fas fa-cog"></i> Program Settings
                    </button>
                </div>
                
                <!-- Program Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd;">
                            <i class="fas fa-users" style="color: #2196f3;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="activeCustomers">0</h3>
                            <p>Active Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3e0;">
                            <i class="fas fa-star" style="color: #ff9800;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalPoints">0</h3>
                            <p>Total Points Issued</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f3e5f5;">
                            <i class="fas fa-ticket-alt" style="color: #9c27b0;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="redeemedPoints">0</h3>
                            <p>Points Redeemed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e8f5e9;">
                            <i class="fas fa-coins" style="color: var(--primary-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="pendingPoints">0</h3>
                            <p>Active Points</p>
                        </div>
                    </div>
                </div>
                
                <!-- Current Settings Display -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> Current Program Settings</h3>
                    </div>
                    <div class="card-body" style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                            <div>
                                <strong><i class="fas fa-shopping-cart"></i> Points Per Purchase:</strong>
                                <p id="displayPointsPerPurchase" style="margin: 5px 0 0 0; font-size: 1.1em; color: var(--primary-color);">1 point per KSh 100</p>
                            </div>
                            <div>
                                <strong><i class="fas fa-money-bill"></i> Redemption Rate:</strong>
                                <p id="displayRedemptionRate" style="margin: 5px 0 0 0; font-size: 1.1em; color: var(--primary-color);">100 points = KSh 10</p>
                            </div>
                            <div>
                                <strong><i class="fas fa-calendar-alt"></i> Points Expiry:</strong>
                                <p id="displayExpiry" style="margin: 5px 0 0 0; font-size: 1.1em; color: var(--primary-color);">12 months</p>
                            </div>
                            <div>
                                <strong><i class="fas fa-check-circle"></i> Minimum Redemption:</strong>
                                <p id="displayMinRedemption" style="margin: 5px 0 0 0; font-size: 1.1em; color: var(--primary-color);">100 points</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Points Table -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-users"></i> Customer Points</h3>
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="searchCustomer" placeholder="Search customer...">
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customersTable">
                                <thead>
                                    <tr>
                                        <th>Customer Name</th>
                                        <th>Phone</th>
                                        <th>Current Points</th>
                                        <th>Lifetime Points</th>
                                        <th>Redeemed Points</th>
                                        <th>Member Since</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Settings Modal -->
    <div id="settingsModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2 id="modalTitle">Loyalty Program Settings</h2>
                <span class="close">&times;</span>
            </div>
            <form id="settingsForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Points Earning Rate <span class="text-danger">*</span></label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span>1 point for every KSh</span>
                            <input type="number" id="pointsPerAmount" required style="width: 100px;" value="100" min="1">
                            <span>spent</span>
                        </div>
                        <small class="form-text">How much customers need to spend to earn 1 point</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Points Redemption Rate <span class="text-danger">*</span></label>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <input type="number" id="pointsValue" required style="width: 100px;" value="100" min="1">
                            <span>points = KSh</span>
                            <input type="number" id="moneyValue" required style="width: 100px;" value="10" min="1">
                        </div>
                        <small class="form-text">How much discount customers get when redeeming points</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Points Expiry (Months) <span class="text-danger">*</span></label>
                        <input type="number" id="pointsExpiry" required value="12" min="1" max="60">
                        <small class="form-text">Points will expire after this many months (1-60)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Minimum Points for Redemption <span class="text-danger">*</span></label>
                        <input type="number" id="minRedemption" required value="100" min="1">
                        <small class="form-text">Minimum points required to redeem</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelSettings">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Points History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Points History - <span id="historyCustomerName"></span></h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Points</th>
                                <th>Description</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody">
                            <tr>
                                <td colspan="5" style="text-align: center;">Loading...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/loyalty.js?v=20261011-2"></script>
</body>
</html>
