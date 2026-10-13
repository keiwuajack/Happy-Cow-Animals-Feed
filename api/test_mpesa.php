<?php
session_start();
require_once '../config/database.php';
require_once '../includes/MpesaService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $mpesa = new MpesaService();
    
    // Try to generate access token as a test
    $reflection = new ReflectionClass($mpesa);
    $method = $reflection->getMethod('generateAccessToken');
    $method->setAccessible(true);
    
    $token = $method->invoke($mpesa);
    
    if ($token) {
        echo json_encode([
            'success' => true,
            'message' => 'M-Pesa connection successful',
            'token_length' => strlen($token)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate access token. Check your credentials.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
