<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Total customers with credit
    $result = query("SELECT COUNT(*) as count FROM customers WHERE credit_limit > 0 OR credit_balance > 0");
    $total_customers = $result->fetch_assoc()['count'];
    
    // Total outstanding balance
    $result = query("SELECT SUM(credit_balance) as total FROM customers");
    $row = $result->fetch_assoc();
    $total_outstanding = $row['total'] ?? 0;
    
    // Total credit limit
    $result = query("SELECT SUM(credit_limit) as total FROM customers");
    $row = $result->fetch_assoc();
    $total_limit = $row['total'] ?? 0;
    
    // Payments today
    $result = query("SELECT SUM(amount) as total FROM credit_payments WHERE DATE(payment_date) = CURDATE()");
    $row = $result->fetch_assoc();
    $payments_today = $row['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_customers' => $total_customers,
            'total_outstanding' => $total_outstanding,
            'total_limit' => $total_limit,
            'payments_today' => $payments_today
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
