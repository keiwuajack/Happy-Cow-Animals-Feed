<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

/**
 * Database migration for new competitive features
 * Run this to add M-Pesa, WhatsApp, Email, and enhanced features
 */

require_once 'database.php';

echo "<h2>Adding Competitive Features to Vision Agrovet POS</h2>";
echo "<p>Starting migration...</p>";

// M-Pesa Integration Tables
$mpesaSettings = "CREATE TABLE IF NOT EXISTS mpesa_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumer_key VARCHAR(255) NOT NULL,
    consumer_secret VARCHAR(255) NOT NULL,
    shortcode VARCHAR(20) NOT NULL,
    passkey TEXT NOT NULL,
    callback_url VARCHAR(255) NOT NULL,
    environment ENUM('sandbox', 'live') DEFAULT 'sandbox',
    enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$mpesaTransactions = "CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    checkout_request_id VARCHAR(100) UNIQUE NOT NULL,
    merchant_request_id VARCHAR(100),
    phone_number VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    account_reference VARCHAR(100),
    mpesa_receipt_number VARCHAR(50),
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_checkout_request (checkout_request_id),
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
)";

echo "Creating M-Pesa tables...<br>";
if ($conn->query($mpesaSettings) && $conn->query($mpesaTransactions)) {
    echo "✅ M-Pesa tables created successfully<br>";
} else {
    echo "❌ Error creating M-Pesa tables: " . $conn->error . "<br>";
}

// WhatsApp Integration Tables
$whatsappSettings = "CREATE TABLE IF NOT EXISTS whatsapp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider ENUM('twilio', 'cloud_api', 'africastalking') DEFAULT 'cloud_api',
    api_key VARCHAR(255) NOT NULL,
    api_secret VARCHAR(255),
    phone_number_id VARCHAR(100),
    from_number VARCHAR(20),
    enabled TINYINT(1) DEFAULT 1,
    send_receipts TINYINT(1) DEFAULT 1,
    send_alerts TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$whatsappLog = "CREATE TABLE IF NOT EXISTS whatsapp_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed', 'delivered', 'read') DEFAULT 'sent',
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_phone (phone_number),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
)";

echo "Creating WhatsApp tables...<br>";
if ($conn->query($whatsappSettings) && $conn->query($whatsappLog)) {
    echo "✅ WhatsApp tables created successfully<br>";
} else {
    echo "❌ Error creating WhatsApp tables: " . $conn->error . "<br>";
}

// Email Service Tables
$emailSettings = "CREATE TABLE IF NOT EXISTS email_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT DEFAULT 587,
    smtp_username VARCHAR(255) NOT NULL,
    smtp_password VARCHAR(255) NOT NULL,
    smtp_encryption ENUM('tls', 'ssl') DEFAULT 'tls',
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NOT NULL,
    enabled TINYINT(1) DEFAULT 1,
    send_receipts TINYINT(1) DEFAULT 1,
    send_quotations TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

$emailLog = "CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    reference VARCHAR(100),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
)";

echo "Creating Email service tables...<br>";
if ($conn->query($emailSettings) && $conn->query($emailLog)) {
    echo "✅ Email service tables created successfully<br>";
} else {
    echo "❌ Error creating Email tables: " . $conn->error . "<br>";
}

// Enhanced customers table
$alterCustomers = "ALTER TABLE customers 
    ADD COLUMN IF NOT EXISTS email VARCHAR(255),
    ADD COLUMN IF NOT EXISTS whatsapp_number VARCHAR(20),
    ADD COLUMN IF NOT EXISTS receive_whatsapp TINYINT(1) DEFAULT 1,
    ADD COLUMN IF NOT EXISTS receive_email TINYINT(1) DEFAULT 1";

echo "Enhancing customers table...<br>";
if ($conn->query($alterCustomers)) {
    echo "✅ Customers table enhanced<br>";
} else {
    echo "⚠️ Customers table may already have these columns<br>";
}

// Enhanced sales table for payment methods
$alterSales = "ALTER TABLE sales 
    ADD COLUMN IF NOT EXISTS mpesa_transaction_id INT,
    ADD COLUMN IF NOT EXISTS mpesa_receipt_number VARCHAR(50),
    ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100),
    ADD FOREIGN KEY IF NOT EXISTS (mpesa_transaction_id) REFERENCES mpesa_transactions(id)";

echo "Enhancing sales table...<br>";
if ($conn->query($alterSales)) {
    echo "✅ Sales table enhanced<br>";
} else {
    echo "⚠️ Sales table may already have these columns<br>";
}

