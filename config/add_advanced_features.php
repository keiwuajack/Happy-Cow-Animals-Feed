<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    die('Unauthorized access');
}

echo "<h2>Adding Advanced Features to Vision Agrovet POS</h2>";
echo "<hr>";

// 1. Add stock_adjustments table
$sql = "CREATE TABLE IF NOT EXISTS stock_adjustments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    adjustment_type ENUM('add', 'remove', 'damage', 'expired', 'transfer') NOT NULL,
    quantity INT NOT NULL,
    previous_stock INT NOT NULL,
    new_stock INT NOT NULL,
    reason TEXT,
    adjusted_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (adjusted_by) REFERENCES users(id)
)";

if (query($sql)) {
    echo "✓ Stock adjustments table created<br>";
} else {
    echo "✗ Error creating stock_adjustments table<br>";
}

// 2. Add payment_method column to sales table
$check = query("SHOW COLUMNS FROM sales LIKE 'payment_method'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE sales ADD COLUMN payment_method ENUM('cash', 'mpesa', 'card', 'split') DEFAULT 'cash' AFTER discount";
    if (query($sql)) {
        echo "✓ Payment method column added to sales<br>";
    } else {
        echo "✗ Error adding payment_method column<br>";
    }
} else {
    echo "• Payment method column already exists<br>";
}

// 3. Add payment_details column for split payments
$check = query("SHOW COLUMNS FROM sales LIKE 'payment_details'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE sales ADD COLUMN payment_details JSON NULL AFTER payment_method";
    if (query($sql)) {
        echo "✓ Payment details column added to sales<br>";
    } else {
        echo "✗ Error adding payment_details column<br>";
    }
} else {
    echo "• Payment details column already exists<br>";
}

// 4. Add loyalty points to customers table
$check = query("SHOW COLUMNS FROM customers LIKE 'loyalty_points'");
if ($check->num_rows == 0) {
    $sql = "ALTER TABLE customers ADD COLUMN loyalty_points INT DEFAULT 0 AFTER credit_limit";
    if (query($sql)) {
        echo "✓ Loyalty points column added to customers<br>";
    } else {
        echo "✗ Error adding loyalty_points column<br>";
    }
} else {
    echo "• Loyalty points column already exists<br>";
}

// 5. Create expenses table
$sql = "CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category ENUM('rent', 'utilities', 'salaries', 'transport', 'supplies', 'maintenance', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'mpesa', 'card', 'bank') DEFAULT 'cash',
    receipt_number VARCHAR(50),
    added_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id)
)";

if (query($sql)) {
    echo "✓ Expenses table created<br>";
} else {
    echo "✗ Error creating expenses table<br>";
}

// Bring older expense tables up to the current API contract.
$expenseColumns = [
    'expense_date' => "ALTER TABLE expenses ADD COLUMN expense_date DATE NULL AFTER description",
    'receipt_number' => "ALTER TABLE expenses ADD COLUMN receipt_number VARCHAR(50) NULL AFTER payment_method",
    'added_by' => "ALTER TABLE expenses ADD COLUMN added_by INT NULL AFTER receipt_number"
];
foreach ($expenseColumns as $column => $alterSql) {
    $columnCheck = query("SHOW COLUMNS FROM expenses LIKE '$column'");
    if ($columnCheck && $columnCheck->num_rows === 0) {
        query($alterSql);
    }
}
query("UPDATE expenses SET expense_date = date WHERE expense_date IS NULL");
query("UPDATE expenses SET added_by = (SELECT id FROM users ORDER BY id LIMIT 1) WHERE added_by IS NULL");

// 6. Create activity_log table
$sql = "CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if (query($sql)) {
    echo "✓ Activity log table created<br>";
} else {
    echo "✗ Error creating activity_log table<br>";
}

// 7. Create customer_points_history table
$sql = "CREATE TABLE IF NOT EXISTS customer_points_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    points INT NOT NULL,
    type ENUM('earned', 'redeemed', 'expired') NOT NULL,
    sale_id INT NULL,
    description TEXT,
    balance_after INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
)";

if (query($sql)) {
    echo "✓ Customer points history table created<br>";
} else {
    echo "✗ Error creating customer_points_history table<br>";
}

// 8. Create loyalty_settings table
$sql = "CREATE TABLE IF NOT EXISTS loyalty_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    points_per_amount INT DEFAULT 100 COMMENT 'Amount to spend for 1 point',
    points_value INT DEFAULT 100 COMMENT 'Points needed for redemption',
    money_value INT DEFAULT 10 COMMENT 'Money value of points',
    points_expiry_months INT DEFAULT 12 COMMENT 'Months before points expire',
    min_redemption INT DEFAULT 100 COMMENT 'Minimum points to redeem',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (query($sql)) {
    echo "✓ Loyalty settings table created<br>";
    
    // Insert default settings
    $check = query("SELECT id FROM loyalty_settings LIMIT 1");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO loyalty_settings (points_per_amount, points_value, money_value, points_expiry_months, min_redemption) 
                VALUES (100, 100, 10, 12, 100)";
        query($sql);
        echo "✓ Default loyalty settings inserted<br>";
    }
} else {
    echo "✗ Error creating loyalty_settings table<br>";
}

echo "<hr>";
echo "<h3>✅ Advanced features database setup complete!</h3>";
echo "<br><a href='../index.php'>← Back to Dashboard</a>";
?>
