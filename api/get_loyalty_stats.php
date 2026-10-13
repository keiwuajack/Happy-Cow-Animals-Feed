<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get redemption rate from settings
$settingsResult = $conn->query("SELECT money_value AS setting_value FROM loyalty_settings LIMIT 1");
$redemptionRate = 10; // Default
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    $redemptionRate = floatval($setting['setting_value']);
}

// Total customers enrolled
$enrolledResult = $conn->query("SELECT COUNT(*) as total FROM customers WHERE loyalty_points IS NOT NULL");
$totalEnrolled = $enrolledResult->fetch_assoc()['total'];

// Total points earned
$earnedResult = $conn->query("SELECT COALESCE(SUM(points_earned), 0) as total FROM loyalty_transactions");
$totalEarned = $earnedResult->fetch_assoc()['total'];

// Total points redeemed
$redeemedResult = $conn->query("SELECT COALESCE(SUM(points_redeemed), 0) as total FROM loyalty_transactions");
$totalRedeemed = $redeemedResult->fetch_assoc()['total'];

// Active points value (total current balances)
$activeResult = $conn->query("SELECT COALESCE(SUM(loyalty_points), 0) as total FROM customers");
$activePoints = $activeResult->fetch_assoc()['total'];
$pointsValue = $activePoints * $redemptionRate;

echo json_encode([
    'success' => true,
    'stats' => [
        'total_customers' => $totalEnrolled,
        'total_earned' => $totalEarned,
        'total_redeemed' => $totalRedeemed,
        'active_points' => $activePoints,
        'points_value' => $pointsValue
    ]
]);
?>
