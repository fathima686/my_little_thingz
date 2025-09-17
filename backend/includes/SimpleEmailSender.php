<?php
// Robust email sender with proper SMTP handling and clear logging
// - Logs to project root email_log.txt
// - Returns accurate success/failure
// - Works with Gmail SMTP (app password required)

class SimpleEmailSender {
    private $config;
    private $logFile;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/email.php';
        // Ensure logs always go to project root email_log.txt
        $this->logFile = realpath(__DIR__ . '/../../') . DIRECTORY_SEPARATOR . 'email_log.txt';
    }

    public function sendPaymentSuccessEmail($user_email, $user_name, $order_details) {
        $subject = 'Payment Successful - Order #' . ($order_details['order_number'] ?? '');
        $message = $this->getPaymentSuccessTemplate($user_name, $order_details);
        return $this->sendEmail($user_email, $user_name, $subject, $message);
    }

    public function sendPaymentFailureEmail($user_email, $user_name, $order_details, $reason = 'Payment verification failed') {
        $subject = 'Payment Failed - Order #' . ($order_details['order_number'] ?? '');
        $message = $this->getPaymentFailureTemplate($user_name, $order_details, $reason);
        return $this->sendEmail($user_email, $user_name, $subject, $message);
    }

    // Supplier approval notification
    public function sendSupplierApprovalEmail($user_email, $user_name) {
        $subject = 'Your Supplier Account Has Been Approved!';
        $message = $this->getSupplierApprovalTemplate($user_name);
        return $this->sendEmail($user_email, $user_name, $subject, $message);
    }

    // Generic email sender for auth/reset flows
    public function sendGenericEmail(string $to_email, string $to_name, string $subject, string $message): bool {
        $timestamp = date('Y-m-d H:i:s');
        $preview = substr(strip_tags($message), 0, 200);
        $this->log("[{$timestamp}] QUEUE EMAIL\nTo: {$to_name} <{$to_email}>\nFrom: {$this->config['from_name']} <{$this->config['from_email']}>\nSubject: {$subject}\nPreview: {$preview}...\n---\n");

        $sent = false;
        $errors = [];
        if (!empty($this->config['use_smtp'])) {
            $this->log('[DEBUG] Attempting SMTP send (generic)...');
            $sent = $this->sendViaSMTP($to_email, $to_name, $subject, $message, $errors);
        } else {
            $this->log('[DEBUG] SMTP disabled, attempting PHP mail() (generic).');
        }
        if (!$sent && empty($this->config['use_smtp'])) {
            $this->log('[DEBUG] Trying PHP mail() fallback (generic)...');
            $sent = $this->sendViaSimpleMethod($to_email, $subject, $message, $errors);
        }
        if ($sent) { $this->log('[INFO] Email sent successfully (generic).'); return true; }
        foreach ($errors as $err) { $this->log('[ERROR] ' . $err); }
        return false;
    }

    private function sendEmail($to_email, $to_name, $subject, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $preview = substr(strip_tags($message), 0, 200);
        $this->log("[{$timestamp}] QUEUE EMAIL\nTo: {$to_name} <{$to_email}>\nFrom: {$this->config['from_name']} <{$this->config['from_email']}>\nSubject: {$subject}\nPreview: {$preview}...\n---\n");

        $sent = false;
        $errors = [];

        if (!empty($this->config['use_smtp'])) {
            $this->log('[DEBUG] Attempting SMTP send...');
            $sent = $this->sendViaSMTP($to_email, $to_name, $subject, $message, $errors);
        } else {
            $this->log('[DEBUG] SMTP disabled, attempting PHP mail().');
        }

        if (!$sent) {
            // Fallback only if SMTP disabled; mail() on Windows often fails without SMTP config
            if (empty($this->config['use_smtp'])) {
                $this->log('[DEBUG] Trying PHP mail() fallback...');
                $sent = $this->sendViaSimpleMethod($to_email, $subject, $message, $errors);
            }
        }

        if ($sent) {
            $this->log('[INFO] Email sent successfully.');
            return true;
        }

        if (!empty($errors)) {
            foreach ($errors as $err) { $this->log('[ERROR] ' . $err); }
        } else {
            $this->log('[ERROR] Email send failed for unknown reason.');
        }
        return false;
    }

    private function sendViaSMTP($to_email, $to_name, $subject, $message, array &$errors) {
        $host = $this->config['smtp_host'];
        $port = (int)$this->config['smtp_port'];
        $user = $this->config['smtp_username'];
        $pass = $this->config['smtp_password'];
        $enc  = strtolower($this->config['smtp_encryption'] ?? 'tls');

        $sock = @fsockopen($host, $port, $errno, $errstr, 30);
        if (!$sock) { $errors[] = "Connect failed: $errstr ($errno)"; return false; }

        $read = function() use ($sock) {
            $data = '';
            // Read multiline responses
            while ($line = fgets($sock, 512)) {
                $data .= $line;
                if (preg_match('/^[0-9]{3} [\s\S]*/', $line)) break; // line starting with code and space means last
                if (!preg_match('/^[0-9]{3}-/', $line)) break;
            }
            return $data;
        };
        $write = function($cmd) use ($sock) { fputs($sock, $cmd . "\r\n"); };
        $expect = function($resp, $want) { return strpos($resp, (string)$want) === 0; };

        $resp = $read();
        if (!$expect($resp, '220')) { $errors[] = '220 not received: ' . trim($resp); fclose($sock); return false; }

        $ehloHost = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $write('EHLO ' . $ehloHost);
        $resp = $read();
        if (!$expect($resp, '250')) { $errors[] = 'EHLO failed: ' . trim($resp); fclose($sock); return false; }

        if ($enc === 'tls') {
            $write('STARTTLS');
            $resp = $read();
            if (!$expect($resp, '220')) { $errors[] = 'STARTTLS failed: ' . trim($resp); fclose($sock); return false; }
            if (!@stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $errors[] = 'TLS negotiation failed'; fclose($sock); return false;
            }
            $write('EHLO ' . $ehloHost);
            $resp = $read();
            if (!$expect($resp, '250')) { $errors[] = 'EHLO after TLS failed: ' . trim($resp); fclose($sock); return false; }
        }

        $write('AUTH LOGIN');
        $resp = $read();
        if (!$expect($resp, '334')) { $errors[] = 'AUTH LOGIN not accepted: ' . trim($resp); fclose($sock); return false; }

        $write(base64_encode($user));
        $resp = $read();
        if (!$expect($resp, '334')) { $errors[] = 'Username not accepted: ' . trim($resp); fclose($sock); return false; }

        $write(base64_encode($pass));
        $resp = $read();
        if (!$expect($resp, '235')) { $errors[] = 'Password not accepted: ' . trim($resp); fclose($sock); return false; }

        $write('MAIL FROM:<'.$this->config['from_email'].'>');
        $resp = $read();
        if (!$expect($resp, '250')) { $errors[] = 'MAIL FROM failed: ' . trim($resp); fclose($sock); return false; }

        $write('RCPT TO:<'.$to_email.'>');
        $resp = $read();
        if (!$expect($resp, '250')) { $errors[] = 'RCPT TO failed: ' . trim($resp); fclose($sock); return false; }

        $write('DATA');
        $resp = $read();
        if (!$expect($resp, '354')) { $errors[] = 'DATA not accepted: ' . trim($resp); fclose($sock); return false; }

        // Headers
        $headers  = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . ">\r\n";
        $headers .= 'To: ' . $to_name . ' <' . $to_email . ">\r\n";
        $headers .= 'Subject: ' . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion() . "\r\n";

        $write($headers . "\r\n" . $message . "\r\n.\r\n");
        $resp = $read();
        if (!$expect($resp, '250')) { $errors[] = 'Message not accepted: ' . trim($resp); fclose($sock); return false; }

        $write('QUIT');
        fclose($sock);
        return true;
    }

    private function sendViaSimpleMethod($to_email, $subject, $message, array &$errors) {
        $headers  = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . ">\r\n";
        $headers .= 'Reply-To: ' . $this->config['from_email'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $ok = @mail($to_email, $subject, $message, $headers);
        if (!$ok) { $errors[] = 'mail() returned false'; }
        return $ok;
    }

    private function log($line) {
        // Ensure directory exists and write log
        @file_put_contents($this->logFile, $line . (str_ends_with($line, "\n") ? '' : "\n"), FILE_APPEND | LOCK_EX);
    }

    private function getPaymentSuccessTemplate($user_name, $order_details) {
        $html  = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Payment Successful</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#6b46c1;color:#fff;padding:20px;text-align:center;border-radius:8px 8px 0 0}.content{padding:20px;background:#f9f9f9;border-radius:0 0 8px 8px}.success{color:#28a745;font-weight:bold;font-size:18px}.order-details{background:#fff;padding:15px;margin:15px 0;border-radius:5px;border-left:4px solid #28a745}.items-list{margin:10px 0}.item{padding:10px;margin:5px 0;background:#f8f9fa;border-radius:3px;border-left:3px solid #6b46c1}.item-info{font-size:14px}.order-summary{background:#e9ecef;padding:10px;margin:10px 0;border-radius:3px}.order-summary .total{font-size:16px;font-weight:bold;color:#28a745;margin-top:10px;padding-top:10px;border-top:1px solid #dee2e6}.shipping-info{background:#f8f9fa;padding:10px;margin:10px 0;border-radius:3px;border-left:3px solid #17a2b8}.footer{text-align:center;padding:20px;color:#666}.button{background:#6b46c1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 0}</style></head><body>";
        $html .= "<div class='container'><div class='header'><h1>🎉 Payment Successful!</h1><p>Thank you for your purchase</p></div><div class='content'>";
        $html .= "<p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p><p class='success'>Your payment has been successfully processed!</p>";
        $html .= "<div class='order-details'><h3>📋 Order Details:</h3>";
        $html .= "<p><strong>Order Number:</strong> " . htmlspecialchars($order_details['order_number'] ?? '') . "</p>";
        if (!empty($order_details['created_at'])) {
            $html .= "<p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A', strtotime($order_details['created_at'])) . "</p>";
        }
        $html .= "<p><strong>Payment Method:</strong> Razorpay</p><p><strong>Order Status:</strong> Processing</p>";

        // Items
        $html .= "<h4>🛍️ Items Ordered:</h4><div class='items-list'>";
        if (!empty($order_details['items']) && is_array($order_details['items'])) {
            foreach ($order_details['items'] as $item) {
                $name = htmlspecialchars($item['artwork_name'] ?? 'Item');
                $qty  = (int)($item['quantity'] ?? 1);
                $price = (float)($item['price'] ?? 0);
                $line  = number_format($qty * $price, 2);
                $html .= "<div class='item'><div class='item-info'><strong>{$name}</strong><br>Quantity: {$qty} × ₹" . number_format($price, 2) . " = ₹{$line}</div></div>";
            }
        }
        $html .= "</div>"; // items-list

        // Summary
        $html .= "<div class='order-summary'><h4>💰 Order Summary:</h4>";
        if (!empty($order_details['subtotal'])) { $html .= "<p><strong>Subtotal:</strong> ₹" . number_format((float)$order_details['subtotal'], 2) . "</p>"; }
        if (!empty($order_details['tax_amount'])) { $html .= "<p><strong>Tax:</strong> ₹" . number_format((float)$order_details['tax_amount'], 2) . "</p>"; }
        if (!empty($order_details['shipping_cost'])) { $html .= "<p><strong>Shipping:</strong> ₹" . number_format((float)$order_details['shipping_cost'], 2) . "</p>"; }
        $html .= "<p class='total'><strong>Total Amount:</strong> ₹" . number_format((float)($order_details['total_amount'] ?? 0), 2) . "</p></div>";

        // Address
        if (!empty($order_details['shipping_address'])) {
            $html .= "<div class='shipping-info'><h4>🚚 Shipping Address:</h4><p>" . nl2br(htmlspecialchars($order_details['shipping_address'])) . "</p></div>";
        }

        $html .= "<p>Thank you for choosing My Little Thingz! We'll process your order and send you updates on the shipping status.</p><p>If you have any questions, please don't hesitate to contact us.</p><a href='#' class='button'>Track Your Order</a></div><div class='footer'><p>Best regards,<br><strong>My Little Thingz Team</strong></p><p>This is an automated message. Please do not reply to this email.</p></div></div></body></html>";
        return $html;
    }

    private function getPaymentFailureTemplate($user_name, $order_details, $reason) {
        $html  = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Payment Failed</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#dc3545;color:#fff;padding:20px;text-align:center;border-radius:8px 8px 0 0}.content{padding:20px;background:#f9f9f9;border-radius:0 0 8px 8px}.error{color:#dc3545;font-weight:bold;font-size:18px}.order-details{background:#fff;padding:15px;margin:15px 0;border-radius:5px;border-left:4px solid #dc3545}.footer{text-align:center;padding:20px;color:#666}.button{background:#6b46c1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 0}</style></head><body>";
        $html .= "<div class='container'><div class='header'><h1>❌ Payment Failed</h1><p>We're here to help</p></div><div class='content'>";
        $html .= "<p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p><p class='error'>Unfortunately, your payment could not be processed.</p>";
        $html .= "<div class='order-details'><h3>Order Details:</h3>";
        $html .= "<p><strong>Order Number:</strong> " . htmlspecialchars($order_details['order_number'] ?? '') . "</p>";
        $html .= "<p><strong>Total Amount:</strong> ₹" . number_format((float)($order_details['total_amount'] ?? 0), 2) . "</p>";
        $html .= "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>";
        $html .= "</div><p>Please try again or contact us if you continue to experience issues.</p><p>Your items are still in your cart and you can retry the payment process.</p></div><div class='footer'><p>Best regards,<br><strong>My Little Thingz Team</strong></p></div></div></body></html>";
        return $html;
    }

    private function getSupplierApprovalTemplate($user_name) {
        $html  = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Supplier Approved</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;line-height:1.6;color:#333;margin:0;padding:0}.container{max-width:600px;margin:0 auto;padding:20px}.header{background:#28a745;color:#fff;padding:20px;text-align:center;border-radius:8px 8px 0 0}.content{padding:20px;background:#f9f9f9;border-radius:0 0 8px 8px}.footer{text-align:center;padding:20px;color:#666}.button{background:#6b46c1;color:#fff;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:10px 0}</style></head><body>";
        $html .= "<div class='container'><div class='header'><h1>✅ Supplier Approval</h1><p>Welcome aboard!</p></div><div class='content'>";
        $html .= "<p>Dear <strong>" . htmlspecialchars($user_name) . "</strong>,</p>";
        $html .= "<p>Great news! Your supplier account has been <strong>approved</strong>. You can now sign in and start managing your products and inventory.</p>";
        $html .= "<p>Next steps:</p><ul><li>Log in to your account</li><li>Add or update your products</li><li>Keep your inventory up-to-date</li></ul>";
        $html .= "<p><a href='http://localhost:5173' class='button'>Go to Dashboard</a></p>";
        $html .= "</div><div class='footer'><p>Best regards,<br><strong>My Little Thingz Team</strong></p></div></div></body></html>";
        return $html;
    }
}
