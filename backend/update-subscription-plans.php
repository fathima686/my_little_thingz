<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update subscription plans to match the strict feature matrix
    
    // Update 'free' to 'basic'
    $stmt = $db->prepare("UPDATE subscription_plans SET plan_code = 'basic', name = 'Basic', features = ? WHERE plan_code = 'free'");
    $basicFeatures = json_encode([
        'Access to basic tutorials',
        'Standard video quality', 
        'Community support',
        'Mobile access'
    ]);
    $stmt->execute([$basicFeatures]);
    
    // Update premium features (no inheritance)
    $stmt = $db->prepare("UPDATE subscription_plans SET features = ? WHERE plan_code = 'premium'");
    $premiumFeatures = json_encode([
        'Unlimited tutorial access',
        'HD video quality',
        'Weekly new content',
        'Priority support',
        'Download videos'
    ]);
    $stmt->execute([$premiumFeatures]);
    
    // Update pro features (explicit list, no inheritance)
    $stmt = $db->prepare("UPDATE subscription_plans SET features = ? WHERE plan_code = 'pro'");
    $proFeatures = json_encode([
        'Unlimited tutorial access',
        'HD video quality', 
        'Weekly new content',
        'Priority support',
        'Download videos',
        'Live workshops',
        '1-on-1 mentorship',
        'Certificate of completion',
        'Early access to new content'
    ]);
    $stmt->execute([$proFeatures]);
    
    // Update any existing 'free' subscriptions to 'basic'
    $stmt = $db->prepare("
        UPDATE subscriptions s 
        JOIN subscription_plans sp ON s.plan_id = sp.id 
        SET s.plan_id = (SELECT id FROM subscription_plans WHERE plan_code = 'basic' LIMIT 1)
        WHERE sp.plan_code = 'free'
    ");
    $stmt->execute();
    
    echo "Subscription plans updated successfully!\n";
    echo "- Basic: Access to basic tutorials, standard video quality, community support, mobile access\n";
    echo "- Premium: Unlimited tutorials, HD video, weekly content, priority support, downloads\n";
    echo "- Pro: All Premium features + live workshops, mentorship, certificates, early access\n";
    
} catch (Exception $e) {
    echo "Error updating subscription plans: " . $e->getMessage() . "\n";
}
?>