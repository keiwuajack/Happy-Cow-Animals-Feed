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
$description = sanitize($data['description']);
$status = sanitize($data['status']);

if ($id) {
    // Update existing category
    $sql = "UPDATE categories SET 
            name = '$name',
            description = '$description',
            status = '$status'
            WHERE id = $id";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
    } else {
        global $conn;
        echo json_encode([
            'success' => false,
            'message' => 'Error updating category',
            'error' => $conn->error,
            'sql' => $sql
        ]);
    }
} else {
    // Insert new category
    $sql = "INSERT INTO categories (name, description, status) 
            VALUES ('$name', '$description', '$status')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Category added successfully']);
    } else {
        global $conn;
        echo json_encode([
            'success' => false,
            'message' => 'Error adding category',
            'error' => $conn->error,
            'sql' => $sql
        ]);
    }
}
?>
