<?php
/**
 * WhatsApp Business API Integration Service
 * Supports Meta WhatsApp Cloud API and Twilio
 */

class WhatsAppService {
    private $conn;
    private $provider; // 'twilio' or 'cloud_api'
    private $apiKey;
    private $apiSecret;
    private $phoneNumberId;
    private $fromNumber;
    private $enabled;
    private $sendReceipts = false;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
    }

    public function canSendReceipt() {
        return $this->enabled && $this->sendReceipts;
    }
    
    private function loadSettings() {
        $result = $this->conn->query("SELECT * FROM whatsapp_settings ORDER BY id LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->provider = $settings['provider'];
            $this->apiKey = $settings['api_key'];
            $this->apiSecret = $settings['api_secret'];
            $this->phoneNumberId = $settings['phone_number_id'];
            $this->fromNumber = $settings['from_number'];
            $this->enabled = (bool) $settings['enabled'];
            $this->sendReceipts = (bool) $settings['send_receipts'];
        }
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendMessage($to, $message) {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp service is not enabled'];
        }

        if (empty($this->apiKey)) {
            return ['success' => false, 'message' => 'WhatsApp access token or API key is missing'];
        }
        
        $to = $this->formatPhoneNumber($to);
        
        switch ($this->provider) {
            case 'twilio':
                return $this->sendViaTwilio($to, $message);
            case 'cloud_api':
                return $this->sendViaCloudAPI($to, $message);
            case 'africastalking':
                return $this->sendViaAfricasTalking($to, $message);
            default:
                return ['success' => false, 'message' => 'Invalid provider'];
        }
    }
    
    /**
     * Send via Twilio WhatsApp API
     */
    private function sendViaTwilio($to, $message) {
        if (empty($this->apiSecret) || empty($this->fromNumber)) {
            return ['success' => false, 'message' => 'Twilio account secret and WhatsApp sender number are required'];
        }
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->apiKey}/Messages.json";
        
        $data = [
            'From' => 'whatsapp:+' . $this->formatPhoneNumber($this->fromNumber),
            'To' => 'whatsapp:+' . $to,
            'Body' => $message
        ];
        
        [$response, $httpCode, $curlError] = $this->request($url, [
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_USERPWD => $this->apiKey . ':' . $this->apiSecret
        ]);
        if ($curlError !== '') return ['success' => false, 'message' => 'Twilio connection failed: ' . $curlError];
        
        $result = json_decode($response, true);
        
        if ($httpCode == 201) {
            $this->logMessage($to, $message, 'sent', $response);
            return ['success' => true, 'message' => 'Message sent successfully', 'sid' => $result['sid']];
        }
        
        $this->logMessage($to, $message, 'failed', $response);
        return ['success' => false, 'message' => $result['message'] ?? 'Failed to send message'];
    }
    
    /**
     * Send via WhatsApp Cloud API (Meta)
     */
    private function sendViaCloudAPI($to, $message) {
        if (empty($this->phoneNumberId)) {
            return ['success' => false, 'message' => 'Meta WhatsApp Phone Number ID is required'];
        }
        // v18.0 expired in January 2026; use the current Graph API version.
        $url = "https://graph.facebook.com/v26.0/{$this->phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        [$response, $httpCode, $curlError] = $this->request($url, [
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
            ]
        ]);
        if ($curlError !== '') return ['success' => false, 'message' => 'Meta WhatsApp connection failed: ' . $curlError];
        
        $result = json_decode($response, true);
        
        if ($httpCode == 200 && isset($result['messages'])) {
            $this->logMessage($to, $message, 'sent', $response);
            return ['success' => true, 'message' => 'Message sent successfully', 'message_id' => $result['messages'][0]['id']];
        }
        
        $this->logMessage($to, $message, 'failed', $response);
        return ['success' => false, 'message' => $result['error']['message'] ?? 'Failed to send message'];
    }
    
    /**
     * Send via Africa's Talking
     */
    private function sendViaAfricasTalking($to, $message) {
        return ['success' => false, 'message' => "Africa's Talking is an SMS endpoint in this POS, not a WhatsApp provider. Choose Meta Cloud API or Twilio."];
    }
    
    /**
     * Send receipt via WhatsApp
     */
    public function sendReceipt($phone, $invoiceNumber, $total, $customerName, $items) {
        if (!$this->sendReceipts) {
            return ['success' => false, 'message' => 'WhatsApp receipts are disabled in WhatsApp Settings'];
        }
        $message = "*RECEIPT - {$invoiceNumber}*\n\n";
        $message .= "Customer: {$customerName}\n";
        $message .= "Date: " . date('d/m/Y H:i') . "\n\n";
        $message .= "Items:\n";
        foreach ($items as $item) {
            $name = preg_replace('/[\r\n]+/', ' ', (string) ($item['name'] ?? 'Product'));
            $quantity = (int) ($item['quantity'] ?? 0);
            $lineTotal = (float) ($item['total'] ?? 0);
            $message .= "- {$name} x{$quantity} - KSh " . number_format($lineTotal, 2) . "\n";
        }
        $message .= "\n*Total: KSh " . number_format((float) $total, 2) . "*\n\n";
        $message .= "Thank you for your business!";
        return $this->sendMessage($phone, $message);
    }
    /**
     * Send low stock alert
     */
    public function sendLowStockAlert($phone, $productName, $currentStock, $reorderLevel) {
        $message = "⚠️ *LOW STOCK ALERT*\n\n";
        $message .= "Product: {$productName}\n";
        $message .= "Current Stock: {$currentStock}\n";
        $message .= "Reorder Level: {$reorderLevel}\n\n";
        $message .= "Please restock soon!";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send credit balance reminder
     */
    public function sendCreditReminder($phone, $customerName, $balance, $dueDate) {
        $message = "💳 *PAYMENT REMINDER*\n\n";
        $message .= "Dear {$customerName},\n\n";
        $message .= "Outstanding Balance: KSh " . number_format($balance, 2) . "\n";
        if ($dueDate) {
            $message .= "Due Date: {$dueDate}\n";
        }
        $message .= "\nPlease arrange payment. Thank you!";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Send payment confirmation
     */
    public function sendPaymentConfirmation($phone, $customerName, $amount, $invoiceNumber) {
        $message = "✅ *PAYMENT RECEIVED*\n\n";
        $message .= "Dear {$customerName},\n\n";
        $message .= "We confirm receipt of KSh " . number_format($amount, 2) . "\n";
        $message .= "Invoice: {$invoiceNumber}\n";
        $message .= "Date: " . date('d/m/Y H:i') . "\n\n";
        $message .= "Thank you for your payment! 🙏";
        
        return $this->sendMessage($phone, $message);
    }
    
    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phone) {
        $rawPhone = trim((string) $phone);
        $hasInternationalPrefix = strpos($rawPhone, '+') === 0;
        $digits = preg_replace('/\D/', '', $rawPhone);
        if (strpos($digits, '0') === 0) return '254' . substr($digits, 1);
        if (strpos($digits, '254') === 0) return $digits;
        if ($hasInternationalPrefix) return $digits;
        if (strlen($digits) === 9) return '254' . $digits;
        return $digits;
    }

    private function request($url, $options = []) {
        if (!function_exists('curl_init')) {
            return ['', 0, 'PHP cURL extension is not enabled'];
        }
        $ch = curl_init($url);
        if (!$ch) return ['', 0, 'Could not initialize cURL'];
        curl_setopt_array($ch, array_merge([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30
        ], $options));
        $response = curl_exec($ch);
        $error = $response === false ? curl_error($ch) : '';
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$response === false ? '' : $response, $httpCode, $error];
    }
    
    /**
     * Log WhatsApp message
     */
    private function logMessage($to, $message, $status, $response) {
        $stmt = $this->conn->prepare("INSERT INTO whatsapp_log (phone_number, message, status, response, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $to, $message, $status, $response);
        return $stmt->execute();
    }
    
    /**
     * Get message logs
     */
    public function getMessageLogs($limit = 50) {
        $stmt = $this->conn->prepare("SELECT * FROM whatsapp_log ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
}
?>
