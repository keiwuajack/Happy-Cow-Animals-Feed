<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) && $data['id'] !== '' ? intval($data['id']) : null;
$category = sanitize($data['category']);
$amount = floatval($data['amount']);
$description = sanitize($data['description']);
$expense_date = sanitize($data['expense_date']);
$payment_method = sanitize($data['payment_method']);
$receipt_number = isset($data['receipt_number']) && !empty($data['receipt_number']) ? sanitize($data['receipt_number']) : null;
$user_id = $_SESSION['user_id'];

if ($id) {
    // Update existing expense
    $receipt_clause = $receipt_number ? "'$receipt_number'" : "NULL";
    $sql = "UPDATE expenses SET 
            category = '$category',
            amount = $amount,
            description = '$description',
            expense_date = '$expense_date',
            payment_method = '$payment_method',
            receipt_number = $receipt_clause
            WHERE id = $id";
    
    if (query($sql)) {
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                    VALUES ($user_id, 'Update Expense', 'Expenses', 'Updated expense: $description (KSh $amount)', '$ip')";
        query($log_sql);
        
        echo json_encode(['success' => true, 'message' => 'Expense updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating expense']);
    }
} else {
    // Insert new expense
    $receipt_clause = $receipt_number ? "'$receipt_number'" : "NULL";
    $sql = "INSERT INTO expenses (category, amount, description, expense_date, payment_method, receipt_number, added_by) 
            VALUES ('$category', $amount, '$description', '$expense_date', '$payment_method', $receipt_clause, $user_id)";
    
    if (query($sql)) {
        // Log activity
        $ip = $_SERVER['REMOTE_ADDR'];
        $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                    VALUES ($user_id, 'Add Expense', 'Expenses', 'Added expense: $description (KSh $amount)', '$ip')";
        query($log_sql);
        
        echo json_encode(['success' => true, 'message' => 'Expense added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding expense']);
    }
}
?>
