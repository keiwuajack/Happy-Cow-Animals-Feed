<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if settings exist
$sql = "SELECT * FROM loyalty_settings LIMIT 1";
$result = query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $settings = [
        'points_rate' => round(100 / max(1, (int) $row['points_per_amount']), 2),
        'redemption_rate' => (float) $row['money_value'],
        'expiry_days' => (int) $row['expiry_days'],
        'welcome_bonus' => (int) $row['welcome_bonus'],
        'auto_enroll' => (int) $row['auto_enroll'],
        'enabled' => (int) $row['enabled'],
    ];
    echo json_encode([
        'success' => true,
        'settings' => $settings
    ]);
} else {
    // Return default settings
    echo json_encode([
        'success' => true,
        'settings' => [
            'points_rate' => 1, 'redemption_rate' => 1, 'expiry_days' => 365, 'welcome_bonus' => 0, 'auto_enroll' => 1, 'enabled' => 1
        ]
    ]);
}
?>
