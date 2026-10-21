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
    <title>Loyalty Program - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/settings.css?v=20261018-1">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20261018-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
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
            <main class="page-content settings-page">
            <div class="page-header">
                <div><p class="settings-eyebrow">Customer rewards</p><h1><i class="fas fa-gift"></i> Loyalty Program Management</h1>
                <p>Reward your customers and increase retention</p>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Enrolled Customers</h3>
                        <p class="stat-value" id="totalEnrolled">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Total Points Issued</h3>
                        <p class="stat-value" id="totalPointsIssued">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Points Redeemed</h3>
                        <p class="stat-value" id="totalPointsRedeemed">0</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="stat-details">
                        <h3>Points Value</h3>
                        <p class="stat-value" id="pointsValue">KSh 0</p>
                    </div>
                </div>
            </div>
            
            <!-- Settings Card -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Loyalty Program Settings</h3>
                </div>
                <div class="card-body">
                    <form id="loyaltySettingsForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-percentage"></i> Points Earning Rate</label>
                                <input type="number" id="pointsRate" min="0" step="0.1" required>
                                <small class="form-text">Points earned per KSh 100 spent</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-money-bill"></i> Redemption Value</label>
                                <input type="number" id="redemptionRate" min="0" step="0.1" required>
                                <small class="form-text">KSh value per loyalty point</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Points Expiry (Days)</label>
                                <input type="number" id="expiryDays" min="0" value="365">
                                <small class="form-text">Days before points expire (0 = never)</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-gift"></i> Welcome Bonus Points</label>
                                <input type="number" id="welcomeBonus" min="0" value="100">
                                <small class="form-text">Points given on enrollment</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="autoEnroll" checked>
                                <span>Auto-enroll new customers</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" id="enableProgram" checked>
                                <span>Enable Loyalty Program</span>
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Top Loyalty Customers -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-trophy"></i> Top Loyalty Customers</h3>
                    <div class="card-actions">
                        <button class="btn btn-sm" onclick="exportCustomers()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="loyaltyCustomersTable">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Points Balance</th>
                                    <th>Total Earned</th>
                                    <th>Total Redeemed</th>
                                    <th>Enrollment Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customersBody">
                                <tr>
                                    <td colspan="8" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Points Activity -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Points Activity</h3>
                    <div class="card-actions">
                        <select id="activityFilter" onchange="loadActivity()">
                            <option value="all">All Transactions</option>
                            <option value="earned">Points Earned</option>
                            <option value="redeemed">Points Redeemed</option>
                            <option value="adjustment">Adjustments</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="activityTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Type</th>
                                    <th>Points</th>
                                    <th>Description</th>
                                    <th>Balance After</th>
                                </tr>
                            </thead>
                            <tbody id="activityBody">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            </main>
        </div>
    </div>
    
    <!-- Adjust Points Modal -->
    <div id="adjustPointsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Adjust Customer Points</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="adjustPointsForm">
                    <input type="hidden" id="adjustCustomerId">
                    
                    <div class="form-group">
                        <label>Customer</label>
                        <input type="text" id="adjustCustomerName" readonly style="background:#f0f0f0;">
                    </div>
                    
                    <div class="form-group">
                        <label>Current Balance</label>
                        <input type="text" id="adjustCurrentBalance" readonly style="background:#f0f0f0; font-weight:bold; color:#27ae60;">
                    </div>
                    
                    <div class="form-group">
                        <label>Adjustment Type</label>
                        <select id="adjustmentType" required>
                            <option value="add">Add Points</option>
                            <option value="subtract">Subtract Points</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Points Amount</label>
                        <input type="number" id="adjustPoints" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea id="adjustReason" rows="3" required placeholder="Explain why points are being adjusted..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn" onclick="closeAdjustModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveAdjustment()">
                    <i class="fas fa-check"></i> Apply Adjustment
                </button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script>
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadSettings();
            loadCustomers();
            loadActivity();
        });
        
        // Load statistics
        function loadStats() {
            fetch('api/get_loyalty_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalEnrolled').textContent = data.stats.total_customers || 0;
                        document.getElementById('totalPointsIssued').textContent = (data.stats.total_earned || 0).toLocaleString();
                        document.getElementById('totalPointsRedeemed').textContent = (data.stats.total_redeemed || 0).toLocaleString();
                        document.getElementById('pointsValue').textContent = 'KSh ' + (data.stats.points_value || 0).toLocaleString();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Load settings
        function loadSettings() {
            fetch('api/get_loyalty_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.settings) {
                        document.getElementById('pointsRate').value = data.settings.points_rate || 1;
                        document.getElementById('redemptionRate').value = data.settings.redemption_rate || 10;
                        document.getElementById('expiryDays').value = data.settings.expiry_days || 365;
                        document.getElementById('welcomeBonus').value = data.settings.welcome_bonus || 100;
                        document.getElementById('autoEnroll').checked = data.settings.auto_enroll == 1;
                        document.getElementById('enableProgram').checked = data.settings.enabled == 1;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Save settings
        document.getElementById('loyaltySettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                points_rate: document.getElementById('pointsRate').value,
                redemption_rate: document.getElementById('redemptionRate').value,
                expiry_days: document.getElementById('expiryDays').value,
                welcome_bonus: document.getElementById('welcomeBonus').value,
                auto_enroll: document.getElementById('autoEnroll').checked ? 1 : 0,
                enabled: document.getElementById('enableProgram').checked ? 1 : 0
            };
            
            fetch('api/save_loyalty_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Settings saved successfully', 'success');
                    loadStats(); // Refresh stats
                } else {
                    showNotification('Failed to save settings', 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving settings', 'error');
                console.error('Error:', error);
            });
        });
        
        // Load customers
        function loadCustomers() {
            fetch('api/get_loyalty_customers.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayCustomers(data.customers);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Display customers
        function displayCustomers(customers) {
            const tbody = document.getElementById('customersBody');
            
            if (!customers || customers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No customers found</td></tr>';
                return;
            }
            
            tbody.innerHTML = customers.map((customer, index) => `
                <tr>
                    <td><strong>#${index + 1}</strong></td>
                    <td>${customer.name}</td>
                    <td>${customer.phone || '-'}</td>
                    <td><span class="badge badge-primary">${customer.loyalty_points || 0} pts</span></td>
                    <td>${customer.total_earned || 0} pts</td>
                    <td>${customer.total_redeemed || 0} pts</td>
                    <td>${customer.enrolled_date ? new Date(customer.enrolled_date).toLocaleDateString() : '-'}</td>
                    <td>
                        <button class="btn btn-sm" onclick="adjustPoints(${customer.id}, '${customer.name}', ${customer.loyalty_points || 0})">
                            <i class="fas fa-edit"></i> Adjust
                        </button>
                        <button class="btn btn-sm" onclick="viewHistory(${customer.id})">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        // Load activity
        function loadActivity() {
            const filter = document.getElementById('activityFilter').value;
            
            fetch('api/get_points_history.php?type=' + filter)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivity(data.transactions);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Display activity
        function displayActivity(transactions) {
            const tbody = document.getElementById('activityBody');
            
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No activity found</td></tr>';
                return;
            }
            
            tbody.innerHTML = transactions.map(txn => `
                <tr>
                    <td>${new Date(txn.created_at).toLocaleString()}</td>
                    <td>${txn.customer_name}</td>
                    <td><span class="badge badge-${getBadgeType(txn.transaction_type)}">${txn.transaction_type}</span></td>
                    <td>${txn.points_earned > 0 ? '+' + txn.points_earned : (txn.points_redeemed > 0 ? '-' + txn.points_redeemed : 0)}</td>
                    <td>${txn.description || '-'}</td>
                    <td>${txn.balance_after || 0} pts</td>
                </tr>
            `).join('');
        }
        
        function getBadgeType(type) {
            const types = {
                'earned': 'success',
                'redeemed': 'warning',
                'adjustment': 'info'
            };
            return types[type] || 'secondary';
        }
        
        // Adjust points
        function adjustPoints(customerId, customerName, currentBalance) {
            document.getElementById('adjustCustomerId').value = customerId;
            document.getElementById('adjustCustomerName').value = customerName;
            document.getElementById('adjustCurrentBalance').value = currentBalance + ' points';
            document.getElementById('adjustPoints').value = '';
            document.getElementById('adjustReason').value = '';
            
            document.getElementById('adjustPointsModal').classList.add('show');
        }
        
        function closeAdjustModal() {
            document.getElementById('adjustPointsModal').classList.remove('show');
        }
        
        function saveAdjustment() {
            const customerId = document.getElementById('adjustCustomerId').value;
            const type = document.getElementById('adjustmentType').value;
            const points = parseInt(document.getElementById('adjustPoints').value);
            const reason = document.getElementById('adjustReason').value;
            
            if (!points || points < 1) {
                showNotification('Please enter a valid points amount', 'error');
                return;
            }
            
            if (!reason.trim()) {
                showNotification('Please provide a reason for adjustment', 'error');
                return;
            }
            
            fetch('api/adjust_loyalty_points.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    customer_id: customerId,
                    type: type,
                    points: points,
                    reason: reason
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Points adjusted successfully', 'success');
                    closeAdjustModal();
                    loadCustomers();
                    loadActivity();
                    loadStats();
                } else {
                    showNotification('Failed to adjust points: ' + data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error adjusting points', 'error');
                console.error('Error:', error);
            });
        }
        
        function viewHistory(customerId) {
            window.location.href = `customer-loyalty-history.php?id=${customerId}`;
        }
        
        function exportCustomers() {
            window.location.href = 'api/export_loyalty_customers.php';
        }
    </script>
</body>
</html>
