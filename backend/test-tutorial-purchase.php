<?php
// Test script to verify tutorial purchase functionality
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test 1: Check database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    echo json_encode(['status' => 'success', 'message' => 'Database connection OK']);
    
    // Test 2: Check Razorpay config
    require_once 'config/razorpay-config.php';
    if (defined('RAZORPAY_KEY') && defined('RAZORPAY_SECRET')) {
        echo json_encode(['status' => 'success', 'message' => 'Razorpay config OK']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Razorpay config missing']);
    }
    
    // Test 3: Check Razorpay SDK
    if (class_exists('Razorpay\\Api\\Api')) {
        echo json_encode(['status' => 'success', 'message' => 'Razorpay SDK loaded']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Razorpay SDK not found']);
    }
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>