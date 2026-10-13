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
$contact_person = sanitize($data['contact_person']);
$phone = sanitize($data['phone']);
$email = sanitize($data['email']);
$address = sanitize($data['address']);
$status = sanitize($data['status']);

if ($id) {
    // Update existing supplier
    $sql = "UPDATE suppliers SET 
            name = '$name',
            contact_person = '$contact_person',
            phone = '$phone',
            email = '$email',
            address = '$address',
            status = '$status'
            WHERE id = $id";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating supplier']);
    }
} else {
    // Insert new supplier
    $sql = "INSERT INTO suppliers (name, contact_person, phone, email, address, status) 
            VALUES ('$name', '$contact_person', '$phone', '$email', '$address', '$status')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding supplier']);
    }
}
?>
