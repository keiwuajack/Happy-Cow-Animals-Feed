<?php
/**
 * WhatsApp Business API Integration Service
 * Supports multiple providers: Twilio, WhatsApp Cloud API, and Africa's Talking
 */

class WhatsAppService {
    private $conn;
    private $provider; // 'twilio', 'cloud_api', 'africastalking'
    private $apiKey;
    private $apiSecret;
    private $phoneNumberId;
    private $fromNumber;
    private $enabled;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
    }
    
    private function loadSettings() {
        $result = $this->conn->query("SELECT * FROM whatsapp_settings LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->provider = $settings['provider'];
            $this->apiKey = $settings['api_key'];
            $this->apiSecret = $settings['api_secret'];
            $this->phoneNumberId = $settings['phone_number_id'];
            $this->fromNumber = $settings['from_number'];
            $this->enabled = $settings['enabled'];
        }
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendMessage($to, $message) {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'WhatsApp service is not enabled'];
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
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->apiKey}/Messages.json";
        
        $data = [
            'From' => 'whatsapp:' . $this->fromNumber,
            'To' => 'whatsapp:' . $to,
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':' . $this->apiSecret);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
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
        $url = "https://graph.facebook.com/v18.0/{$this->phoneNumberId}/messages";
        
        $data = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $message]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
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
        $url = "https://api.africastalking.com/version1/messaging";
        
        $data = [
            'username' => $this->apiKey,
            'to' => $to,
            'message' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apiKey: ' . $this->apiSecret,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode == 201 && isset($result['SMSMessageData'])) {
            $this->logMessage($to, $message, 'sent', $response);
            return ['success' => true, 'message' => 'Message sent successfully'];
        }
        
        $this->logMessage($to, $message, 'failed', $response);
        return ['success' => false, 'message' => 'Failed to send message'];
    }
    
    /**
     * Send receipt via WhatsApp
     */
    public function sendReceipt($phone, $invoiceNumber, $total, $customerName, $items) {
        $message = "🧾 *RECEIPT - {$invoiceNumber}*\n\n";
        $message .= "Customer: {$customerName}\n";
        $message .= "Date: " . date('d/m/Y H:i') . "\n\n";
        $message .= "Items:\n";
        
        foreach ($items as $item) {
            $message .= "• {$item['name']} x{$item['quantity']} - KSh " . number_format($item['total'], 2) . "\n";
        }
        
        $message .= "\n*Total: KSh " . number_format($total, 2) . "*\n\n";
        $message .= "Thank you for your business! 🙏";
        
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
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (substr($phone, 0, 1) === '+') {
            $phone = substr($phone, 1);
        } elseif (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }
        
        return $phone;
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
