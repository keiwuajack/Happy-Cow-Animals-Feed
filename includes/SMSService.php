<?php
/**
 * Africa's Talking SMS Integration
 * For sending receipts, low stock alerts, and customer notifications
 */

class SMSService {
    private $apiKey;
    private $username;
    private $sender;
    private $baseUrl = 'https://api.africastalking.com/version1/messaging';
    
    public function __construct() {
        // Load settings from database
        $this->loadSettings();
    }
    
    private function loadSettings() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT * FROM sms_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($settings) {
                $this->apiKey = $settings['api_key'];
                $this->username = $settings['username'];
                $this->sender = $settings['sender_id'] ?? 'VISIONPOS';
            }
        } catch (PDOException $e) {
            error_log("SMS Settings Error: " . $e->getMessage());
        }
    }
    
    public function sendSMS($to, $message) {
        if (empty($this->apiKey) || empty($this->username)) {
            return ['success' => false, 'message' => 'SMS not configured'];
        }
        
        // Format phone number (add Kenya code if not present)
        $to = $this->formatPhoneNumber($to);
        
        $data = [
            'username' => $this->username,
            'to' => $to,
            'message' => $message,
            'from' => $this->sender
        ];
        
        $headers = [
            'apiKey: ' . $this->apiKey,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ];
        
        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        // Log SMS
        $this->logSMS($to, $message, $httpCode == 200 ? 'sent' : 'failed', $response);
        
        return [
            'success' => $httpCode == 200,
            'response' => $result
        ];
    }
    
    public function sendReceiptSMS($phone, $invoiceNumber, $total, $customerName) {
        $message = "Dear {$customerName}, thank you for shopping at Happy Cow Animal Feeds. Invoice: {$invoiceNumber}, Total: KSh {$total}. Tel: 0797251258";
        return $this->sendSMS($phone, $message);
    }
    
    public function sendLowStockAlert($phone, $productName, $currentStock) {
        $message = "LOW STOCK ALERT: {$productName} is running low. Current stock: {$currentStock}. Please reorder soon. - Happy Cow Animal Feeds";
        return $this->sendSMS($phone, $message);
    }
    
    public function sendCreditReminder($phone, $customerName, $balance) {
        $message = "Dear {$customerName}, you have an outstanding balance of KSh {$balance} at Happy Cow Animal Feeds. Please clear your account. Tel: 0797251258";
        return $this->sendSMS($phone, $message);
    }
    
    private function formatPhoneNumber($phone) {
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add Kenya code if not present
        if (substr($phone, 0, 1) === '0') {
            $phone = '254' . substr($phone, 1);
        } elseif (substr($phone, 0, 3) !== '254') {
            $phone = '254' . $phone;
        }
        
        return '+' . $phone;
    }
    
    private function logSMS($to, $message, $status, $response) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("
                INSERT INTO sms_log (recipient, message, status, response, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$to, $message, $status, $response]);
        } catch (PDOException $e) {
            error_log("SMS Log Error: " . $e->getMessage());
        }
    }
    
    public function getBalance() {
        // Get account balance from Africa's Talking
        if (empty($this->apiKey) || empty($this->username)) {
            return null;
        }
        
        $url = "https://api.africastalking.com/version1/user?username=" . $this->username;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['apiKey: ' . $this->apiKey]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        return $result['UserData']['balance'] ?? null;
    }
}
