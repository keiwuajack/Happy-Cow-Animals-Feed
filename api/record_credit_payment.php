<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$customer_id = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
$amount = floatval($input['amount'] ?? 0);
$payment_method = isset($input['payment_method']) ? strtolower((string) $input['payment_method']) : 'cash';
$reference_number = $input['reference_number'] ?? null;
$notes = $input['notes'] ?? null;

if ($customer_id <= 0 || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data']);
    exit;
}

try {
    $conn->begin_transaction();
    
    $customerStmt = $conn->prepare("SELECT credit_balance FROM customers WHERE id = ?");
    $customerStmt->bind_param('i', $customer_id);
    $customerStmt->execute();
    $customerResult = $customerStmt->get_result();
    $customer = $customerResult ? $customerResult->fetch_assoc() : null;
    $customerStmt->close();
    
    if (!$customer) {
        throw new Exception('Customer not found');
    }
    
    if ($amount > $customer['credit_balance']) {
        throw new Exception('Payment amount exceeds outstanding balance');
    }
    
    $user_id = $_SESSION['user_id'];
    $paymentStmt = $conn->prepare("INSERT INTO credit_payments (customer_id, amount, payment_method, reference_number, notes, recorded_by, payment_date) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $refValue = is_string($reference_number) ? $reference_number : null;
    $notesValue = is_string($notes) ? $notes : null;
    $paymentStmt->bind_param('idsssi', $customer_id, $amount, $payment_method, $refValue, $notesValue, $user_id);
    $paymentStmt->execute();
    $paymentStmt->close();
    
    $new_balance = $customer['credit_balance'] - $amount;
    $updateStmt = $conn->prepare("UPDATE customers SET credit_balance = ? WHERE id = ?");
    $updateStmt->bind_param('di', $new_balance, $customer_id);
    $updateStmt->execute();
    $updateStmt->close();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $activityDescription = 'Recorded credit payment of KSh ' . number_format($amount, 2) . ' for customer ID: ' . (string) $customer_id;
    $activityStmt = $conn->prepare("INSERT INTO activity_log (user_id, action, module, description, ip_address) VALUES (?, 'Credit Payment', 'Credit Sales', ?, ?)");
    $activityStmt->bind_param('iss', $user_id, $activityDescription, $ip);
    $activityStmt->execute();
    $activityStmt->close();
    
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
