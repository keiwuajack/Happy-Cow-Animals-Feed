<?php
session_start();
require_once '../config/database.php';
require_once '../includes/EmailService.php';
require_once '../includes/WhatsAppService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$saleId = filter_var($data['sale_id'] ?? null, FILTER_VALIDATE_INT);
if (!$saleId || $saleId < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid sale']);
    exit;
}

$saleSql = "SELECT s.invoice_number, s.subtotal, s.discount, s.tax, s.total, s.payment_method,
    c.name AS customer_name, c.email, c.receive_email, c.whatsapp_number, c.receive_whatsapp
    FROM sales s LEFT JOIN customers c ON c.id = s.customer_id WHERE s.id = ?";
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
$sale = $saleStmt->get_result()->fetch_assoc();
$saleStmt->close();
if (!$sale) {
    echo json_encode(['success' => false, 'message' => 'Sale not found']);
    exit;
}

$itemsStmt = $conn->prepare("SELECT product_name AS name, quantity, unit_price AS price, subtotal AS total FROM sale_items WHERE sale_id = ?");
$itemsStmt->bind_param('i', $saleId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}
$itemsStmt->close();

$customerName = $sale['customer_name'] ?: 'Customer';
$notifications = [
    'email' => ['success' => false, 'skipped' => true, 'message' => 'No opted-in customer email address'],
    'whatsapp' => ['success' => false, 'skipped' => true, 'message' => 'No opted-in WhatsApp number']
];

if (!empty($sale['receive_email']) && filter_var($sale['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    try {
        $email = new EmailService();
        if ($email->canSendReceipt()) {
            $notifications['email'] = $email->sendReceipt(
                $sale['email'], $customerName, $sale['invoice_number'], $items,
                (float) $sale['subtotal'], (float) $sale['tax'], (float) $sale['discount'],
                (float) $sale['total'], $sale['payment_method']
            );
        }
    } catch (Throwable $error) {
        $notifications['email'] = ['success' => false, 'message' => 'Email error: ' . $error->getMessage()];
    }
}

if (!empty($sale['receive_whatsapp']) && !empty($sale['whatsapp_number'])) {
    try {
        $whatsapp = new WhatsAppService();
        if ($whatsapp->canSendReceipt()) {
            $notifications['whatsapp'] = $whatsapp->sendReceipt(
                $sale['whatsapp_number'], $sale['invoice_number'], (float) $sale['total'], $customerName, $items
            );
        }
    } catch (Throwable $error) {
        $notifications['whatsapp'] = ['success' => false, 'message' => 'WhatsApp error: ' . $error->getMessage()];
    }
}

echo json_encode(['success' => true, 'notifications' => $notifications]);
?>
