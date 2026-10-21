<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Only admin can access user management
if ($_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/users.css?v=20261020-3">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header users-page-header">
                    <div>
                        <p class="users-eyebrow">Administration</p>
                        <h1><i class="fas fa-user-shield"></i> User Management</h1>
                        <p class="users-page-description">Manage staff access, roles, and account status. Edit a cashier or manager to choose their sections.</p>
                    </div>
                    <button class="btn btn-primary users-add-button" id="addUserBtn">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
                
                <div class="stats-grid users-stats-grid">
                    <div class="stat-card users-stat-card">
                        <div class="stat-icon users-stat-icon users-stat-icon-total"><i class="fas fa-users"></i></div>
                        <div class="stat-details"><h3 id="userTotal">0</h3><p>Total Users</p></div>
                    </div>
                    <div class="stat-card users-stat-card">
                        <div class="stat-icon users-stat-icon users-stat-icon-active"><i class="fas fa-user-check"></i></div>
                        <div class="stat-details"><h3 id="userActive">0</h3><p>Active Accounts</p></div>
                    </div>
                    <div class="stat-card users-stat-card">
                        <div class="stat-icon users-stat-icon users-stat-icon-admin"><i class="fas fa-user-shield"></i></div>
                        <div class="stat-details"><h3 id="userAdmins">0</h3><p>Administrators</p></div>
                    </div>
                    <div class="stat-card users-stat-card">
                        <div class="stat-icon users-stat-icon users-stat-icon-cashier"><i class="fas fa-cash-register"></i></div>
                        <div class="stat-details"><h3 id="userCashiers">0</h3><p>Cashiers</p></div>
                    </div>
                </div>

                <div class="content-box users-list-card">
                    <div class="users-list-header">
                        <div>
                            <p class="users-eyebrow">Team directory</p>
                            <h2><i class="fas fa-address-book"></i> Staff accounts</h2>
                            <p>Search staff and manage their access.</p>
                        </div>
                        <div class="users-list-controls">
                            <label class="users-search-wrap" for="usersSearch">
                                <i class="fas fa-search" aria-hidden="true"></i>
                                <span class="sr-only">Search users</span>
                                <input type="search" id="usersSearch" class="form-control" placeholder="Search name, username, email..." autocomplete="off">
                            </label>
                            <label class="users-filter-label" for="roleFilter">Role
                                <select id="roleFilter" class="form-control">
                                    <option value="">All roles</option>
                                    <option value="admin">Admin</option>
                                    <option value="manager">Manager</option>
                                    <option value="cashier">Cashier</option>
                                </select>
                            </label>
                            <label class="users-filter-label" for="statusFilter">Status
                                <select id="statusFilter" class="form-control">
                                    <option value="">All statuses</option>
                                    <option value="active" selected>Active users</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </label>
                        </div>
                    </div>
                    <div class="table-responsive users-table-wrap">
                        <table id="usersTable">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="8" class="users-table-empty">Loading users...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Modal -->
    <div id="userModal" class="modal users-modal" aria-hidden="true">
        <div class="modal-content users-dialog" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="userModalDescription" tabindex="-1">
            <div class="users-dialog-header">
                <div class="users-title-icon"><i class="fas fa-user-plus"></i></div>
                <div class="users-title-copy">
                    <p class="users-dialog-eyebrow">Staff access</p>
                    <h2 id="modalTitle">Add User</h2>
                    <p id="userModalDescription">Create an account for a team member.</p>
                </div>
                <button type="button" class="close users-close" aria-label="Close user form"><i class="fas fa-times"></i></button>
            </div>
            <form id="userForm" class="users-form">
                <div class="users-form-body">
                    <input type="hidden" id="userId">

                    <section class="users-form-section" aria-labelledby="userAccountTitle">
                        <div class="users-section-heading">
                            <span class="users-step">01</span>
                            <div><h3 id="userAccountTitle">Account details</h3><p>Set the staff member’s login and contact information.</p></div>
                        </div>
                        <div class="users-fields-grid">
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="username">Username <span>Required</span></label>
                                <div class="users-input-wrap"><i class="fas fa-at" aria-hidden="true"></i><input type="text" id="username" class="form-control" autocomplete="username" placeholder="e.g. jane.cashier" required></div>
                            </div>
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="fullName">Full name <span>Required</span></label>
                                <div class="users-input-wrap"><i class="fas fa-user" aria-hidden="true"></i><input type="text" id="fullName" class="form-control" autocomplete="name" placeholder="Staff member’s name" required></div>
                            </div>
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="email">Email <span class="users-optional">Optional</span></label>
                                <div class="users-input-wrap"><i class="fas fa-envelope" aria-hidden="true"></i><input type="email" id="email" class="form-control" autocomplete="email" placeholder="name@example.com"></div>
                            </div>
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="phone">Phone <span class="users-optional">Optional</span></label>
                                <div class="users-input-wrap"><i class="fas fa-phone" aria-hidden="true"></i><input type="tel" id="phone" class="form-control" autocomplete="tel" placeholder="07... or +254..."></div>
                            </div>
                        </div>
                    </section>

                    <section class="users-form-section" aria-labelledby="userAccessTitle">
                        <div class="users-section-heading">
                            <span class="users-step">02</span>
                            <div><h3 id="userAccessTitle">Access & security</h3><p>Choose a role, then customize the sections this staff member can open.</p></div>
                        </div>
                        <div class="users-fields-grid">
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="password">Password <span id="passwordRequiredLabel">Required</span></label>
                                <div class="users-password-wrap">
                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                    <input type="password" id="password" class="form-control" autocomplete="new-password" placeholder="Create a secure password" required>
                                    <button type="button" id="togglePasswordVisibility" class="users-password-toggle" aria-label="Show password" aria-pressed="false"><i class="fas fa-eye"></i></button>
                                </div>
                                <small id="passwordHint" class="users-field-hint">Use a strong password for this account.</small>
                            </div>
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="role">Role <span>Required</span></label>
                                <div class="users-input-wrap"><i class="fas fa-user-shield" aria-hidden="true"></i>
                                    <select id="role" class="form-control" required>
                                        <option value="cashier">Cashier</option>
                                        <option value="manager">Manager</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <small class="users-field-hint">The role supplies default access. You can customize section access for cashiers and managers below.</small>
                            </div>
                            <div class="form-group users-form-group">
                                <label class="users-field-label" for="status">Account status</label>
                                <div class="users-input-wrap"><i class="fas fa-toggle-on" aria-hidden="true"></i>
                                    <select id="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <small class="users-field-hint">Inactive users cannot sign in.</small>
                            </div>
                        </div>
                        <div class="users-permissions-panel" id="permissionsPanel">
                            <label class="users-custom-permissions" for="customPermissions">
                                <input type="checkbox" id="customPermissions">
                                <span><strong>Customize access for this user</strong><small>When enabled, only the checked sections are available to this account.</small></span>
                            </label>
                            <p class="users-admin-permissions-note" id="adminPermissionsNote" hidden>Administrators always have full access to every section.</p>
                            <div class="users-permission-grid" id="permissionGrid" hidden>
                                <?php foreach (security_permission_catalog() as $permission => $label): ?>
                                    <label class="users-permission-option">
                                        <input type="checkbox" class="user-permission" value="<?= htmlspecialchars($permission, ENT_QUOTES, 'UTF-8') ?>">
                                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                <?php endforeach; ?>
                                <p class="users-permission-hint">Point of Sale automatically includes product and customer lookup.</p>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="users-form-actions">
                    <button type="button" class="btn users-cancel-button" id="cancelUser">Cancel</button>
                    <button type="submit" class="btn btn-primary users-save-button" id="saveUserBtn"><i class="fas fa-check"></i> Save User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261021-1"></script>
    <script>window.rolePermissionDefaults = <?= json_encode(security_role_permissions(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="assets/js/users.js?v=20261020-3"></script>
</body>
</html>
