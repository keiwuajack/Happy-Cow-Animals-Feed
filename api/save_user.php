<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

$id = isset($data['id']) && $data['id'] !== '' ? intval($data['id']) : null;
$username = sanitize($data['username']);
$full_name = sanitize($data['full_name']);
$email = sanitize($data['email']);
$phone = sanitize($data['phone']);
$password = $data['password'];
$role = sanitize($data['role']);
$status = sanitize($data['status']);

if ($id) {
    // Update existing user
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET 
                username = '$username',
                full_name = '$full_name',
                email = '$email',
                phone = '$phone',
                password = '$hashed_password',
                role = '$role',
                status = '$status'
                WHERE id = $id";
    } else {
        $sql = "UPDATE users SET 
                username = '$username',
                full_name = '$full_name',
                email = '$email',
                phone = '$phone',
                role = '$role',
                status = '$status'
                WHERE id = $id";
    }
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user']);
    }
} else {
    // Insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, full_name, email, phone, password, role, status) 
            VALUES ('$username', '$full_name', '$email', '$phone', '$hashed_password', '$role', '$status')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding user. Username might already exist.']);
    }
}
?>
