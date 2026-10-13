<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $query = "SELECT cp.*, c.name as customer_name, u.username as recorded_by_name
              FROM credit_payments cp
              JOIN customers c ON cp.customer_id = c.id
              LEFT JOIN users u ON cp.recorded_by = u.id
              ORDER BY cp.payment_date DESC
              LIMIT 50";
    
    $result = query($query);
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'payments' => $payments
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
