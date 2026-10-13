<?php
/**
 * M-Pesa Daraja API Integration Service
 * Handles STK Push, payment verification, and callback processing
 */

class MpesaService {
    private $conn;
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $passkey;
    private $callbackUrl;
    private $environment; // 'sandbox' or 'live'
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
    }
    
    private function loadSettings() {
        $result = $this->conn->query("SELECT * FROM mpesa_settings LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->consumerKey = $settings['consumer_key'];
            $this->consumerSecret = $settings['consumer_secret'];
            $this->shortcode = $settings['shortcode'];
            $this->passkey = $settings['passkey'];
            $this->callbackUrl = $settings['callback_url'];
            $this->environment = $settings['environment'];
        }
    }
    
    /**
     * Generate access token for API authentication
     */
    private function generateAccessToken() {
        $url = $this->environment === 'live' 
            ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_USERPWD, $this->consumerKey . ':' . $this->consumerSecret);
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($status == 200) {
            $result = json_decode($response);
            return $result->access_token;
        }
        
        return null;
    }
    
    /**
     * Initiate STK Push payment request
     */
    public function stkPush($phone, $amount, $accountReference, $description = 'Payment') {
        $accessToken = $this->generateAccessToken();
        
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }
        
        $url = $this->environment === 'live'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
        
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        // Format phone number
        $phone = $this->formatPhoneNumber($phone);
        
        $data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $description
        ];
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization:Bearer ' . $accessToken
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $result = json_decode($response, true);
        
        if ($status == 200 && isset($result['ResponseCode']) && $result['ResponseCode'] == '0') {
            // Log the transaction
            $this->logTransaction(
                $result['CheckoutRequestID'],
                $phone,
                $amount,
                $accountReference,
                'pending',
                json_encode($result)
            );
            
            return [
                'success' => true,
                'message' => 'STK push sent successfully',
                'checkoutRequestId' => $result['CheckoutRequestID'],
                'merchantRequestId' => $result['MerchantRequestID']
            ];
        }
        
        return [
            'success' => false,
            'message' => $result['errorMessage'] ?? 'Failed to initiate payment',
            'response' => $result
        ];
    }
    
    /**
     * Query transaction status
     */
    public function queryTransaction($checkoutRequestId) {
        $accessToken = $this->generateAccessToken();
        
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to generate access token'];
        }
        
        $url = $this->environment === 'live'
            ? 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
        
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        
        $data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId
        ];
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type:application/json',
            'Authorization:Bearer ' . $accessToken
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        return json_decode($response, true);
    }
    
    /**
     * Process M-Pesa callback
     */
    public function processCallback($callbackData) {
        $checkoutRequestId = $callbackData['Body']['stkCallback']['CheckoutRequestID'];
        $resultCode = $callbackData['Body']['stkCallback']['ResultCode'];
        $resultDesc = $callbackData['Body']['stkCallback']['ResultDesc'];
        
        if ($resultCode == 0) {
            // Payment successful
            $items = $callbackData['Body']['stkCallback']['CallbackMetadata']['Item'];
            $mpesaReceiptNumber = '';
            $amount = 0;
            $phone = '';
            
            foreach ($items as $item) {
                if ($item['Name'] == 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item['Value'];
                } elseif ($item['Name'] == 'Amount') {
                    $amount = $item['Value'];
                } elseif ($item['Name'] == 'PhoneNumber') {
                    $phone = $item['Value'];
                }
            }
            
            // Update transaction status
            $stmt = $this->conn->prepare("UPDATE mpesa_transactions SET status = 'completed', mpesa_receipt_number = ?, response = ?, updated_at = NOW() WHERE checkout_request_id = ?");
            $response = json_encode($callbackData);
            $stmt->bind_param("sss", $mpesaReceiptNumber, $response, $checkoutRequestId);
            $stmt->execute();
            
            return [
                'success' => true,
                'receipt' => $mpesaReceiptNumber,
                'amount' => $amount,
                'phone' => $phone
            ];
        } else {
            // Payment failed
            $stmt = $this->conn->prepare("UPDATE mpesa_transactions SET status = 'failed', response = ?, updated_at = NOW() WHERE checkout_request_id = ?");
            $response = json_encode($callbackData);
            $stmt->bind_param("ss", $response, $checkoutRequestId);
            $stmt->execute();
            
            return [
                'success' => false,
                'message' => $resultDesc
            ];
        }
    }
    
    /**
     * Log M-Pesa transaction
     */
    private function logTransaction($checkoutRequestId, $phone, $amount, $accountReference, $status, $response) {
        $stmt = $this->conn->prepare("INSERT INTO mpesa_transactions (checkout_request_id, phone_number, amount, account_reference, status, response, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sdssss", $checkoutRequestId, $phone, $amount, $accountReference, $status, $response);
        return $stmt->execute();
    }
    
    /**
     * Format phone number to 254 format
     */
    private function formatPhoneNumber($phone) {
        // Remove spaces and special characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to 254 format
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
     * Get transaction by checkout request ID
     */
    public function getTransaction($checkoutRequestId) {
        $stmt = $this->conn->prepare("SELECT * FROM mpesa_transactions WHERE checkout_request_id = ?");
        $stmt->bind_param("s", $checkoutRequestId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Get all transactions with filters
     */
    public function getTransactions($filters = []) {
        $sql = "SELECT * FROM mpesa_transactions WHERE 1=1";
        
        if (isset($filters['status'])) {
            $sql .= " AND status = '" . $this->conn->real_escape_string($filters['status']) . "'";
        }
        
        if (isset($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= '" . $this->conn->real_escape_string($filters['date_from']) . "'";
        }
        
        if (isset($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= '" . $this->conn->real_escape_string($filters['date_to']) . "'";
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $result = $this->conn->query($sql);
        $transactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        return $transactions;
    }
}
?>
