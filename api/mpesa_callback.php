<?php
/**
 * M-Pesa Callback Handler
 * This endpoint receives payment notifications from Safaricom
 */

require_once '../config/database.php';
require_once '../includes/MpesaService.php';

// Get the JSON data from M-Pesa
$callbackData = file_get_contents('php://input');
$data = json_decode($callbackData, true);

// Log the callback for debugging
file_put_contents('../logs/mpesa_callback_' . date('Y-m-d') . '.log', 
    date('Y-m-d H:i:s') . ' - ' . $callbackData . "\n", 
    FILE_APPEND
);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
    exit();
}

try {
    $mpesa = new MpesaService();
    $result = $mpesa->processCallback($data);
    
    // If payment was successful, you can trigger additional actions here
    if ($result['success']) {
        // Update related sale if account reference matches
        // Send receipt to customer
        // Update loyalty points
        // etc.
    }
    
    // Respond to Safaricom
    echo json_encode([
        'ResultCode' => 0,
        'ResultDesc' => 'Callback processed successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'ResultCode' => 1,
        'ResultDesc' => 'Error processing callback: ' . $e->getMessage()
    ]);
}
?>
