<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = intval($data['id']);
$user_id = $_SESSION['user_id'];

// Get expense details for logging
$sql = "SELECT description, amount FROM expenses WHERE id = $id";
$result = query($sql);
$expense = $result->fetch_assoc();

$sql = "DELETE FROM expenses WHERE id = $id";

if (query($sql)) {
    // Log activity
    $ip = $_SERVER['REMOTE_ADDR'];
    $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                VALUES ($user_id, 'Delete Expense', 'Expenses', 'Deleted expense: {$expense['description']} (KSh {$expense['amount']})', '$ip')";
    query($log_sql);
    
    echo json_encode(['success' => true, 'message' => 'Expense deleted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting expense']);
}
?>
