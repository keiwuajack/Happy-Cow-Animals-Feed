<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    die('Unauthorized access');
}

// Check if expiry_date column already exists
$check = query("SHOW COLUMNS FROM products LIKE 'expiry_date'");
if ($check->num_rows > 0) {
    echo "Column 'expiry_date' already exists!<br>";
} else {
    // Add expiry_date column to products table
    $sql = "ALTER TABLE products ADD COLUMN expiry_date DATE NULL AFTER description";
    
    if (query($sql)) {
        echo "✓ Successfully added expiry_date column to products table<br>";
    } else {
        echo "✗ Error adding expiry_date column<br>";
    }
}

echo "<br><a href='../products.php'>← Back to Products</a>";
?>
