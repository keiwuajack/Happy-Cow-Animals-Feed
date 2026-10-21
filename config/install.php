<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('Not found');
}

// Database installation script
define('DB_HOST', getenv('POS_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('POS_DB_USER') ?: 'root');
define('DB_PASS', getenv('POS_DB_PASS') ?: '');
define('DB_NAME', getenv('POS_DB_NAME') ?: 'agrovet_pos');

// Create connection without database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select database
$conn->select_db(DB_NAME);
$conn->set_charset('utf8mb4');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
    permissions_json TEXT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Categories table created successfully<br>";
} else {
    echo "Error creating categories table: " . $conn->error . "<br>";
}

// Create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    sku VARCHAR(50) UNIQUE,
    barcode VARCHAR(50) UNIQUE,
    description TEXT,
    expiry_date DATE NULL,
    image VARCHAR(255) NULL,
    unit VARCHAR(50) DEFAULT 'pcs',
    cost_price DECIMAL(10,2) NOT NULL,
    selling_price DECIMAL(10,2) NOT NULL,
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    supplier_id INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Products table created successfully<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Create customers table
$sql = "CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    farm_size VARCHAR(50),
    customer_type ENUM('farmer', 'dealer', 'individual') DEFAULT 'individual',
    credit_limit DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Customers table created successfully<br>";
} else {
    echo "Error creating customers table: " . $conn->error . "<br>";
}

// Create suppliers table
$sql = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Suppliers table created successfully<br>";
} else {
    echo "Error creating suppliers table: " . $conn->error . "<br>";
}

// Create sales table
$sql = "CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT,
    user_id INT,
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) DEFAULT 0,
    payment_method ENUM('cash', 'mpesa', 'card', 'credit') DEFAULT 'cash',
    payment_status ENUM('paid', 'partial', 'unpaid') DEFAULT 'paid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Sales table created successfully<br>";
} else {
    echo "Error creating sales table: " . $conn->error . "<br>";
}

// Create sale_items table
$sql = "CREATE TABLE IF NOT EXISTS sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(200) NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Sale items table created successfully<br>";
} else {
    echo "Error creating sale items table: " . $conn->error . "<br>";
}

// Insert the initial admin user only during a controlled CLI installation.
$initialPassword = bin2hex(random_bytes(12));
$password = password_hash($initialPassword, PASSWORD_DEFAULT);

// Check if admin user already exists
$checkUser = $conn->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
if ($checkUser && $checkUser->num_rows > 0) {
    $row = $checkUser->fetch_assoc();
    if ($row['count'] == 0) {
        $sql = "INSERT INTO users (username, password, full_name, email, role) 
                VALUES ('admin', '$password', 'System Administrator', '', 'admin')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Default admin user created successfully<br>";
            echo "Initial admin user created. One-time password: {$initialPassword}<br>";
        } else {
            echo "Error creating admin user: " . $conn->error . "<br>";
        }
    } else {
        echo "Admin user already exists<br>";
        echo "Admin user already exists.<br>";
    }
} else {
    // Table might not be ready, wait a moment and try again
    sleep(1);
    $sql = "INSERT INTO users (username, password, full_name, email, role) 
            VALUES ('admin', '$password', 'System Administrator', '', 'admin')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Default admin user created successfully<br>";
        echo "Initial admin user created. Change its password before use.<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
}

// Insert sample categories
$categories = [
    ['Seeds & Planting', 'All types of seeds and planting materials'],
    ['Fertilizers', 'Organic and inorganic fertilizers'],
    ['Pesticides', 'Insecticides, herbicides, and fungicides'],
    ['Animal Feed', 'Feeds for livestock and poultry'],
    ['Veterinary Supplies', 'Medicines and veterinary products'],
    ['Farm Tools', 'Hand tools and farm equipment'],
    ['Irrigation', 'Irrigation systems and accessories']
];

foreach ($categories as $cat) {
    $sql = "INSERT INTO categories (name, description) VALUES ('{$cat[0]}', '{$cat[1]}')
            ON DUPLICATE KEY UPDATE name=name";
    $conn->query($sql);
}
echo "Sample categories inserted successfully<br>";

echo "<br><strong>Installation completed successfully!</strong><br>";
echo "<a href='../login.php'>Go to Login Page</a>";

$conn->close();
?>
