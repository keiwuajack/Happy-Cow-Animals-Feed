<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $query = "SELECT c.*, 
              (SELECT MAX(payment_date) FROM credit_payments WHERE customer_id = c.id) as last_payment
              FROM customers c 
              WHERE c.credit_limit > 0 OR c.credit_balance > 0
              ORDER BY c.credit_balance DESC";
    
    $result = query($query);
    $customers = [];
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'customers' => $customers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
