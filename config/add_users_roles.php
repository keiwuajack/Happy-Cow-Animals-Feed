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

// Add sample staff users with different roles
$staff = [
    ['username' => 'cashier1', 'password' => 'cashier123', 'full_name' => 'James Kipchoge', 'email' => 'james@agrovet.com', 'role' => 'cashier'],
    ['username' => 'cashier2', 'password' => 'cashier123', 'full_name' => 'Sarah Kiplagat', 'email' => 'sarah@agrovet.com', 'role' => 'cashier'],
    ['username' => 'manager', 'password' => 'manager123', 'full_name' => 'David Koech', 'email' => 'david@agrovet.com', 'role' => 'manager'],
];

echo "<h3>Adding Staff Users</h3>";
$staff_added = 0;

foreach ($staff as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Check if user already exists
    $check_sql = "SELECT id FROM users WHERE username = '" . $conn->real_escape_string($user['username']) . "'";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows == 0) {
        $sql = "INSERT INTO users (username, password, full_name, email, role, status) 
                VALUES ('" . $conn->real_escape_string($user['username']) . "', '$hashed_password', '" . $conn->real_escape_string($user['full_name']) . "', '" . $conn->real_escape_string($user['email']) . "', '" . $user['role'] . "', 'active')";
        
        if ($conn->query($sql) === TRUE) {
            $staff_added++;
            echo "✓ Added " . strtoupper($user['role']) . ": " . $user['full_name'] . " (Username: " . $user['username'] . ")<br>";
        }
    } else {
        echo "! User already exists: " . $user['username'] . "<br>";
    }
}

// Create permissions table if not exists
$result = $conn->query("SHOW TABLES LIKE 'permissions'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE IF NOT EXISTS permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role VARCHAR(50) NOT NULL,
        permission VARCHAR(100) NOT NULL,
        status ENUM('allowed', 'denied') DEFAULT 'allowed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_role_permission (role, permission)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✓ Permissions table created<br>";
    }
}

// Add role-based permissions
$permissions = [
    // Admin permissions
    ['role' => 'admin', 'permission' => 'view_dashboard'],
    ['role' => 'admin', 'permission' => 'manage_users'],
    ['role' => 'admin', 'permission' => 'manage_products'],
    ['role' => 'admin', 'permission' => 'manage_categories'],
    ['role' => 'admin', 'permission' => 'manage_customers'],
    ['role' => 'admin', 'permission' => 'view_reports'],
    ['role' => 'admin', 'permission' => 'manage_settings'],
    
    // Manager permissions
    ['role' => 'manager', 'permission' => 'view_dashboard'],
    ['role' => 'manager', 'permission' => 'process_sales'],
    ['role' => 'manager', 'permission' => 'manage_products'],
    ['role' => 'manager', 'permission' => 'manage_customers'],
    ['role' => 'manager', 'permission' => 'view_reports'],
    ['role' => 'manager', 'permission' => 'manage_credit_sales'],
    
    // Cashier permissions
    ['role' => 'cashier', 'permission' => 'process_sales'],
    ['role' => 'cashier', 'permission' => 'view_products'],
    ['role' => 'cashier', 'permission' => 'view_customer_info'],
];

echo "<h3>Configuring Role Permissions</h3>";
$perms_added = 0;

foreach ($permissions as $perm) {
    $sql = "INSERT IGNORE INTO permissions (role, permission) 
            VALUES ('" . $conn->real_escape_string($perm['role']) . "', '" . $conn->real_escape_string($perm['permission']) . "')";
    
    if ($conn->query($sql) === TRUE) {
        if ($conn->affected_rows > 0) {
            $perms_added++;
            echo "✓ " . strtoupper($perm['role']) . " -> " . $perm['permission'] . "<br>";
        }
    }
}

echo "<br><strong>Summary: $staff_added staff users added, $perms_added permissions configured</strong><br>";
echo "<br><h4>Login Credentials for Staff:</h4>";
echo "<ul>";
foreach ($staff as $user) {
    echo "<li><strong>" . strtoupper($user['role']) . ":</strong> " . $user['username'] . " / " . $user['password'] . "</li>";
}
echo "</ul>";

$conn->close();
?>
