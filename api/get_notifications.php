<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$notifications = [];

// Low stock notifications
$sql = "SELECT p.id, p.name, p.quantity, p.reorder_level, p.unit, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.quantity <= p.reorder_level AND p.status = 'active'
        ORDER BY p.quantity ASC";
$result = query($sql);

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => 'stock_' . $row['id'],
        'type' => 'low_stock',
        'severity' => $row['quantity'] == 0 ? 'critical' : 'warning',
        'title' => 'Low Stock Alert',
        'message' => $row['name'] . ' is running low (' . $row['quantity'] . ' ' . $row['unit'] . ' remaining)',
        'product_id' => $row['id'],
        'product_name' => $row['name'],
        'quantity' => $row['quantity'],
        'unit' => $row['unit'],
        'reorder_level' => $row['reorder_level'],
        'category' => $row['category_name'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Out of stock notifications
$sql = "SELECT p.id, p.name, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.quantity = 0 AND p.status = 'active'";
$result = query($sql);

while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => 'outofstock_' . $row['id'],
        'type' => 'out_of_stock',
        'severity' => 'critical',
        'title' => 'Out of Stock',
        'message' => $row['name'] . ' is out of stock!',
        'product_id' => $row['id'],
        'product_name' => $row['name'],
        'category' => $row['category_name'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

// Check if products table has expiry_date column, if not we'll skip expiry notifications
$check_column = query("SHOW COLUMNS FROM products LIKE 'expiry_date'");
$has_expiry = $check_column->num_rows > 0;

if ($has_expiry) {
    // Expired products
    $sql = "SELECT p.id, p.name, p.expiry_date, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.expiry_date IS NOT NULL 
            AND p.expiry_date < CURDATE() 
            AND p.status = 'active'
            AND p.quantity > 0";
    $result = query($sql);

    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => 'expired_' . $row['id'],
            'type' => 'expired',
            'severity' => 'critical',
            'title' => 'Product Expired',
            'message' => $row['name'] . ' has expired on ' . date('M d, Y', strtotime($row['expiry_date'])),
            'product_id' => $row['id'],
            'product_name' => $row['name'],
            'expiry_date' => $row['expiry_date'],
            'category' => $row['category_name'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    // Expiring soon (within 30 days)
    $sql = "SELECT p.id, p.name, p.expiry_date, DATEDIFF(p.expiry_date, CURDATE()) as days_left, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.expiry_date IS NOT NULL 
            AND p.expiry_date >= CURDATE() 
            AND p.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND p.status = 'active'
            AND p.quantity > 0
            ORDER BY p.expiry_date ASC";
    $result = query($sql);

    while ($row = $result->fetch_assoc()) {
        $days_left = $row['days_left'];
        $severity = $days_left <= 7 ? 'critical' : ($days_left <= 15 ? 'warning' : 'info');
        
        $notifications[] = [
            'id' => 'expiring_' . $row['id'],
            'type' => 'expiring_soon',
            'severity' => $severity,
            'title' => 'Expiring Soon',
            'message' => $row['name'] . ' expires in ' . $days_left . ' day' . ($days_left != 1 ? 's' : ''),
            'product_id' => $row['id'],
            'product_name' => $row['name'],
            'expiry_date' => $row['expiry_date'],
            'days_left' => $days_left,
            'category' => $row['category_name'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Count by severity
$critical_count = 0;
$warning_count = 0;
$info_count = 0;

foreach ($notifications as $notif) {
    if ($notif['severity'] === 'critical') $critical_count++;
    elseif ($notif['severity'] === 'warning') $warning_count++;
    else $info_count++;
}

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'total' => count($notifications),
    'counts' => [
        'critical' => $critical_count,
        'warning' => $warning_count,
        'info' => $info_count
    ],
    'has_expiry_tracking' => $has_expiry
]);
?>
