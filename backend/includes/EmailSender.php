<?php
// Email sending utility class
require_once __DIR__ . '/../config/email.php';

class EmailSender {
    private $config;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/email.php';
    }
    
    public function sendPaymentSuccessEmail($user_email, $user_name, $order_details) {
        $subject = "Payment Successful - Order #" . $order_details['order_number'];
        
        $message = $this->getPaymentSuccessTemplate($user_name, $order_details);
        
        return $this->sendEmail($user_email, $user_name, $subject, $message);
    }
    
    public function sendPaymentFailureEmail($user_email, $user_name, $order_details, $reason = 'Payment verification failed') {
        $subject = "Payment Failed - Order #" . $order_details['order_number'];
        
        $message = $this->getPaymentFailureTemplate($user_name, $order_details, $reason);
        
        return $this->sendEmail($user_email, $user_name, $subject, $message);
    }
    
    private function sendEmail($to_email, $to_name, $subject, $message) {
        if ($this->config['use_smtp']) {
            return $this->sendViaSMTP($to_email, $to_name, $subject, $message);
        } else {
            return $this->sendViaPHP($to_email, $subject, $message);
        }
    }
    
    private function sendViaSMTP($to_email, $to_name, $subject, $message) {
        try {
            // Simple SMTP implementation using fsockopen
            $smtp = fsockopen($this->config['smtp_host'], $this->config['smtp_port'], $errno, $errstr, 30);
            if (!$smtp) {
                throw new Exception("SMTP connection failed: $errstr ($errno)");
            }
            
            // Read initial response
            $response = fgets($smtp, 512);
            
            // EHLO command
            fputs($smtp, "EHLO localhost\r\n");
            $response = fgets($smtp, 512);
            
            // Start TLS
            fputs($smtp, "STARTTLS\r\n");
            $response = fgets($smtp, 512);
            
            // Enable crypto
            stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            
            // EHLO again after TLS
            fputs($smtp, "EHLO localhost\r\n");
            $response = fgets($smtp, 512);
            
            // AUTH LOGIN
            fputs($smtp, "AUTH LOGIN\r\n");
            $response = fgets($smtp, 512);
            
            // Send username
            fputs($smtp, base64_encode($this->config['smtp_username']) . "\r\n");
            $response = fgets($smtp, 512);
            
            // Send password
            fputs($smtp, base64_encode($this->config['smtp_password']) . "\r\n");
            $response = fgets($smtp, 512);
            
            // MAIL FROM
            fputs($smtp, "MAIL FROM: <" . $this->config['from_email'] . ">\r\n");
            $response = fgets($smtp, 512);
            
            // RCPT TO
            fputs($smtp, "RCPT TO: <" . $to_email . ">\r\n");
            $response = fgets($smtp, 512);
            
            // DATA
            fputs($smtp, "DATA\r\n");
            $response = fgets($smtp, 512);
            
            // Email headers and body
            $headers = "From: " . $this->config['from_name'] . " <" . $this->config['from_email'] . ">\r\n";
            $headers .= "Reply-To: " . $this->config['from_email'] . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            fputs($smtp, $headers . "\r\n" . $message . "\r\n.\r\n");
            $response = fgets($smtp, 512);
            
            // QUIT
            fputs($smtp, "QUIT\r\n");
            fclose($smtp);
            
            return true;
        } catch (Exception $e) {
            error_log("SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendViaPHP($to_email, $subject, $message) {
        $headers = "From: " . $this->config['from_name'] . " <" . $this->config['from_email'] . ">\r\n";
        $headers .= "Reply-To: " . $this->config['from_email'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to_email, $subject, $message, $headers);
    }
    
    private function getPaymentSuccessTemplate($user_name, $order_details) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Successful</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #6b46c1; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .success { color: #28a745; font-weight: bold; }
                .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Payment Successful!</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>$user_name</strong>,</p>
                    <p class='success'>Your payment has been successfully processed!</p>
                    
                    <div class='order-details'>
                        <h3>Order Details:</h3>
                        <p><strong>Order Number:</strong> " . $order_details['order_number'] . "</p>
                        <p><strong>Total Amount:</strong> ₹" . number_format($order_details['total_amount'], 2) . "</p>
                        <p><strong>Payment Method:</strong> Razorpay</p>
                        <p><strong>Order Status:</strong> Processing</p>
                        <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    </div>
                    
                    <p>Thank you for your purchase! We'll process your order and send you updates on the shipping status.</p>
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>My Little Thingz Team</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    private function getPaymentFailureTemplate($user_name, $order_details, $reason) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Payment Failed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .error { color: #dc3545; font-weight: bold; }
                .order-details { background: white; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { text-align: center; padding: 20px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Payment Failed</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>$user_name</strong>,</p>
                    <p class='error'>Unfortunately, your payment could not be processed.</p>
                    
                    <div class='order-details'>
                        <h3>Order Details:</h3>
                        <p><strong>Order Number:</strong> " . $order_details['order_number'] . "</p>
                        <p><strong>Total Amount:</strong> ₹" . number_format($order_details['total_amount'], 2) . "</p>
                        <p><strong>Reason:</strong> $reason</p>
                        <p><strong>Order Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    </div>
                    
                    <p>Please try again or contact us if you continue to experience issues.</p>
                    <p>Your items are still in your cart and you can retry the payment process.</p>
                </div>
                <div class='footer'>
                    <p>Best regards,<br>My Little Thingz Team</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
