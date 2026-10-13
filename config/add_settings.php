<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'agrovet_pos');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Check if settings table exists
$result = $conn->query("SHOW TABLES LIKE 'settings'");

if ($result->num_rows == 0) {
    // Create settings table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value LONGTEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Settings table created<br>";
    } else {
        echo "✗ Error creating settings table: " . $conn->error . "<br>";
    }
}

// Store settings to configure
$settings = [
    ['key' => 'store_name', 'value' => 'Vision Agrovet Limited'],
    ['key' => 'store_phone', 'value' => '+254-712-345-678'],
    ['key' => 'store_email', 'value' => 'info@visionagrovet.com'],
    ['key' => 'store_address', 'value' => '123 Agricultural Road, Eldoret, Kenya'],
    ['key' => 'store_city', 'value' => 'Eldoret'],
    ['key' => 'store_country', 'value' => 'Kenya'],
    ['key' => 'store_postal_code', 'value' => '30100'],
    ['key' => 'store_website', 'value' => 'www.visionagrovet.com'],
    ['key' => 'store_currency', 'value' => 'KES'],
    ['key' => 'currency_symbol', 'value' => 'KSh'],
    ['key' => 'default_tax_rate', 'value' => '16'],
    ['key' => 'receipt_header', 'value' => 'VISION AGROVET POS SYSTEM'],
    ['key' => 'receipt_footer', 'value' => 'Thank you for your business! Visit us again.'],
    ['key' => 'business_registration', 'value' => 'P.O Box 1234, Eldoret 30100'],
    ['key' => 'print_logo', 'value' => '1'],
    ['key' => 'auto_backup', 'value' => '1'],
    ['key' => 'backup_frequency', 'value' => 'daily'],
    ['key' => 'credit_payment_terms', 'value' => '30'],
    ['key' => 'loyalty_program_enabled', 'value' => '1'],
    ['key' => 'loyalty_points_multiplier', 'value' => '1'],
];

echo "<h3>Configuring Store Settings</h3>";
$added = 0;
$updated = 0;

foreach ($settings as $setting) {
    $check_sql = "SELECT id FROM settings WHERE setting_key = '" . $conn->real_escape_string($setting['key']) . "'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Update existing
        $sql = "UPDATE settings SET setting_value = '" . $conn->real_escape_string($setting['value']) . "' WHERE setting_key = '" . $conn->real_escape_string($setting['key']) . "'";
        if ($conn->query($sql) === TRUE) {
            $updated++;
            echo "✓ Updated: " . $setting['key'] . "<br>";
        }
    } else {
        // Insert new
        $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('" . $conn->real_escape_string($setting['key']) . "', '" . $conn->real_escape_string($setting['value']) . "')";
        if ($conn->query($sql) === TRUE) {
            $added++;
            echo "✓ Added: " . $setting['key'] . "<br>";
        }
    }
}

echo "<br><strong>Settings: $added added, $updated updated</strong><br>";
$conn->close();
?>
