<?php
session_start();
require_once '../config/database.php';
require_once '../includes/MpesaService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$phone = $data['phone'] ?? '';
$amount = $data['amount'] ?? 0;
$accountReference = $data['account_reference'] ?? '';
$description = $data['description'] ?? 'Payment';

if (empty($phone) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number or amount']);
    exit();
}

try {
    $mpesa = new MpesaService();
    $result = $mpesa->stkPush($phone, $amount, $accountReference, $description);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
