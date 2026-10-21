<?php
/**
 * Email service using the SMTP configuration saved in Email Settings
 * Handles sending professional email receipts and notifications
 */

class EmailService {
    private $conn;
    private $settings = null;
    private $enabled = false;
    private $sendReceipts = false;
    private $sendQuotations = false;
    private $fromEmail = '';
    private $fromName = '';
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        $this->loadSettings();
    }

    public function canSendReceipt() {
        return $this->enabled && $this->sendReceipts;
    }

    public function canSendQuotation() {
        return $this->enabled && $this->sendQuotations;
    }
    
    private function loadSettings() {
        $result = $this->conn->query("SELECT * FROM email_settings ORDER BY id LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $settings = $result->fetch_assoc();
            $this->settings = $settings;
            $this->enabled = (bool) $settings['enabled'];
            $this->sendReceipts = (bool) $settings['send_receipts'];
            $this->sendQuotations = (bool) $settings['send_quotations'];
            $this->fromEmail = $settings['from_email'];
            $this->fromName = $settings['from_name'];
        }
    }
    
    /**
     * Send email receipt
     */
    public function sendReceipt($toEmail, $customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod) {
        if (!$this->enabled || !$this->sendReceipts) {
            return ['success' => false, 'message' => 'Email receipts are disabled in Email Settings'];
        }
        
        try {
            $html = $this->generateReceiptHTML($customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod);
            $this->sendHtmlEmail($toEmail, $customerName, "Receipt #{$invoiceNumber} - Happy Cow Animal Feeds", $html);
            $this->logEmail($toEmail, 'Receipt', 'sent', $invoiceNumber);
            
            return ['success' => true, 'message' => 'Receipt sent successfully'];
        } catch (Throwable $e) {
            $this->logEmail($toEmail, 'Receipt', 'failed', $invoiceNumber, $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send receipt email: ' . $e->getMessage()];
        }
    }
    
    /**
     * Generate HTML receipt
     */
    private function generateReceiptHTML($customerName, $invoiceNumber, $items, $subtotal, $tax, $discount, $total, $paymentMethod) {
        $date = date('F d, Y - h:i A');
        $customerName = htmlspecialchars((string) $customerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $invoiceNumber = htmlspecialchars((string) $invoiceNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $paymentMethod = htmlspecialchars((string) $paymentMethod, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemName = htmlspecialchars((string) ($item['name'] ?? 'Product'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 0);
            $itemsHTML .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$itemName}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$quantity}</td>
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
                                <td style='text-align: right; padding-right: 20px;'>Tax:</td>
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
        if (!$this->enabled || !$this->sendQuotations) {
            return ['success' => false, 'message' => 'Quotation emails are disabled in Email Settings'];
        }
        
        try {
            // Similar HTML structure as receipt but for quotation
            $html = $this->generateQuotationHTML($customerName, $quotationNumber, $items, $total, $validUntil);
            $this->sendHtmlEmail($toEmail, $customerName, "Quotation #{$quotationNumber} - Happy Cow Animal Feeds", $html);
            $this->logEmail($toEmail, 'Quotation', 'sent', $quotationNumber);
            
            return ['success' => true, 'message' => 'Quotation sent successfully'];
        } catch (Throwable $e) {
            $this->logEmail($toEmail, 'Quotation', 'failed', $quotationNumber, $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send quotation email: ' . $e->getMessage()];
        }
    }

    /** Send a MIME HTML email over the SMTP settings saved by the administrator. */
    private function sendHtmlEmail($toEmail, $toName, $subject, $html) {
        if (!$this->settings || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid customer email address is required');
        }
        $settings = $this->settings;
        $host = trim((string) $settings['smtp_host']);
        $port = (int) $settings['smtp_port'];
        $username = (string) $settings['smtp_username'];
        $password = (string) $settings['smtp_password'];
        $encryption = strtolower((string) $settings['smtp_encryption']);
        if ($host === '' || $port < 1 || $username === '' || $password === '') {
            throw new RuntimeException('SMTP settings are incomplete');
        }

        $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host
        ]]);
        $socket = @stream_socket_client($target, $errorNumber, $errorMessage, 20, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new RuntimeException('Could not connect to the SMTP server: ' . ($errorMessage ?: 'connection failed'));
        }
        stream_set_timeout($socket, 20);

        try {
            $this->expectSmtpReply($socket, [220]);
            $this->smtpCommand($socket, 'EHLO localhost', [250]);
            if ($encryption === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);
                $cryptoEnabled = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($cryptoEnabled !== true) {
                    throw new RuntimeException('Could not start a secure SMTP connection. Check the local PHP CA certificate setup.');
                }
                $this->smtpCommand($socket, 'EHLO localhost', [250]);
            } elseif ($encryption !== 'ssl') {
                throw new RuntimeException('Unsupported SMTP encryption. Choose TLS or SSL.');
            }

            $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
            $this->smtpCommand($socket, base64_encode($username), [334]);
            $this->smtpCommand($socket, base64_encode($password), [235]);
            $this->smtpCommand($socket, 'MAIL FROM:<' . $this->cleanHeader($this->fromEmail) . '>', [250]);
            $recipient = $this->cleanHeader($toEmail);
            $this->smtpCommand($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $boundary = 'hc_' . bin2hex(random_bytes(12));
            $encodedName = $this->encodeHeader($this->fromName);
            $encodedToName = $this->encodeHeader($toName);
            $encodedSubject = $this->encodeHeader($subject);
            $plainText = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'From: ' . $encodedName . ' <' . $this->cleanHeader($this->fromEmail) . '>',
                'To: ' . $encodedToName . ' <' . $recipient . '>',
                'Subject: ' . $encodedSubject,
                'MIME-Version: 1.0',
                'Content-Type: multipart/alternative; boundary="' . $boundary . '"'
            ];
            $body = implode("\r\n", $headers) . "\r\n\r\n";
            $body .= '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($plainText));
            $body .= '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html));
            $body .= '--' . $boundary . "--\r\n.";
            $body = preg_replace('/\r?\n/', "\r\n", $body);
            if (fwrite($socket, $body . "\r\n") === false) {
                throw new RuntimeException('Could not write the email to the SMTP server');
            }
            $this->expectSmtpReply($socket, [250]);
            fwrite($socket, "QUIT\r\n");
        } finally {
            fclose($socket);
        }
    }

    private function smtpCommand($socket, $command, $expectedCodes) {
        if (fwrite($socket, $command . "\r\n") === false) {
            throw new RuntimeException('SMTP connection closed while sending a command');
        }
        return $this->expectSmtpReply($socket, $expectedCodes);
    }

    private function expectSmtpReply($socket, $expectedCodes) {
        $reply = '';
        do {
            $line = fgets($socket, 2048);
            if ($line === false) {
                $meta = stream_get_meta_data($socket);
                throw new RuntimeException(!empty($meta['timed_out']) ? 'SMTP server timed out' : 'SMTP server closed the connection');
            }
            $reply .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            $safeReply = trim(preg_replace('/\s+/', ' ', $reply));
            throw new RuntimeException('SMTP server replied: ' . substr($safeReply, 0, 300));
        }
        return $reply;
    }

    private function encodeHeader($value) {
        $value = $this->cleanHeader((string) $value);
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function cleanHeader($value) {
        return trim(str_replace(["\r", "\n", '<', '>'], '', (string) $value));
    }
    
    /**
     * Generate quotation HTML
     */
    private function generateQuotationHTML($customerName, $quotationNumber, $items, $total, $validUntil) {
        // Similar to receipt HTML but customized for quotation
        $customerName = htmlspecialchars((string) $customerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $quotationNumber = htmlspecialchars((string) $quotationNumber, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $validUntil = htmlspecialchars((string) $validUntil, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $itemsHTML = '';
        foreach ($items as $item) {
            $itemName = htmlspecialchars((string) ($item['name'] ?? 'Product'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 0);
            $itemsHTML .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$itemName}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>{$quantity}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['price'], 2) . "</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: right;'>KSh " . number_format($item['total'], 2) . "</td>
            </tr>";
        }
        
        return "<!DOCTYPE html><html><body><h2>Quotation #{$quotationNumber}</h2><p>Valid until: {$validUntil}</p><table>{$itemsHTML}</table><p><strong>Total: KSh " . number_format($total, 2) . "</strong></p></body></html>";
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $type, $status, $reference, $errorMessage = null) {
        $stmt = $this->conn->prepare("INSERT INTO email_log (recipient, type, status, reference, error_message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $to, $type, $status, $reference, $errorMessage);
        return $stmt->execute();
    }
}
?>
