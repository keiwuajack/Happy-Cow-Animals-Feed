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
    <title>Expenses - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <h1><i class="fas fa-money-bill-wave"></i> Expenses Tracking</h1>
                    <button class="btn btn-primary" id="addExpenseBtn">
                        <i class="fas fa-plus"></i> Add Expense
                    </button>
                </div>
                
                <!-- Summary Cards -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 30px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #fee;">
                            <i class="fas fa-calendar-day" style="color: var(--danger-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="todayExpenses">KSh 0.00</h3>
                            <p>Today's Expenses</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ffefd5;">
                            <i class="fas fa-calendar-week" style="color: var(--warning-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="weekExpenses">KSh 0.00</h3>
                            <p>This Week</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e3f2fd;">
                            <i class="fas fa-calendar-alt" style="color: var(--info-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="monthExpenses">KSh 0.00</h3>
                            <p>This Month</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f0f0f0;">
                            <i class="fas fa-chart-line" style="color: var(--dark-color);"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="totalExpenses">KSh 0.00</h3>
                            <p>Total Expenses</p>
                        </div>
                    </div>
                </div>
                
                <div class="content-box">
                    <div class="table-controls">
                        <input type="text" id="searchExpense" placeholder="Search expenses..." class="search-box">
                        <select id="filterCategory" class="filter-select">
                            <option value="">All Categories</option>
                            <option value="rent">Rent</option>
                            <option value="utilities">Utilities</option>
                            <option value="salaries">Salaries</option>
                            <option value="transport">Transport</option>
                            <option value="supplies">Supplies</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="other">Other</option>
                        </select>
                        <input type="date" id="startDate" class="filter-select">
                        <input type="date" id="endDate" class="filter-select">
                    </div>
                    
                    <div class="table-responsive">
                        <table id="expensesTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Receipt #</th>
                                    <th>Added By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Expense Modal -->
    <div class="modal" id="expenseModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add Expense</h2>
                <span class="close">&times;</span>
            </div>
            <form id="expenseForm">
                <input type="hidden" id="expenseId">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category *</label>
                            <select id="category" required>
                                <option value="">Select Category</option>
                                <option value="rent">Rent</option>
                                <option value="utilities">Utilities (Water, Electricity, Internet)</option>
                                <option value="salaries">Salaries & Wages</option>
                                <option value="transport">Transport</option>
                                <option value="supplies">Supplies & Equipment</option>
                                <option value="maintenance">Maintenance & Repairs</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Amount *</label>
                            <input type="number" id="amount" step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description *</label>
                        <textarea id="description" rows="3" required placeholder="Enter expense description..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Expense Date *</label>
                            <input type="date" id="expenseDate" required>
                        </div>
                        <div class="form-group">
                            <label>Payment Method *</label>
                            <select id="paymentMethod" required>
                                <option value="cash">Cash</option>
                                <option value="mpesa">M-Pesa</option>
                                <option value="card">Card</option>
                                <option value="bank">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Receipt Number (Optional)</label>
                        <input type="text" id="receiptNumber" placeholder="e.g., RCP-12345">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="cancelExpense">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
    <script src="assets/js/expenses.js?v=20261011-2"></script>
</body>
</html>
