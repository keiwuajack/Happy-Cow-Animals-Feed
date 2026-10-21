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
    <title>Database Backup - Happy Cow Animal Feeds</title>
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
                    <h1><i class="fas fa-database"></i> Database Backup & Restore</h1>
                </div>
                
                <!-- Backup Actions -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="card" style="background: linear-gradient(135deg, #2ecc71, #27ae60); color: white;">
                        <div class="card-body" style="padding: 30px; text-align: center;">
                            <i class="fas fa-download" style="font-size: 3em; margin-bottom: 15px;"></i>
                            <h3 style="margin: 0 0 10px 0;">Create Backup</h3>
                            <p style="margin: 0 0 20px 0; opacity: 0.9;">Download a complete backup of your database</p>
                            <button class="btn" style="background: white; color: var(--primary-color);" id="createBackupBtn">
                                <i class="fas fa-download"></i> Create Backup Now
                            </button>
                        </div>
                    </div>
                    
                    <div class="card" style="background: linear-gradient(135deg, #3498db, #2980b9); color: white;">
                        <div class="card-body" style="padding: 30px; text-align: center;">
                            <i class="fas fa-upload" style="font-size: 3em; margin-bottom: 15px;"></i>
                            <h3 style="margin: 0 0 10px 0;">Restore Backup</h3>
                            <p style="margin: 0 0 20px 0; opacity: 0.9;">Restore database from a backup file</p>
                            <input type="file" id="restoreFile" accept=".sql" style="display: none;">
                            <button class="btn" style="background: white; color: #3498db;" id="restoreBackupBtn">
                                <i class="fas fa-upload"></i> Choose Backup File
                            </button>
                        </div>
                    </div>
                    
                    <div class="card" style="background: linear-gradient(135deg, #9b59b6, #8e44ad); color: white;">
                        <div class="card-body" style="padding: 30px; text-align: center;">
                            <i class="fas fa-clock" style="font-size: 3em; margin-bottom: 15px;"></i>
                            <h3 style="margin: 0 0 10px 0;">Auto Backup</h3>
                            <p style="margin: 0 0 20px 0; opacity: 0.9;">Configure automatic daily backups</p>
                            <button class="btn" style="background: white; color: #9b59b6;" id="autoBackupBtn">
                                <i class="fas fa-cog"></i> Configure
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Backup Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd;">
                            <i class="fas fa-database" style="color: #2196f3;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="dbSize">0 MB</h3>
                            <p>Database Size</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e8f5e9;">
                            <i class="fas fa-save" style="color: var(--primary-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalBackups">0</h3>
                            <p>Total Backups</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fff3e0;">
                            <i class="fas fa-calendar" style="color: #ff9800;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="lastBackup">Never</h3>
                            <p>Last Backup</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f3e5f5;">
                            <i class="fas fa-hdd" style="color: #9c27b0;"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="backupSize">0 MB</h3>
                            <p>Total Backup Size</p>
                        </div>
                    </div>
                </div>
                
                <!-- Backup Files List -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-folder"></i> Available Backups</h3>
                        <button class="btn btn-danger" id="cleanupBtn">
                            <i class="fas fa-trash"></i> Cleanup Old Backups
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="backupsTable">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Warning Message -->
                <div class="card" style="margin-top: 20px; border-left: 5px solid #f39c12;">
                    <div class="card-body">
                        <h4 style="color: #f39c12; margin: 0 0 10px 0;">
                            <i class="fas fa-exclamation-triangle"></i> Important Notes
                        </h4>
                        <ul style="margin: 0; padding-left: 20px; color: #666;">
                            <li>Always create a backup before making major changes to the system</li>
                            <li>Store backups in a secure location outside the server</li>
                            <li>Restoring a backup will overwrite all current data</li>
                            <li>Test your backups regularly to ensure they work</li>
                            <li>Automatic backups are stored in the configured private backup directory</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto Backup Settings Modal -->
    <div id="autoBackupModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Auto Backup Settings</h2>
                <span class="close">&times;</span>
            </div>
            <form id="autoBackupForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Enable Auto Backup</label>
                        <select id="autoBackupEnabled">
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Backup Frequency</label>
                        <select id="backupFrequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Keep Last N Backups</label>
                        <input type="number" id="keepBackups" value="7" min="1" max="30">
                        <small class="form-text">Older backups will be automatically deleted</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelAutoBackup">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script src="assets/js/backup.js?v=20261011-2"></script>
</body>
</html>
