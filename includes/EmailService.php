<?php
/**
 * Email Service using PHPMailer
 * Handles sending professional email receipts and notifications
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $conn;
    private $mail;
    private $enabled;
    private $fromEmail;
    private $fromName;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->mail = new PHPMailer(true);
        $this->loadSettings();
        $this->configureSMTP();
    }
    
    private function loadSettings() {
        $result = $this->conn->query("SELECT * FROM email_settings LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->enabled = $settings['enabled'];
            $this->fromEmail = $settings['from_email'];
            $this->fromName = $settings['from_name'];
            
            if ($this->enabled) {
                $this->mail->Host = $settings['smtp_host'];
                $this->mail->Port = $settings['smtp_port'];
                $this->mail->Username = $settings['smtp_username'];
                $this->mail->Password = $settings['smtp_password'];
                $this->mail->SMTPSecure = $settings['smtp_encryption'];
            }
        }
    }
    
    private function configureSMTP() {
        if (!$this->enabled) return;
        
        $this->mail->isSMTP();
        $this->mail->SMTPAuth = true;
        $this->mail->setFrom($this->fromEmail, $this->fromName);
        $this->mail->isHTML(true);
    }
    
    /**
     * Send email receipt
     */
    public function sendReceipt($toEmail, $customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod) {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Email service is not enabled'];
        }
        
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $customerName);
            $this->mail->Subject = "Receipt #{$invoiceNumber} - Happy Cow Animal Feeds";
            
            $html = $this->generateReceiptHTML($customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod);
            $this->mail->Body = $html;
            $this->mail->AltBody = strip_tags($html);
            
            $this->mail->send();
            $this->logEmail($toEmail, 'Receipt', 'sent', $invoiceNumber);
            
            return ['success' => true, 'message' => 'Receipt sent successfully'];
        } catch (Exception $e) {
            $this->logEmail($toEmail, 'Receipt', 'failed', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate HTML receipt
     */
    private function generateReceiptHTML($customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod) {
        $date = date('F d, Y - h:i A');
        
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemsHTML .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['total'], 2) . "</td>
            </tr>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #fff; padding: 30px; border: 1px solid #ddd; border-top: none; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; border-radius: 0 0 10px 10px; font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background: #f8f9fa; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; }
                .totals { margin-top: 20px; }
                .totals table { margin: 0; }
                .totals td { padding: 8px; border: none; }
                .total-row { font-size: 18px; font-weight: bold; background: #f8f9fa; }
                .badge { display: inline-block; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold; }
                .badge-success { background: #d4edda; color: #155724; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0; font-size: 28px;'>🌾 Happy Cow Animal Feeds</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Official Receipt</p>
                </div>
                
                <div class='content'>
                    <div style='margin-bottom: 30px;'>
                        <h2 style='color: #667eea; margin: 0 0 10px 0;'>Receipt #{$invoiceNumber}</h2>
                        <p style='margin: 5px 0; color: #666;'><strong>Date:</strong> {$date}</p>
                        <p style='margin: 5px 0; color: #666;'><strong>Customer:</strong> {$customerName}</p>
                        <p style='margin: 5px 0; color: #666;'><strong>Payment Method:</strong> <span class='badge badge-success'>{$paymentMethod}</span></p>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th style='text-align: center;'>Qty</th>
                                <th style='text-align: right;'>Price</th>
                                <th style='text-align: right;'>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHTML}
                        </tbody>
                    </table>
                    
                    <div class='totals'>
                        <table>
                            <tr>
                                <td style='text-align: right; padding-right: 20px;'>Subtotal:</td>
                                <td style='text-align: right; width: 150px;'>KSh " . number_format($subtotal, 2) . "</td>
                            </tr>
                            <tr>
                                <td style='text-align: right; padding-right: 20px;'>Discount:</td>
                                <td style='text-align: right; color: #28a745;'>-KSh " . number_format($discount, 2) . "</td>
                            </tr>
                            <tr>
                                <td style='text-align: right; padding-right: 20px;'>Tax (16%):</td>
                                <td style='text-align: right;'>KSh " . number_format($tax, 2) . "</td>
                            </tr>
                            <tr class='total-row'>
                                <td style='text-align: right; padding: 15px 20px 15px 0;'>TOTAL:</td>
                                <td style='text-align: right; padding: 15px 0;'>KSh " . number_format($total, 2) . "</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style='margin-top: 30px; padding: 20px; background: #f8f9fa; border-left: 4px solid #667eea; border-radius: 5px;'>
                        <p style='margin: 0; color: #666;'><strong>Thank you for your business!</strong></p>
                        <p style='margin: 10px 0 0 0; font-size: 14px; color: #888;'>For any queries, please contact us with your receipt number.</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p style='margin: 0;'>This is a computer-generated receipt and does not require a signature.</p>
                    <p style='margin: 10px 0 0 0;'>© " . date('Y') . " Happy Cow Animal Feeds. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Send quotation email
     */
    public function sendQuotation($toEmail, $customerName, $quotationNumber, $items, $total, $validUntil) {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Email service is not enabled'];
        }
        
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($toEmail, $customerName);
            $this->mail->Subject = "Quotation #{$quotationNumber} - Happy Cow Animal Feeds";
            
            // Similar HTML structure as receipt but for quotation
            $html = $this->generateQuotationHTML($customerName, $quotationNumber, $items, $total, $validUntil);
            $this->mail->Body = $html;
            $this->mail->AltBody = strip_tags($html);
            
            $this->mail->send();
            $this->logEmail($toEmail, 'Quotation', 'sent', $quotationNumber);
            
            return ['success' => true, 'message' => 'Quotation sent successfully'];
        } catch (Exception $e) {
            $this->logEmail($toEmail, 'Quotation', 'failed', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate quotation HTML
     */
    private function generateQuotationHTML($customerName, $quotationNumber, $items, $total, $validUntil) {
        // Similar to receipt HTML but customized for quotation
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemsHTML .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$item['name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$item['quantity']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['total'], 2) . "</td>
            </tr>";
        }
        
        return "<!DOCTYPE html><html><body><h2>Quotation #{$quotationNumber}</h2><p>Valid until: {$validUntil}</p><table>{$itemsHTML}</table><p><strong>Total: KSh " . number_format($total, 2) . "</strong></p></body></html>";
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $type, $status, $reference) {
        $stmt = $this->conn->prepare("INSERT INTO email_log (recipient, type, status, reference, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $to, $type, $status, $reference);
        return $stmt->execute();
    }
}
?>
