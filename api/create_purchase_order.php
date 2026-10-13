<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager']);
security_require_same_origin();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$items = $data['items'] ?? [];
$supplierId = filter_var($data['supplier_id'] ?? null, FILTER_VALIDATE_INT);
if (!$supplierId || !is_array($items) || !$items) { echo json_encode(['success' => false, 'message' => 'Supplier and at least one product are required']); exit(); }
try {
    $pdo->beginTransaction();
    $check = $pdo->prepare("SELECT id FROM suppliers WHERE id = ? AND status = 'active'"); $check->execute([$supplierId]);
    if (!$check->fetch()) throw new Exception('Supplier is not active');
    $validated = []; $total = 0;
    $productStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND status = 'active'");
    foreach ($items as $item) {
        $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
        $unitCost = filter_var($item['unit_cost'] ?? null, FILTER_VALIDATE_FLOAT);
        if (!$productId || !$quantity || $quantity < 1 || $unitCost === false || $unitCost < 0) throw new Exception('Invalid purchase order line');
        $productStmt->execute([$productId]); if (!$productStmt->fetch()) throw new Exception('Product is unavailable');
        $lineTotal = $quantity * $unitCost; $total += $lineTotal;
        $validated[] = [$productId, $quantity, $unitCost, $lineTotal];
    }
    $number = 'PO' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $stmt = $pdo->prepare("INSERT INTO purchase_orders (po_number, supplier_id, total_amount, status, expected_date, created_by, notes) VALUES (?, ?, ?, 'draft', ?, ?, ?)");
    $stmt->execute([$number, $supplierId, $total, $data['expected_date'] ?: null, $_SESSION['user_id'], trim($data['notes'] ?? '') ?: null]);
    $poId = $pdo->lastInsertId();
    $itemStmt = $pdo->prepare("INSERT INTO purchase_order_items (po_id, product_id, quantity_ordered, unit_cost, total) VALUES (?, ?, ?, ?, ?)");
    foreach ($validated as $line) $itemStmt->execute([$poId, $line[0], $line[1], $line[2], $line[3]]);
    $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, module, description) VALUES (?, 'purchase_order_created', 'Purchasing', ?)");
    $log->execute([$_SESSION['user_id'], "Created purchase order {$number}"]);
    $pdo->commit(); echo json_encode(['success' => true, 'message' => 'Purchase order created', 'po_number' => $number]);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?>
