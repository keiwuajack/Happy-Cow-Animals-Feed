<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$customer_id = $input['customer_id'] ?? null;
$amount = floatval($input['amount'] ?? 0);
$payment_method = $input['payment_method'] ?? 'cash';
$reference_number = $input['reference_number'] ?? null;
$notes = $input['notes'] ?? null;

if (!$customer_id || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // Get customer current balance
    $customer_id_safe = sanitize($customer_id);
    $result = query("SELECT credit_balance FROM customers WHERE id = $customer_id_safe");
    $customer = $result->fetch_assoc();
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    if ($amount > $customer['credit_balance']) {
        throw new Exception('Payment amount exceeds outstanding balance');
    }
    
    // Record payment
    $amount_safe = sanitize($amount);
    $method_safe = sanitize($payment_method);
    $ref_safe = sanitize($reference_number);
    $notes_safe = sanitize($notes);
    $user_id = $_SESSION['user_id'];
    
    query("INSERT INTO credit_payments (customer_id, amount, payment_method, reference_number, notes, recorded_by, payment_date) 
           VALUES ($customer_id_safe, $amount_safe, '$method_safe', '$ref_safe', '$notes_safe', $user_id, NOW())");
    
    // Update customer balance
    $new_balance = $customer['credit_balance'] - $amount;
    query("UPDATE customers SET credit_balance = $new_balance WHERE id = $customer_id_safe");
    
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'];
    query("INSERT INTO activity_log (user_id, action, module, description, ip_address) 
           VALUES ($user_id, 'Credit Payment', 'Credit Sales', 'Recorded credit payment of KSh $amount for customer ID: $customer_id', '$ip')");
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment recorded successfully',
        'new_balance' => $new_balance
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
