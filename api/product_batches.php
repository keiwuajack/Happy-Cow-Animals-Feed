<?php
require_once '../config/pdo.php';
header('Content-Type: application/json');
security_require_roles(['admin', 'manager', 'cashier']);
$products = $pdo->query("SELECT id, name, sku FROM products WHERE status = 'active' ORDER BY name")->fetchAll();
$batches = [];
try { $batches = $pdo->query("SELECT b.*, p.name AS product_name, p.sku, COALESCE((SELECT SUM(sb.quantity) FROM sale_item_batches sb WHERE sb.batch_id = b.id),0) AS sold_qty FROM product_batches b JOIN products p ON p.id = b.product_id ORDER BY b.expiry_date IS NULL, b.expiry_date ASC, p.name ASC")->fetchAll(); } catch (Throwable $e) { }
$history = [];
try { $history = $pdo->query("SELECT sb.created_at, b.batch_number, p.name AS product_name, sb.quantity, s.id AS sale_id, s.invoice_number, u.full_name AS cashier FROM sale_item_batches sb JOIN product_batches b ON b.id = sb.batch_id JOIN products p ON p.id = b.product_id JOIN sale_items si ON si.id = sb.sale_item_id JOIN sales s ON s.id = si.sale_id LEFT JOIN users u ON u.id = s.user_id ORDER BY sb.id DESC LIMIT 100")->fetchAll(); } catch (Throwable $e) { }
echo json_encode(['success' => true, 'products' => $products, 'batches' => $batches, 'history' => $history]);
?>
