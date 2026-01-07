<?php
// Comprehensive Razorpay integration test
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Razorpay Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Razorpay Integration Test</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Test 1: Environment Configuration
    echo '<div class="test-section">';
    echo '<h2>1. Environment Configuration</h2>';
    
    $envPath = __DIR__ . '/.env';
    if (file_exists($envPath)) {
        echo '<p class="success">✓ .env file exists</p>';
        $envContent = file_get_contents($envPath);
        if (strpos($envContent, 'RAZORPAY_KEY_ID') !== false && strpos($envContent, 'RAZORPAY_KEY_SECRET') !== false) {
            echo '<p class="success">✓ Razorpay credentials found in .env</p>';
        } else {
            echo '<p class="error">✗ Razorpay credentials missing in .env</p>';
        }
    } else {
        echo '<p class="error">✗ .env file not found</p>';
    }
    echo '</div>';
    
    // Test 2: Razorpay Configuration Loading
    echo '<div class="test-section">';
    echo '<h2>2. Razorpay Configuration Loading</h2>';
    
    try {
        require_once __DIR__ . '/config/razorpay-config.php';
        if (defined('RAZORPAY_KEY') && defined('RAZORPAY_SECRET')) {
            echo '<p class="success">✓ Razorpay configuration loaded successfully</p>';
            echo '<p class="info">Key ID: ' . substr(RAZORPAY_KEY, 0, 15) . '...</p>';
            echo '<p class="info">Secret: ' . substr(RAZORPAY_SECRET, 0, 10) . '...</p>';
        } else {
            echo '<p class="error">✗ Razorpay constants not defined</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Configuration loading failed: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Test 3: Composer Dependencies
    echo '<div class="test-section">';
    echo '<h2>3. Composer Dependencies</h2>';
    
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        echo '<p class="success">✓ Composer autoload exists</p>';
        try {
            require_once $autoloadPath;
            echo '<p class="success">✓ Autoload included successfully</p>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Autoload failed: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p class="error">✗ Composer autoload not found. Run: composer install</p>';
    }
    echo '</div>';
    
    // Test 4: Razorpay SDK
    echo '<div class="test-section">';
    echo '<h2>4. Razorpay SDK</h2>';
    
    if (class_exists('Razorpay\\Api\\Api')) {
        echo '<p class="success">✓ Razorpay SDK loaded</p>';
        
        // Test API connection
        if (defined('RAZORPAY_KEY') && defined('RAZORPAY_SECRET')) {
            try {
                $api = new Razorpay\Api\Api(RAZORPAY_KEY, RAZORPAY_SECRET);
                echo '<p class="success">✓ Razorpay API client created</p>';
                
                // Test creating a simple order (small amount)
                try {
                    $orderData = [
                        'amount' => 100, // ₹1.00 in paise
                        'currency' => 'INR',
                        'receipt' => 'test_' . time(),
                        'notes' => ['test' => 'integration_test']
                    ];
                    
                    $order = $api->order->create($orderData);
                    echo '<p class="success">✓ Test order created successfully</p>';
                    echo '<p class="info">Order ID: ' . $order['id'] . '</p>';
                    echo '<p class="info">Amount: ₹' . ($order['amount'] / 100) . '</p>';
                } catch (Exception $e) {
                    echo '<p class="error">✗ Order creation failed: ' . $e->getMessage() . '</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ API client creation failed: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="error">✗ Cannot test API - credentials not loaded</p>';
        }
    } else {
        echo '<p class="error">✗ Razorpay SDK not found</p>';
    }
    echo '</div>';
    
    // Test 5: Database Connection
    echo '<div class="test-section">';
    echo '<h2>5. Database Connection</h2>';
    
    try {
        require_once __DIR__ . '/config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        echo '<p class="success">✓ Database connection successful</p>';
        
        // Check if tutorial tables exist
        $tables = ['tutorials', 'tutorial_purchases', 'users'];
        foreach ($tables as $table) {
            try {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    echo '<p class="success">✓ Table ' . $table . ' exists</p>';
                } else {
                    echo '<p class="error">✗ Table ' . $table . ' missing</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">✗ Error checking table ' . $table . ': ' . $e->getMessage() . '</p>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Database connection failed: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Test 6: Frontend Configuration
    echo '<div class="test-section">';
    echo '<h2>6. Frontend Configuration</h2>';
    
    $frontendEnvPath = __DIR__ . '/../frontend/.env';
    if (file_exists($frontendEnvPath)) {
        echo '<p class="success">✓ Frontend .env file exists</p>';
        $frontendEnvContent = file_get_contents($frontendEnvPath);
        if (strpos($frontendEnvContent, 'VITE_RAZORPAY_KEY') !== false) {
            echo '<p class="success">✓ Frontend Razorpay key configured</p>';
        } else {
            echo '<p class="error">✗ Frontend Razorpay key missing</p>';
        }
    } else {
        echo '<p class="error">✗ Frontend .env file not found</p>';
    }
    echo '</div>';
    
    echo '<div class="test-section">';
    echo '<h2>Summary</h2>';
    echo '<p>If all tests show ✓, your Razorpay integration should be working correctly.</p>';
    echo '<p>If you see any ✗, please fix those issues before testing payments.</p>';
    echo '</div>';
    ?>
</body>
</html>