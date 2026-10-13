<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager']);
security_require_same_origin();
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$poId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$poId) { echo json_encode(['success' => false, 'message' => 'Invalid purchase order']); exit(); }
try {
    $pdo->beginTransaction();
    $poStmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ? AND status IN ('draft', 'sent', 'partial') FOR UPDATE"); $poStmt->execute([$poId]); $po = $poStmt->fetch();
    if (!$po) throw new Exception('Purchase order is unavailable or already received');
    $itemsStmt = $pdo->prepare("SELECT * FROM purchase_order_items WHERE po_id = ? FOR UPDATE"); $itemsStmt->execute([$poId]); $items = $itemsStmt->fetchAll();
    if (!$items) throw new Exception('Purchase order has no items');
    $stockStmt = $pdo->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
    $lineStmt = $pdo->prepare('UPDATE purchase_order_items SET quantity_received = quantity_ordered WHERE id = ?');
    foreach ($items as $item) {
        $outstanding = (int) $item['quantity_ordered'] - (int) $item['quantity_received'];
        if ($outstanding > 0) { $stockStmt->execute([$outstanding, $item['product_id']]); $lineStmt->execute([$item['id']]); }
    }
    $update = $pdo->prepare("UPDATE purchase_orders SET status = 'received', received_date = CURDATE() WHERE id = ?"); $update->execute([$poId]);
    $log = $pdo->prepare("INSERT INTO activity_log (user_id, action, module, description) VALUES (?, 'purchase_order_received', 'Purchasing', ?)");
    $log->execute([$_SESSION['user_id'], "Received purchase order {$po['po_number']} and updated stock"]);
    $pdo->commit(); echo json_encode(['success' => true, 'message' => 'Purchase order received and stock updated']);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?>
