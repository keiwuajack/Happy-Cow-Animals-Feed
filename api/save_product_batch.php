<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager']);
security_require_same_origin();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$productId = filter_var($data['product_id'] ?? null, FILTER_VALIDATE_INT); $batch = trim($data['batch_number'] ?? ''); $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT); $cost = filter_var($data['unit_cost'] ?? null, FILTER_VALIDATE_FLOAT); $expiry = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
if (!$productId || $batch === '' || $quantity === false || $quantity < 0 || $cost === false || $cost < 0) { echo json_encode(['success' => false, 'message' => 'Enter valid batch details']); exit(); }
try { $stmt = $pdo->prepare("INSERT INTO product_batches (product_id, batch_number, expiry_date, quantity, unit_cost) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE expiry_date = VALUES(expiry_date), quantity = VALUES(quantity), unit_cost = VALUES(unit_cost)"); $stmt->execute([$productId, $batch, $expiry, $quantity, $cost]); echo json_encode(['success' => true, 'message' => 'Batch saved']); } catch (Throwable $e) { echo json_encode(['success' => false, 'message' => 'Batch storage is not installed. Run config/migrate_batches.php from CLI.']); }
?>
