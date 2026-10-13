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
$name = sanitize($data['name']);
$phone = sanitize($data['phone']);
$email = sanitize($data['email']);
$customer_type = sanitize($data['customer_type']);
$address = sanitize($data['address']);
$farm_size = sanitize($data['farm_size']);
$credit_limit = floatval($data['credit_limit']);
$status = sanitize($data['status']);

if ($id) {
    // Update existing customer
    $sql = "UPDATE customers SET 
            name = '$name',
            phone = '$phone',
            email = '$email',
            customer_type = '$customer_type',
            address = '$address',
            farm_size = '$farm_size',
            credit_limit = $credit_limit,
            status = '$status'
            WHERE id = $id";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating customer']);
    }
} else {
    // Insert new customer
    $sql = "INSERT INTO customers (name, phone, email, customer_type, address, farm_size, credit_limit, status) 
            VALUES ('$name', '$phone', '$email', '$customer_type', '$address', '$farm_size', $credit_limit, '$status')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding customer']);
    }
}
?>
