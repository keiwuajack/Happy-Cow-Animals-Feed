<?php
session_start();
require_once '../config/database.php';
require_once '../includes/WhatsAppService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$to = $data['to'] ?? '';
$message = $data['message'] ?? '';

if (empty($to) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Phone number and message are required']);
    exit();
}

try {
    $whatsapp = new WhatsAppService();
    $result = $whatsapp->sendMessage($to, $message);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
