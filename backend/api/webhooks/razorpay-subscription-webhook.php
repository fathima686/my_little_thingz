<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once '../../config/database.php';
require_once '../../config/razorpay-config.php';

// Razorpay webhook secret (set this in your Razorpay dashboard)
$webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET') ?: '';

function verifyWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

try {
    $payload = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

    // Verify webhook signature
    if ($webhookSecret && !verifyWebhookSignature($payload, $signature, $webhookSecret)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature']);
        exit;
    }

    $event = json_decode($payload, true);
    
    if (!$event || !isset($event['event'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid event data']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    $eventType = $event['event'];
    $subscriptionData = $event['payload']['subscription']['entity'] ?? null;
    $paymentData = $event['payload']['payment']['entity'] ?? null;

    if (!$subscriptionData) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'No subscription data in event']);
        exit;
    }

    $razorpaySubscriptionId = $subscriptionData['id'] ?? '';

    // Find subscription in database
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE razorpay_subscription_id = ?");
    $stmt->execute([$razorpaySubscriptionId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        error_log("Subscription not found in database: $razorpaySubscriptionId");
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Subscription not found']);
        exit;
    }

    // Handle different event types
    switch ($eventType) {
        case 'subscription.activated':
        case 'subscription.charged':
            // Update subscription status
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = ?,
                    current_start = FROM_UNIXTIME(?),
                    current_end = FROM_UNIXTIME(?),
                    paid_count = ?,
                    remaining_count = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $currentStart = $subscriptionData['current_start'] ?? null;
            $currentEnd = $subscriptionData['current_end'] ?? null;
            $paidCount = $subscriptionData['paid_count'] ?? 0;
            $remainingCount = $subscriptionData['remaining_count'] ?? null;

            $updateStmt->execute([
                $subscriptionData['status'],
                $currentStart,
                $currentEnd,
                $paidCount,
                $remainingCount,
                $subscription['id']
            ]);

            // Create invoice record if payment exists
            if ($paymentData && isset($paymentData['id'])) {
                try {
                    $planStmt = $db->prepare("SELECT price, currency FROM subscription_plans WHERE id = ?");
                    $planStmt->execute([$subscription['plan_id']]);
                    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

                    $invoiceStmt = $db->prepare("
                        INSERT INTO subscription_invoices 
                        (subscription_id, razorpay_invoice_id, razorpay_payment_id, amount, currency, status, paid_at)
                        VALUES (?, ?, ?, ?, ?, 'paid', NOW())
                        ON DUPLICATE KEY UPDATE 
                            status = 'paid',
                            paid_at = NOW(),
                            updated_at = NOW()
                    ");
                    
                    $invoiceId = $event['payload']['invoice']['entity']['id'] ?? null;
                    $amount = ($paymentData['amount'] ?? ($plan['price'] * 100)) / 100; // Convert from paise
                    
                    $invoiceStmt->execute([
                        $subscription['id'],
                        $invoiceId,
                        $paymentData['id'],
                        $amount,
                        $plan['currency'] ?? 'INR'
                    ]);
                } catch (Throwable $e) {
                    error_log('Invoice creation error in webhook: ' . $e->getMessage());
                }
            }
            break;

        case 'subscription.cancelled':
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$subscription['id']]);
            break;

        case 'subscription.completed':
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'completed',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$subscription['id']]);
            break;

        case 'subscription.paused':
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'halted',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$subscription['id']]);
            break;

        case 'subscription.resumed':
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = 'active',
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$subscription['id']]);
            break;

        case 'subscription.updated':
            $updateStmt = $db->prepare("
                UPDATE subscriptions 
                SET status = ?,
                    current_start = FROM_UNIXTIME(?),
                    current_end = FROM_UNIXTIME(?),
                    paid_count = ?,
                    remaining_count = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $currentStart = $subscriptionData['current_start'] ?? null;
            $currentEnd = $subscriptionData['current_end'] ?? null;
            $paidCount = $subscriptionData['paid_count'] ?? 0;
            $remainingCount = $subscriptionData['remaining_count'] ?? null;

            $updateStmt->execute([
                $subscriptionData['status'],
                $currentStart,
                $currentEnd,
                $paidCount,
                $remainingCount,
                $subscription['id']
            ]);
            break;

        default:
            error_log("Unhandled webhook event: $eventType");
            break;
    }

    echo json_encode(['status' => 'success', 'message' => 'Webhook processed']);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('Webhook error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_encode([
        'status' => 'error',
        'message' => 'Error processing webhook: ' . $e->getMessage()
    ]);
}


