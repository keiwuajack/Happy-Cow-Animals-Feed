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

// Configure loyalty settings for all active customers
echo "<h3>Configuring Loyalty Program for Customers</h3>";

$customers = $conn->query("SELECT id FROM customers WHERE status = 'active' LIMIT 10");
$settings_added = 0;

while ($customer = $customers->fetch_assoc()) {
    $customer_id = $customer['id'];
    
    // Check if loyalty settings already exist for this customer
    $check_sql = "SELECT id FROM loyalty_settings WHERE customer_id = $customer_id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows == 0) {
        // Insert loyalty settings for customer
        $sql = "INSERT INTO loyalty_settings (customer_id, points_balance, tier_level, join_date, status) 
                VALUES ($customer_id, 0, 'bronze', NOW(), 'active')";
        
        if ($conn->query($sql) === TRUE) {
            $settings_added++;
            echo "✓ Loyalty program initialized for Customer ID: $customer_id<br>";
        }
    } else {
        echo "! Loyalty settings already exist for Customer ID: $customer_id<br>";
    }
}

// Add loyalty points for those with sales
echo "<h3>Adding Loyalty Points to Active Customers</h3>";

$sql = "SELECT DISTINCT s.customer_id, COUNT(s.id) as purchase_count, SUM(s.total) as total_spent 
        FROM sales s 
        WHERE s.customer_id IS NOT NULL 
        GROUP BY s.customer_id";

$result = $conn->query($sql);
$points_added = 0;

while ($row = $result->fetch_assoc()) {
    $customer_id = $row['customer_id'];
    $total_spent = $row['total_spent'];
    
    // Calculate points: 1 point per 100 KSh spent
    $points = floor($total_spent / 100);
    
    // Update loyalty balance
    $update_sql = "UPDATE loyalty_settings SET points_balance = points_balance + $points WHERE customer_id = $customer_id";
    
    if ($conn->query($update_sql) === TRUE) {
        $points_added += $points;
        echo "✓ Customer ID $customer_id - Added $points loyalty points (Total Spent: KSh " . number_format($total_spent, 2) . ")<br>";
    }
}

// Set tier levels based on points
echo "<h3>Assigning Loyalty Tiers</h3>";

$tier_updates = [
    ['tier' => 'gold', 'min_points' => 5000],
    ['tier' => 'silver', 'min_points' => 2000],
    ['tier' => 'bronze', 'min_points' => 0],
];

foreach ($tier_updates as $tier) {
    if ($tier['tier'] !== 'bronze') {
        $sql = "UPDATE loyalty_settings 
                SET tier_level = '" . $tier['tier'] . "' 
                WHERE points_balance >= " . $tier['min_points'] . " 
                AND status = 'active'";
        
        if ($conn->query($sql) === TRUE) {
            $affected = $conn->affected_rows;
            if ($affected > 0) {
                echo "✓ " . $affected . " customers promoted to " . strtoupper($tier['tier']) . " tier<br>";
            }
        }
    }
}

echo "<br><strong>Summary: $settings_added loyalty profiles created, $points_added total points awarded</strong><br>";
$conn->close();
?>
