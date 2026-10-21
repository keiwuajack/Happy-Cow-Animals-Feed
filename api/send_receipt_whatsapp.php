<?php
session_start();
require_once '../config/database.php';
require_once '../includes/WhatsAppService.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$saleId = intval($data['sale_id'] ?? 0);
if ($saleId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale']);
    exit;
}
$saleSql = "SELECT s.*, c.name AS customer_name, c.whatsapp_number FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?";
$isCashier = strtolower((string) ($_SESSION['role'] ?? '')) === 'cashier';
if ($isCashier) {
    $saleSql .= ' AND s.user_id = ?';
}
$saleStmt = $conn->prepare($saleSql);
if ($isCashier) {
    $cashierId = (int) $_SESSION['user_id'];
    $saleStmt->bind_param('ii', $saleId, $cashierId);
} else {
    $saleStmt->bind_param('i', $saleId);
}
$saleStmt->execute();
$saleResult = $saleStmt->get_result();
$sale = $saleResult ? $saleResult->fetch_assoc() : null;
$saleStmt->close();
if (!$sale) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}
if (empty($sale['whatsapp_number'])) {
    echo json_encode(['success' => false, 'message' => 'Customer has no WhatsApp number']);
    exit;
}
$itemStmt = $conn->prepare("SELECT product_name AS name, quantity, subtotal AS total FROM sale_items WHERE sale_id = ?");
$itemStmt->bind_param('i', $saleId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();
$items = [];
while ($item = $itemResult->fetch_assoc()) {
    $items[] = $item;
}
$itemStmt->close();
try {
    $service = new WhatsAppService();
    echo json_encode($service->sendReceipt($sale['whatsapp_number'], $sale['invoice_number'], $sale['total'], $sale['customer_name'], $items));
} catch (Exception $exception) {
    echo json_encode(['success' => false, 'message' => $exception->getMessage()]);
}
?>
