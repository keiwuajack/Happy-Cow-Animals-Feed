<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$id = intval($_POST['product_id'] ?? 0);
if ($id <= 0 || !isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image received']);
    exit();
}

$file = $_FILES['image'];
if ($file['size'] > 3 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Image is too large (max 3MB)']);
    exit();
}

$info = @getimagesize($file['tmp_name']);
$types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
if (!$info || !isset($types[$info[2]])) {
    echo json_encode(['success' => false, 'message' => 'Use a JPG, PNG or WEBP image']);
    exit();
}

$dir = __DIR__ . '/../uploads/products/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$name = 'p' . $id . '_' . bin2hex(random_bytes(6)) . '.' . $types[$info[2]];
if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
    echo json_encode(['success' => false, 'message' => 'Could not save image']);
    exit();
}

$old = query("SELECT image FROM products WHERE id = $id")->fetch_assoc();
$path = 'uploads/products/' . $name;
if (!query("UPDATE products SET image = '$path' WHERE id = $id")) {
    @unlink($dir . $name);
    echo json_encode(['success' => false, 'message' => 'Could not update product']);
    exit();
}
if (!empty($old['image']) && strpos($old['image'], 'uploads/products/') === 0) {
    @unlink(__DIR__ . '/../' . $old['image']);
}

echo json_encode(['success' => true, 'image' => $path]);
