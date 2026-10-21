<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Settings - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/settings.css?v=20261018-1">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20261018-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="mobile-overlay" onclick="toggleMobileMenu()"></div>
    
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            <main class="page-content settings-page">
                <div class="page-header">
                    <div>
                        <p class="settings-eyebrow">Payment integration</p>
                        <h1><i class="fas fa-mobile-alt"></i> M-Pesa Payment Settings</h1>
                        <p>Configure Daraja credentials, callback details, and your payment environment.</p>
                    </div>
                </div>
            
                <div class="card settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Daraja API Configuration</h3>
                    <div class="card-actions">
                        <button class="btn btn-sm" onclick="testConnection()">
                            <i class="fas fa-check-circle"></i> Test Connection
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="mpesaSettingsForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-key"></i> Consumer Key</label>
                                <input type="text" id="consumerKey" required placeholder="Your Daraja API Consumer Key">
                                <small class="form-text">Get from Safaricom Daraja Portal</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-lock"></i> Consumer Secret</label>
                                <input type="password" id="consumerSecret" required placeholder="Your Consumer Secret">
                                <small class="form-text">Keep this confidential</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-building"></i> Business Shortcode</label>
                                <input type="text" id="shortcode" required placeholder="e.g., 174379">
                                <small class="form-text">Your Paybill or Till Number</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-shield-alt"></i> Passkey</label>
                                <textarea id="passkey" rows="3" required placeholder="Your Lipa Na M-Pesa Online Passkey"></textarea>
                                <small class="form-text">From Daraja Portal</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-link"></i> Callback URL</label>
                                <input type="url" id="callbackUrl" required placeholder="https://yourdomain.com/api/mpesa_callback.php">
                                <small class="form-text">Where M-Pesa sends payment notifications</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-server"></i> Environment</label>
                                <select id="environment" required>
                                    <option value="sandbox">Sandbox (Testing)</option>
                                    <option value="live">Live (Production)</option>
                                </select>
                                <small class="form-text">Use sandbox for testing</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="settings-toggle">
                                <input type="checkbox" id="enabled" checked>
                                <span>Enable M-Pesa Payments</span>
                            </label>
                        </div>
                        
                        <div class="settings-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Settings
                            </button>
                            <button type="button" class="btn" onclick="loadSettings()">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
                <div class="card settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent M-Pesa Transactions</h3>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table id="transactionsTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Phone Number</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>M-Pesa Receipt</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transactionsBody">
                                <tr>
                                    <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
                <div class="card settings-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Setup Instructions</h3>
                </div>
                <div class="card-body">
                    <div class="instructions">
                        <h4>📱 How to Set Up M-Pesa Daraja API:</h4>
                        <ol>
                            <li>
                                <strong>Create Daraja Account:</strong>
                                <p>Visit <a href="https://developer.safaricom.co.ke" target="_blank">developer.safaricom.co.ke</a> and create an account</p>
                            </li>
                            <li>
                                <strong>Create App:</strong>
                                <p>Create a new app and select "Lipa Na M-Pesa Online"</p>
                            </li>
                            <li>
                                <strong>Get Credentials:</strong>
                                <p>Copy Consumer Key, Consumer Secret, and Passkey from your app</p>
                            </li>
                            <li>
                                <strong>Register URL:</strong>
                                <p>Register your callback URL in the Daraja portal</p>
                            </li>
                            <li>
                                <strong>Test in Sandbox:</strong>
                                <p>Use sandbox environment first with test credentials</p>
                            </li>
                            <li>
                                <strong>Go Live:</strong>
                                <p>Apply for production access and switch to live environment</p>
                            </li>
                        </ol>
                        
                        <div class="settings-help">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Pro Tip:</strong> Always test thoroughly in sandbox before going live. Use the test button above to verify your configuration.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script>
        let settings = null;
        
        // Load settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            loadTransactions();
        });
        
        // Load current settings
        function loadSettings() {
            fetch('api/get_mpesa_settings.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.settings) {
                        settings = data.settings;
                        document.getElementById('consumerKey').value = data.settings.consumer_key || '';
                        document.getElementById('consumerSecret').value = data.settings.consumer_secret || '';
                        document.getElementById('shortcode').value = data.settings.shortcode || '';
                        document.getElementById('passkey').value = data.settings.passkey || '';
                        document.getElementById('callbackUrl').value = data.settings.callback_url || '';
                        document.getElementById('environment').value = data.settings.environment || 'sandbox';
                        document.getElementById('enabled').checked = data.settings.enabled == 1;
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Save settings
        document.getElementById('mpesaSettingsForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = {
                consumer_key: document.getElementById('consumerKey').value,
                consumer_secret: document.getElementById('consumerSecret').value,
                shortcode: document.getElementById('shortcode').value,
                passkey: document.getElementById('passkey').value,
                callback_url: document.getElementById('callbackUrl').value,
                environment: document.getElementById('environment').value,
                enabled: document.getElementById('enabled').checked ? 1 : 0
            };
            
            fetch('api/save_mpesa_settings.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Settings saved successfully', 'success');
                } else {
                    showNotification(data.message || 'Failed to save settings', 'error');
                }
            })
            .catch(error => {
                showNotification('Error saving settings', 'error');
                console.error('Error:', error);
            });
        });
        
        // Test connection
        function testConnection() {
            showNotification('Testing M-Pesa connection...', 'info');
            
            fetch('api/test_mpesa.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('✅ Connection successful! M-Pesa is configured correctly.', 'success');
                    } else {
                        showNotification('❌ Connection failed: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Error testing connection', 'error');
                    console.error('Error:', error);
                });
        }
        
        // Load transactions
        function loadTransactions() {
            fetch('api/get_mpesa_transactions.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTransactions(data.transactions);
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Display transactions
        function displayTransactions(transactions) {
            const tbody = document.getElementById('transactionsBody');
            
            if (!transactions || transactions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No transactions found</td></tr>';
                return;
            }
            
            tbody.innerHTML = transactions.map(txn => `
                <tr>
                    <td>${new Date(txn.created_at).toLocaleString()}</td>
                    <td>${txn.phone_number}</td>
                    <td>KSh ${parseFloat(txn.amount).toFixed(2)}</td>
                    <td>${txn.account_reference || '-'}</td>
                    <td>${txn.mpesa_receipt_number || '-'}</td>
                    <td><span class="badge badge-${getStatusClass(txn.status)}">${txn.status}</span></td>
                    <td>
                        <button class="btn btn-sm" onclick="viewTransaction('${txn.checkout_request_id}')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function getStatusClass(status) {
            const classes = {
                'completed': 'success',
                'pending': 'warning',
                'failed': 'danger',
                'cancelled': 'secondary'
            };
            return classes[status] || 'secondary';
        }
        
        function viewTransaction(checkoutRequestId) {
            // Implement transaction details modal
            alert('Transaction details: ' + checkoutRequestId);
        }
    </script>
</body>
</html>
