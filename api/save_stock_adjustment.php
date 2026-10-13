<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();
security_require_roles(['admin', 'manager']);

$data = json_decode(file_get_contents('php://input'), true);

$product_id = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT);
$adjustment_type = $data['adjustment_type'] ?? '';
$quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);
$reason = trim($data['reason'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$product_id || !in_array($adjustment_type, ['add', 'remove', 'damage', 'expired'], true) || !$quantity || $quantity < 1 || $reason === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid stock adjustment details']);
    exit();
}

$reason = sanitize($reason);

if (!$conn->begin_transaction()) {
    echo json_encode(['success' => false, 'message' => 'Could not start stock adjustment']);
    exit();
}

// Get current product stock
$sql = "SELECT quantity, name FROM products WHERE id = $product_id FOR UPDATE";
$result = query($sql);
$product = $result->fetch_assoc();

if (!$product) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

$previous_stock = $product['quantity'];
$new_stock = $previous_stock;

// Calculate new stock based on adjustment type
if ($adjustment_type === 'add') {
    $new_stock += $quantity;
} else {
    $new_stock -= $quantity;
}

// Check if new stock would be negative
if ($new_stock < 0) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock for this adjustment']);
    exit();
}

try {
    // Insert adjustment record
    $sql = "INSERT INTO stock_adjustments (product_id, adjustment_type, quantity, previous_stock, new_stock, reason, adjusted_by) 
            VALUES ($product_id, '$adjustment_type', $quantity, $previous_stock, $new_stock, '$reason', $user_id)";
    
    if (!query($sql)) {
        throw new Exception('Failed to record adjustment');
    }
    
    // Update product stock
    $sql = "UPDATE products SET quantity = $new_stock WHERE id = $product_id";
    
    if (!query($sql)) {
        throw new Exception('Failed to update product stock');
    }
    
    // Log activity
    $action = ucfirst($adjustment_type) . " Stock";
    $description = "$action: {$product['name']} - Quantity: $quantity. Reason: $reason";
    $ip = $_SERVER['REMOTE_ADDR'];
    $sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
            VALUES ($user_id, '$action', 'Stock Adjustments', '$description', '$ip')";
    query($sql);
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock adjustment saved successfully',
        'new_stock' => $new_stock
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
