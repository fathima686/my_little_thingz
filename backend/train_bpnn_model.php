<?php
/**
 * BPNN Model Training Script
 * 
 * This script trains the initial BPNN model for gift preference prediction.
 * Run this script after setting up the database to create the first model.
 */

require_once 'config/database.php';
require_once 'services/BPNNTrainer.php';
require_once 'services/UserBehaviorTracker.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Starting BPNN model training...\n";
    
    $trainer = new BPNNTrainer($db);
    $behaviorTracker = new UserBehaviorTracker($db);
    
    // Check if we have enough data
    $stats = $behaviorTracker->getBehaviorStatistics();
    echo "Current data statistics:\n";
    echo "- Total behaviors: " . number_format($stats['total_behaviors']) . "\n";
    echo "- Unique users: " . number_format($stats['unique_users']) . "\n";
    echo "- Unique artworks: " . number_format($stats['unique_artworks']) . "\n";
    echo "- Recent activity (24h): " . number_format($stats['recent_24h']) . "\n";
    
    if ($stats['total_behaviors'] < 100) {
        echo "\n⚠️  Warning: Insufficient data for training. Need at least 100 behavior records.\n";
        echo "Please ensure users are interacting with the system before training.\n";
        exit(1);
    }
    
    // Training configuration
    $config = [
        'hidden_layers' => [8, 6],
        'learning_rate' => 0.01,
        'epochs' => 1000,
        'validation_split' => 0.2,
        'training_data_limit' => min(2000, $stats['total_behaviors']),
        'activation_function' => 'sigmoid'
    ];
    
    echo "\nTraining configuration:\n";
    echo "- Hidden layers: " . implode(', ', $config['hidden_layers']) . "\n";
    echo "- Learning rate: " . $config['learning_rate'] . "\n";
    echo "- Epochs: " . $config['epochs'] . "\n";
    echo "- Training data limit: " . $config['training_data_limit'] . "\n";
    echo "- Activation function: " . $config['activation_function'] . "\n";
    
    echo "\nStarting training process...\n";
    $startTime = microtime(true);
    
    // Train the model
    $results = $trainer->trainModel($config);
    
    $endTime = microtime(true);
    $trainingTime = round($endTime - $startTime, 2);
    
    echo "\nTraining completed in {$trainingTime} seconds!\n";
    echo "\nTraining results:\n";
    echo "- Model ID: " . $results['model_id'] . "\n";
    echo "- Training samples: " . number_format($results['training_samples']) . "\n";
    echo "- Final training loss: " . round($results['final_training_loss'], 6) . "\n";
    echo "- Final validation loss: " . round($results['final_validation_loss'], 6) . "\n";
    echo "- Final training accuracy: " . round($results['final_training_accuracy'] * 100, 2) . "%\n";
    echo "- Final validation accuracy: " . round($results['final_validation_accuracy'] * 100, 2) . "%\n";
    echo "- Training epochs: " . $results['training_epochs'] . "\n";
    
    // Test the model
    echo "\nTesting the trained model...\n";
    try {
        $testResults = $trainer->testModel();
        echo "Test results:\n";
        echo "- Test accuracy: " . round($testResults['accuracy'] * 100, 2) . "%\n";
        echo "- Correct predictions: " . $testResults['correct_predictions'] . "\n";
        echo "- Total predictions: " . $testResults['total_predictions'] . "\n";
    } catch (Exception $e) {
        echo "Test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 BPNN model training completed successfully!\n";
    echo "The AI recommendation system is now ready to use.\n";
    echo "\nNext steps:\n";
    echo "1. Test the recommendations API: /api/customer/bpnn_recommendations.php\n";
    echo "2. Integrate the BPNNRecommendations component in your frontend\n";
    echo "3. Monitor model performance and retrain as needed\n";
    
} catch (Exception $e) {
    echo "Training failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

