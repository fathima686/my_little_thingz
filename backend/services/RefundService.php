<?php
/**
 * REFUND SERVICE
 * Handles refund processing and notifications for unboxing requests
 */

class RefundService {
    private $pdo;
    private $razorpayKeyId;
    private $razorpayKeySecret;
    
    public function __construct($database) {
        $this->pdo = $database->getConnection();
        
        // Load Razorpay credentials from environment
        $this->razorpayKeyId = $_ENV['RAZORPAY_KEY_ID'] ?? 'rzp_test_your_key_id';
        $this->razorpayKeySecret = $_ENV['RAZORPAY_KEY_SECRET'] ?? 'your_key_secret';
    }
    
    /**
     * Process refund for approved unboxing request
     */
    public function processRefund($requestId, $adminId, $adminNotes = '') {
        try {
            // Get request details with order and payment info
            $stmt = $this->pdo->prepare("
                SELECT ur.*, o.razorpay_payment_id, o.total_amount, o.order_number,
                       u.email as customer_email, u.first_name, u.last_name
                FROM unboxing_requests ur
                JOIN orders o ON ur.order_id = o.id
                JOIN users u ON ur.customer_id = u.id
                WHERE ur.id = ? AND ur.request_status = 'refund_approved'
            ");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                throw new Exception('Request not found or not approved for refund');
            }
            
            $refundResult = null;
            $refundId = null;
            $refundAmount = $request['total_amount'] * 100; // Convert to paise
            
            // Process Razorpay refund if payment ID exists
            if ($request['razorpay_payment_id']) {
                $refundResult = $this->createRazorpayRefund(
                    $request['razorpay_payment_id'], 
                    $refundAmount,
                    "Refund for unboxing issue - Order #{$request['order_number']}"
                );
                
                if ($refundResult && $refundResult['status'] === 'success') {
                    $refundId = $refundResult['refund_id'];
                }
            }
            
            // Update request with refund details
            $updateStmt = $this->pdo->prepare("
                UPDATE unboxing_requests 
                SET request_status = 'refund_processed',
                    refund_id = ?,
                    refund_amount = ?,
                    refund_processed_at = NOW(),
                    admin_notes = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $refundId,
                $request['total_amount'],
                $adminNotes,
                $requestId
            ]);
            
            // Add refund columns to table if they don't exist
            $this->ensureRefundColumns();
            
            // Send email notification to customer
            $this->sendRefundNotification($request, $refundResult);
            
            // Log the refund in history
            $this->logRefundHistory($requestId, $adminId, $refundResult);
            
            return [
                'status' => 'success',
                'message' => 'Refund processed successfully',
                'refund_id' => $refundId,
                'amount' => $request['total_amount']
            ];
            
        } catch (Exception $e) {
            error_log('Refund processing error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Create refund through Razorpay API
     */
    private function createRazorpayRefund($paymentId, $amount, $notes = '') {
        try {
            $url = "https://api.razorpay.com/v1/payments/{$paymentId}/refund";
            
            $data = [
                'amount' => $amount,
                'notes' => [
                    'reason' => $notes
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->razorpayKeyId . ':' . $this->razorpayKeySecret)
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $refundData = json_decode($response, true);
                return [
                    'status' => 'success',
                    'refund_id' => $refundData['id'],
                    'amount' => $refundData['amount'] / 100, // Convert back to rupees
                    'razorpay_response' => $refundData
                ];
            } else {
                $errorData = json_decode($response, true);
                throw new Exception('Razorpay refund failed: ' . ($errorData['error']['description'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log('Razorpay refund error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send email notification to customer about refund
     */
    private function sendRefundNotification($request, $refundResult) {
        $customerEmail = $request['customer_email'];
        $customerName = trim($request['first_name'] . ' ' . $request['last_name']);
        $orderNumber = $request['order_number'];
        $refundAmount = $request['total_amount'];
        
        $subject = "Refund Processed - Order #{$orderNumber}";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 8px 8px; }
                .refund-details { background: white; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #28a745; }
                .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                .amount { font-size: 24px; font-weight: bold; color: #28a745; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Refund Approved & Processed</h1>
                </div>
                <div class='content'>
                    <p>Dear {$customerName},</p>
                    
                    <p>Great news! Your refund request for order <strong>#{$orderNumber}</strong> has been approved and processed.</p>
                    
                    <div class='refund-details'>
                        <h3>📋 Refund Details</h3>
                        <p><strong>Order Number:</strong> #{$orderNumber}</p>
                        <p><strong>Refund Amount:</strong> <span class='amount'>₹{$refundAmount}</span></p>
                        <p><strong>Processing Date:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                        " . ($refundResult && $refundResult['status'] === 'success' ? 
                            "<p><strong>Refund ID:</strong> {$refundResult['refund_id']}</p>" : 
                            "<p><strong>Refund Method:</strong> Manual processing</p>"
                        ) . "
                    </div>
                    
                    <h3>💰 When will I receive my money?</h3>
                    <ul>
                        <li><strong>Credit/Debit Cards:</strong> 5-7 business days</li>
                        <li><strong>Net Banking:</strong> 5-7 business days</li>
                        <li><strong>UPI:</strong> 1-3 business days</li>
                        <li><strong>Wallets:</strong> 1-3 business days</li>
                    </ul>
                    
                    <p><strong>Note:</strong> The refund will be credited to the same payment method used for the original purchase.</p>
                    
                    <h3>📞 Need Help?</h3>
                    <p>If you have any questions about your refund, please contact our customer support:</p>
                    <ul>
                        <li>Email: support@mylittlethingz.com</li>
                        <li>Phone: +91 9876543210</li>
                    </ul>
                    
                    <p>Thank you for your patience, and we apologize for any inconvenience caused.</p>
                    
                    <p>Best regards,<br>
                    <strong>My Little Thingz Team</strong></p>
                </div>
                <div class='footer'>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: My Little Thingz <noreply@mylittlethingz.com>',
            'Reply-To: support@mylittlethingz.com'
        ];
        
        // Send email
        $emailSent = mail($customerEmail, $subject, $message, implode("\r\n", $headers));
        
        // Log email attempt
        $logMessage = date('Y-m-d H:i:s') . " - Refund notification email " . 
                     ($emailSent ? "sent successfully" : "failed") . 
                     " to {$customerEmail} for order #{$orderNumber}\n";
        file_put_contents('email_log.txt', $logMessage, FILE_APPEND);
        
        return $emailSent;
    }
    
    /**
     * Log refund processing in history
     */
    private function logRefundHistory($requestId, $adminId, $refundResult) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO unboxing_request_history 
                (request_id, old_status, new_status, changed_by_user_id, change_reason)
                VALUES (?, 'refund_approved', 'refund_processed', ?, ?)
            ");
            
            $reason = "Refund processed. ";
            if ($refundResult && $refundResult['status'] === 'success') {
                $reason .= "Razorpay Refund ID: " . $refundResult['refund_id'];
            } else {
                $reason .= "Manual refund processing required.";
            }
            
            $stmt->execute([$requestId, $adminId, $reason]);
        } catch (Exception $e) {
            error_log('Failed to log refund history: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure refund columns exist in unboxing_requests table
     */
    private function ensureRefundColumns() {
        try {
            $this->pdo->exec("
                ALTER TABLE unboxing_requests 
                ADD COLUMN IF NOT EXISTS refund_id VARCHAR(100) NULL,
                ADD COLUMN IF NOT EXISTS refund_amount DECIMAL(10,2) NULL,
                ADD COLUMN IF NOT EXISTS refund_processed_at TIMESTAMP NULL
            ");
        } catch (Exception $e) {
            // Columns might already exist, ignore error
            error_log('Refund columns might already exist: ' . $e->getMessage());
        }
    }
    
    /**
     * Get refund status for customer
     */
    public function getRefundStatus($orderId, $customerId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ur.*, o.order_number, o.total_amount
                FROM unboxing_requests ur
                JOIN orders o ON ur.order_id = o.id
                WHERE ur.order_id = ? AND ur.customer_id = ?
            ");
            $stmt->execute([$orderId, $customerId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error getting refund status: ' . $e->getMessage());
            return null;
        }
    }
}
?>