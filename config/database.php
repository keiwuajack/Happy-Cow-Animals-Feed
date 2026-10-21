<?php
// Database configuration. Set POS_DB_* environment variables in production.
define('DB_HOST', getenv('POS_DB_HOST') ?: 'localhost');
define('DB_USER', getenv('POS_DB_USER') ?: 'root');
define('DB_PASS', getenv('POS_DB_PASS') ?: '');
define('DB_NAME', getenv('POS_DB_NAME') ?: 'agrovet_pos');

require_once __DIR__ . '/../includes/security.php';

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Refresh the user's role on each request so role changes and deactivations
// take effect immediately instead of waiting for the next sign-in.
if (isset($_SESSION['user_id'])) {
    $roleStmt = $conn->prepare('SELECT role, status, permissions_json FROM users WHERE id = ? LIMIT 1');
    if (!$roleStmt) {
        http_response_code(503);
        exit('Access check is temporarily unavailable');
    }

    $sessionUserId = (int) $_SESSION['user_id'];
    $roleStmt->bind_param('i', $sessionUserId);
    if (!$roleStmt->execute()) {
        $roleStmt->close();
        http_response_code(503);
        exit('Access check is temporarily unavailable');
    }
    $currentUser = $roleStmt->get_result()->fetch_assoc();
    $roleStmt->close();

    if (!$currentUser || strtolower((string) $currentUser['status']) !== 'active') {
        $_SESSION = [];
        session_destroy();
    } else {
        $_SESSION['role'] = $currentUser['role'];
        $storedPermissions = json_decode((string) ($currentUser['permissions_json'] ?? ''), true);
        $knownPermissions = security_permission_catalog();
        $_SESSION['permissions_override'] = is_array($storedPermissions)
            ? array_values(array_intersect($storedPermissions, array_keys($knownPermissions)))
            : null;
    }
}

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

security_enforce_route_permission();
?>
