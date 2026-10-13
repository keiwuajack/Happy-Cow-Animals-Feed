<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
$status = $_GET['status'] ?? '';
$sql = "SELECT po.*, s.name AS supplier_name, u.full_name AS created_by_name FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id JOIN users u ON u.id = po.created_by";
$params = [];
if (in_array($status, ['draft', 'sent', 'partial', 'received', 'cancelled'], true)) { $sql .= ' WHERE po.status = ?'; $params[] = $status; }
$sql .= ' ORDER BY po.created_at DESC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
echo json_encode(['success' => true, 'orders' => $stmt->fetchAll()]);
?>
