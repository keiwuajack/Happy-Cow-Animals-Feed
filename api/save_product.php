<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit();
}

$id = isset($data['id']) && $data['id'] !== '' ? intval($data['id']) : null;
$name = sanitize($data['name']);
$sku = sanitize($data['sku']);
$barcode = sanitize($data['barcode'] ?? '');
$barcode_value = $barcode !== '' ? "'$barcode'" : "NULL";
$category_id = intval($data['category_id']);
$unit = sanitize($data['unit']);
$cost_price = floatval($data['cost_price']);
$selling_price = floatval($data['selling_price']);
$quantity = intval($data['quantity']);
$reorder_level = intval($data['reorder_level']);
$description = sanitize($data['description']);
$expiry_date = isset($data['expiry_date']) && !empty($data['expiry_date']) ? sanitize($data['expiry_date']) : null;
$status = sanitize($data['status']);

if ($id) {
    // Update existing product
    $sku_value = !empty($sku) ? "'$sku'" : "NULL";
    $expiry_value = $expiry_date ? "'$expiry_date'" : "NULL";
    $sql = "UPDATE products SET 
            name = '$name',
            sku = $sku_value,
            barcode = $barcode_value,
            category_id = $category_id,
            unit = '$unit',
            cost_price = $cost_price,
            selling_price = $selling_price,
            quantity = $quantity,
            reorder_level = $reorder_level,
            description = '$description',
            expiry_date = $expiry_value,
            status = '$status'
            WHERE id = $id";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully', 'id' => $id]);
    } else {
        $message = $conn->errno === 1062
            ? 'A product with this SKU or barcode already exists.'
            : 'Could not update product. Check the product details and try again.';
        echo json_encode(['success' => false, 'message' => $message]);
    }
} else {
    // Insert new product
    $sku_clause = !empty($sku) ? "'$sku'" : "NULL";
    $expiry_value = $expiry_date ? "'$expiry_date'" : "NULL";
    $sql = "INSERT INTO products     (name, sku, barcode, category_id, unit, cost_price, selling_price, quantity, reorder_level, description, expiry_date, status) 
                VALUES ('$name', $sku_clause, $barcode_value, $category_id, '$unit', $cost_price, $selling_price, $quantity, $reorder_level, '$description', $expiry_value, '$status')";
    
    if (query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully', 'id' => $conn->insert_id]);
    } else {
        $message = $conn->errno === 1062
            ? 'A product with this SKU or barcode already exists.'
            : 'Could not add product. Check the product details and try again.';
        echo json_encode(['success' => false, 'message' => $message]);
    }
}
?>

