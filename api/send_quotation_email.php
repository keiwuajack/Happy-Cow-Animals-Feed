<?php
session_start();
require_once '../config/database.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
$quotationId = filter_var($data['quotation_id'] ?? null, FILTER_VALIDATE_INT);
if (!$quotationId || $quotationId < 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid quotation']);
    exit;
}

$quoteStmt = $conn->prepare("SELECT q.quote_number, q.total, q.valid_until, c.name AS customer_name, c.email, c.receive_email
    FROM quotations q LEFT JOIN customers c ON c.id = q.customer_id WHERE q.id = ?");
$quoteStmt->bind_param('i', $quotationId);
$quoteStmt->execute();
$quote = $quoteStmt->get_result()->fetch_assoc();
$quoteStmt->close();
if (!$quote) {
    echo json_encode(['success' => false, 'message' => 'Quotation not found']);
    exit;
}
if (empty($quote['receive_email']) || !filter_var($quote['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'The customer has no opted-in email address']);
    exit;
}

$itemsStmt = $conn->prepare("SELECT COALESCE(p.name, 'Product') AS name, qi.quantity, qi.unit_price AS price, qi.total
    FROM quotation_items qi LEFT JOIN products p ON p.id = qi.product_id WHERE qi.quotation_id = ?");
$itemsStmt->bind_param('i', $quotationId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}
$itemsStmt->close();

try {
    $email = new EmailService();
    $result = $email->sendQuotation(
        $quote['email'], $quote['customer_name'] ?: 'Customer', $quote['quote_number'],
        $items, (float) $quote['total'], $quote['valid_until']
    );
    echo json_encode($result);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'message' => 'Email error: ' . $error->getMessage()]);
}
?>
