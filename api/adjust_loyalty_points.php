<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$customerId = $data['customer_id'];
$type = $data['type']; // 'add' or 'subtract'
$points = intval($data['points']);
$reason = $data['reason'];

if (!$customerId || !$points || $points < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Get current balance
$stmt = $conn->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'Customer not found']);
    exit();
}

$currentBalance = $customer['loyalty_points'] ?? 0;

// Calculate new balance
if ($type === 'add') {
    $newBalance = $currentBalance + $points;
    $earnedPoints = $points;
    $redeemedPoints = 0;
} else {
    if ($currentBalance < $points) {
        echo json_encode(['success' => false, 'message' => 'Insufficient points balance']);
        exit();
    }
    $newBalance = $currentBalance - $points;
    $earnedPoints = 0;
    $redeemedPoints = $points;
}

// Update customer balance
$stmt = $conn->prepare("UPDATE customers SET loyalty_points = ? WHERE id = ?");
$stmt->bind_param("ii", $newBalance, $customerId);

if ($stmt->execute()) {
    // Log the transaction
    $stmt = $conn->prepare("INSERT INTO loyalty_transactions (customer_id, points_earned, points_redeemed, transaction_type, description, created_at) VALUES (?, ?, ?, 'adjustment', ?, NOW())");
    $stmt->bind_param("iiis", $customerId, $earnedPoints, $redeemedPoints, $reason);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'Points adjusted successfully',
        'new_balance' => $newBalance
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update points']);
}
?>