// Product Variants Table (for size/color variations)
$productVariants = "CREATE TABLE IF NOT EXISTS product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    variant_value VARCHAR(100) NOT NULL,
    sku VARCHAR(50) UNIQUE,
    price DECIMAL(10,2),
    stock INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_sku (sku)
)";

echo "Creating product variants table...<br>";
if ($conn->query($productVariants)) {
    echo "✅ Product variants table created<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Loyalty program enhancements
$loyaltyTransactions = "CREATE TABLE IF NOT EXISTS loyalty_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    sale_id INT,
    points_earned INT DEFAULT 0,
    points_redeemed INT DEFAULT 0,
    transaction_type ENUM('earned', 'redeemed', 'adjustment') NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_created (created_at)
)";

echo "Creating loyalty transactions table...<br>";
if ($conn->query($loyaltyTransactions)) {
    echo "✅ Loyalty transactions table created<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Store management (multi-branch)
$stores = "CREATE TABLE IF NOT EXISTS stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    manager_id INT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_code (code)
)";

echo "Creating stores table...<br>";
if ($conn->query($stores)) {
    echo "✅ Stores table created<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// Older installations used stores without a code column.
$storeCodeCheck = $conn->query("SHOW COLUMNS FROM stores LIKE 'code'");
if ($storeCodeCheck && $storeCodeCheck->num_rows === 0) {
    $conn->query("ALTER TABLE stores ADD COLUMN code VARCHAR(20) NULL AFTER name");
    $conn->query("UPDATE stores SET code = CONCAT('STORE-', id) WHERE code IS NULL OR code = ''");
}

// Insert default store using columns shared by old and new schemas.
$defaultStore = "INSERT IGNORE INTO stores (id, name, is_active) VALUES (1, 'Main Store', 1)";
$conn->query($defaultStore);

// Notification preferences
$notificationPreferences = "CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notify_low_stock TINYINT(1) DEFAULT 1,
    notify_sales TINYINT(1) DEFAULT 1,
    notify_returns TINYINT(1) DEFAULT 1,
    notify_expenses TINYINT(1) DEFAULT 0,
    notification_method ENUM('email', 'sms', 'whatsapp', 'all') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
)";

echo "Creating notification preferences table...<br>";
if ($conn->query($notificationPreferences)) {
    echo "✅ Notification preferences table created<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

// System settings for branding
$systemSettings = "CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50),
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

echo "Creating system settings table...<br>";
if ($conn->query($systemSettings)) {
    echo "✅ System settings table created<br>";
    
    // Insert default settings
    $defaultSettings = [
        "('business_name', 'Vision Agrovet POS', 'text', 'Business name for receipts and emails')",
        "('business_address', '', 'text', 'Business physical address')",
        "('business_phone', '', 'text', 'Business contact phone')",
        "('business_email', '', 'text', 'Business contact email')",
        "('receipt_footer', 'Thank you for your business!', 'text', 'Footer text on receipts')",
        "('currency_symbol', 'KSh', 'text', 'Currency symbol')",
        "('tax_rate', '16', 'number', 'Default VAT rate percentage')",
        "('loyalty_points_rate', '1', 'number', 'Points earned per 100 KSh spent')",
        "('loyalty_redemption_rate', '10', 'number', 'KSh value per loyalty point')"
    ];
    
    foreach ($defaultSettings as $setting) {
        $conn->query("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES $setting");
    }
    echo "✅ Default system settings inserted<br>";
} else {
    echo "❌ Error: " . $conn->error . "<br>";
}

echo "<br><h3>🎉 Migration Complete!</h3>";
echo "<p><strong>New Features Added:</strong></p>";
echo "<ul>";
echo "<li>✅ M-Pesa STK Push Integration (Daraja API)</li>";
echo "<li>✅ WhatsApp Business Messaging</li>";
echo "<li>✅ Email Receipt System</li>";
echo "<li>✅ Product Variants (Size/Color)</li>";
echo "<li>✅ Enhanced Loyalty Program</li>";
echo "<li>✅ Multi-Store Support</li>";
echo "<li>✅ Notification Preferences</li>";
echo "<li>✅ System Settings & Branding</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Configure M-Pesa settings with your Daraja API credentials</li>";
echo "<li>Set up WhatsApp provider (Cloud API recommended)</li>";
echo "<li>Configure SMTP settings for email</li>";
echo "<li>Customize system settings and branding</li>";
echo "</ol>";

echo "<p><a href='../index.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#667eea; color:white; text-decoration:none; border-radius:5px;'>Go to Dashboard</a></p>";

$conn->close();
?>
