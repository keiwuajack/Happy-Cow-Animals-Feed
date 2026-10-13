<?php
// Database configuration. Set POS_DB_* environment variables in production.
define('DB_HOST', getenv('POS_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('POS_DB_USER') ?: 'root');
define('DB_PASS', getenv('POS_DB_PASS') ?: '');
define('DB_NAME', getenv('POS_DB_NAME') ?: 'agrovet_pos');

require_once __DIR__ . '/../includes/security.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Function to sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to execute query and return result
function query($sql) {
    global $conn;
    return $conn->query($sql);
}

if (isset($_SESSION['user_id'])) {
    security_require_same_origin();
}
?>
