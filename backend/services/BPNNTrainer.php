<?php

require_once 'BPNNNeuralNetwork.php';
require_once 'BPNNDataProcessor.php';

/**
 * BPNN Trainer for Gift Preference Prediction
 * 
 * This class handles training, saving, and loading of BPNN models
 * for the recommendation system.
 */
class BPNNTrainer
{
    private $db;
    private $dataProcessor;
    private $modelName = 'gift_preference_predictor';

    public function __construct($database)
    {
        $this->db = $database;
        $this->dataProcessor = new BPNNDataProcessor($database);
    }

    /**
     * Train a new BPNN model
     * 
     * @param array $config Training configuration
     * @return array Training results
     */
    public function trainModel($config = [])
    {
        $defaultConfig = [
            'hidden_layers' => [8, 6],
            'learning_rate' => 0.01,
            'epochs' => 1000,
            'validation_split' => 0.2,
            'training_data_limit' => 2000,
            'activation_function' => 'sigmoid'
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Prepare training data
        $trainingData = $this->dataProcessor->prepareTrainingData($config['training_data_limit']);
        
        if (count($trainingData) < 100) {
            throw new Exception('Insufficient training data. Need at least 100 samples.');
        }
        
        // Normalize features
        $normalizationParams = $this->dataProcessor->getNormalizationParams();
        $normalizedData = $this->normalizeTrainingData($trainingData, $normalizationParams);
        
        // Create and train neural network
        $inputSize = count($normalizedData[0][0]);
        $nn = new BPNNNeuralNetwork(
            $inputSize,
            $config['hidden_layers'],
            1,
            $config['learning_rate'],
            $config['activation_function']
        );
        
        // Train the network
        $history = $nn->train($normalizedData, $config['epochs'], $config['validation_split']);
        
        // Get final metrics
        $finalTrainingLoss = end($history['training_loss']);
        $finalValidationLoss = end($history['validation_loss']);
        $finalTrainingAccuracy = end($history['training_accuracy']);
        $finalValidationAccuracy = end($history['validation_accuracy']);
        
        // Save model to database
        $modelId = $this->saveModel($nn, $config, $history, count($trainingData));
        
        return [
            'model_id' => $modelId,
            'training_samples' => count($trainingData),
            'final_training_loss' => $finalTrainingLoss,
            'final_validation_loss' => $finalValidationLoss,
            'final_training_accuracy' => $finalTrainingAccuracy,
            'final_validation_accuracy' => $finalValidationAccuracy,
            'training_epochs' => count($history['training_loss']),
            'history' => $history
        ];
    }

    /**
     * Normalize training data using calculated parameters
     * 
     * @param array $trainingData Raw training data
     * @param array $normalizationParams Normalization parameters
     * @return array Normalized training data
     */
    private function normalizeTrainingData($trainingData, $normalizationParams)
    {
        $normalizedData = [];
        
        foreach ($trainingData as $sample) {
            $features = $sample[0];
            $target = $sample[1];
            
            $normalizedFeatures = BPNNNeuralNetwork::normalizeInputs(
                $features,
                $normalizationParams['minValues'],
                $normalizationParams['maxValues']
            );
            
            $normalizedData[] = [$normalizedFeatures, $target];
        }
        
        return $normalizedData;
    }

    /**
     * Save trained model to database
     * 
     * @param BPNNNeuralNetwork $nn Trained neural network
     * @param array $config Training configuration
     * @param array $history Training history
     * @param int $trainingDataSize Number of training samples
     * @return int Model ID
     */
    private function saveModel($nn, $config, $history, $trainingDataSize)
    {
        // Deactivate current active model
        $stmt = $this->db->prepare("UPDATE bpnn_models SET is_active = 0 WHERE model_name = :model_name");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
        
        // Get next version number
        $stmt = $this->db->prepare("
            SELECT MAX(CAST(SUBSTRING(model_version, 1, LOCATE('.', model_version) - 1) AS UNSIGNED)) as major_version
            FROM bpnn_models 
            WHERE model_name = :model_name
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $majorVersion = $result['major_version'] ? (int)$result['major_version'] + 1 : 1;
        $version = $majorVersion . '.0';
        
        // Get model parameters
        $parameters = $nn->getParameters();
        
        // Create architecture JSON
        $architecture = [
            'input_size' => $parameters['inputSize'],
            'hidden_layers' => $parameters['hiddenLayers'],
            'output_size' => $parameters['outputSize'],
            'activation' => $parameters['activationFunction'],
            'learning_rate' => $parameters['learningRate']
        ];
        
        // Serialize weights
        $weights = serialize($parameters['weights']);
        
        // Calculate final metrics
        $finalTrainingLoss = end($history['training_loss']);
        $finalValidationLoss = end($history['validation_loss']);
        $finalTrainingAccuracy = end($history['training_accuracy']);
        $finalValidationAccuracy = end($history['validation_accuracy']);
        
        // Insert new model
        $stmt = $this->db->prepare("
            INSERT INTO bpnn_models 
            (model_name, model_version, architecture, weights, training_data_size, 
             training_accuracy, validation_accuracy, training_loss, validation_loss, 
             training_epochs, learning_rate, is_active)
            VALUES (:model_name, :version, :architecture, :weights, :data_size,
                    :train_acc, :val_acc, :train_loss, :val_loss,
                    :epochs, :lr, 1)
        ");
        
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->bindValue(':version', $version, PDO::PARAM_STR);
        $stmt->bindValue(':architecture', json_encode($architecture), PDO::PARAM_STR);
        $stmt->bindValue(':weights', $weights, PDO::PARAM_STR);
        $stmt->bindValue(':data_size', $trainingDataSize, PDO::PARAM_INT);
        $stmt->bindValue(':train_acc', $finalTrainingAccuracy, PDO::PARAM_STR);
        $stmt->bindValue(':val_acc', $finalValidationAccuracy, PDO::PARAM_STR);
        $stmt->bindValue(':train_loss', $finalTrainingLoss, PDO::PARAM_STR);
        $stmt->bindValue(':val_loss', $finalValidationLoss, PDO::PARAM_STR);
        $stmt->bindValue(':epochs', count($history['training_loss']), PDO::PARAM_INT);
        $stmt->bindValue(':lr', $config['learning_rate'], PDO::PARAM_STR);
        $stmt->execute();
        
        return $this->db->lastInsertId();
    }

    /**
     * Load the active model
     * 
     * @return BPNNNeuralNetwork|null Loaded neural network
     */
    public function loadActiveModel()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM bpnn_models 
            WHERE model_name = :model_name AND is_active = 1
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
        
        $modelData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$modelData) {
            return null;
        }
        
        // Deserialize weights
        $weights = unserialize($modelData['weights']);
        
        // Create neural network
        $architecture = json_decode($modelData['architecture'], true);
        $nn = new BPNNNeuralNetwork(
            $architecture['input_size'],
            $architecture['hidden_layers'],
            $architecture['output_size'],
            $architecture['learning_rate'],
            $architecture['activation']
        );
        
        // Load parameters
        $parameters = [
            'weights' => $weights,
            'biases' => $weights, // Assuming weights array includes biases
            'inputSize' => $architecture['input_size'],
            'hiddenLayers' => $architecture['hidden_layers'],
            'outputSize' => $architecture['output_size'],
            'learningRate' => $architecture['learning_rate'],
            'activationFunction' => $architecture['activation'],
            'isTrained' => true
        ];
        
        $nn->loadParameters($parameters);
        
        return $nn;
    }

    /**
     * Retrain model with new data
     * 
     * @param array $config Training configuration
     * @return array Training results
     */
    public function retrainModel($config = [])
    {
        // Get current model performance
        $currentModel = $this->getModelPerformance();
        
        // Train new model
        $results = $this->trainModel($config);
        
        // Compare performance
        $improvement = $results['final_validation_accuracy'] - $currentModel['validation_accuracy'];
        
        // If new model is worse, revert to previous model
        if ($improvement < 0) {
            $this->revertToPreviousModel();
            return [
                'status' => 'reverted',
                'message' => 'New model performed worse, reverted to previous model',
                'improvement' => $improvement
            ];
        }
        
        return array_merge($results, [
            'status' => 'success',
            'improvement' => $improvement
        ]);
    }

    /**
     * Get current model performance
     * 
     * @return array Model performance metrics
     */
    public function getModelPerformance()
    {
        $stmt = $this->db->prepare("
            SELECT * FROM bpnn_models 
            WHERE model_name = :model_name AND is_active = 1
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
        
        $model = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$model) {
            return null;
        }
        
        return [
            'model_id' => $model['id'],
            'version' => $model['model_version'],
            'training_accuracy' => (float)$model['training_accuracy'],
            'validation_accuracy' => (float)$model['validation_accuracy'],
            'training_loss' => (float)$model['training_loss'],
            'validation_loss' => (float)$model['validation_loss'],
            'training_epochs' => (int)$model['training_epochs'],
            'training_data_size' => (int)$model['training_data_size'],
            'created_at' => $model['created_at']
        ];
    }

    /**
     * Revert to previous model
     */
    private function revertToPreviousModel()
    {
        // Deactivate current model
        $stmt = $this->db->prepare("UPDATE bpnn_models SET is_active = 0 WHERE model_name = :model_name AND is_active = 1");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
        
        // Activate previous model
        $stmt = $this->db->prepare("
            UPDATE bpnn_models 
            SET is_active = 1 
            WHERE model_name = :model_name 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Get model training history
     * 
     * @param int $limit Number of models to retrieve
     * @return array Model history
     */
    public function getModelHistory($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT 
                id, model_version, training_accuracy, validation_accuracy,
                training_loss, validation_loss, training_epochs, training_data_size,
                is_active, created_at
            FROM bpnn_models 
            WHERE model_name = :model_name
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete old models (keep only recent ones)
     * 
     * @param int $keepCount Number of models to keep
     */
    public function cleanupOldModels($keepCount = 5)
    {
        $stmt = $this->db->prepare("
            DELETE FROM bpnn_models 
            WHERE model_name = :model_name 
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM bpnn_models 
                    WHERE model_name = :model_name2 
                    ORDER BY created_at DESC 
                    LIMIT :keep_count
                ) as keep_models
            )
        ");
        $stmt->bindValue(':model_name', $this->modelName, PDO::PARAM_STR);
        $stmt->bindValue(':model_name2', $this->modelName, PDO::PARAM_STR);
        $stmt->bindValue(':keep_count', $keepCount, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Test model performance on validation data
     * 
     * @return array Test results
     */
    public function testModel()
    {
        $nn = $this->loadActiveModel();
        if (!$nn) {
            throw new Exception('No active model found');
        }
        
        // Get test data (recent user behavior)
        $stmt = $this->db->prepare("
            SELECT 
                ub.user_id, ub.artwork_id, ub.behavior_type, ub.rating_value,
                a.category_id, a.price
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            AND a.status = 'active'
            ORDER BY RAND()
            LIMIT 100
        ");
        $stmt->execute();
        
        $testData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $correct = 0;
        $total = 0;
        $predictions = [];
        
        foreach ($testData as $behavior) {
            $userId = (int)$behavior['user_id'];
            $artworkId = (int)$behavior['artwork_id'];
            
            // Extract features
            $features = $this->dataProcessor->extractFeatures($userId, $artworkId);
            if (!$features) {
                continue;
            }
            
            // Normalize features
            $normalizationParams = $this->dataProcessor->getNormalizationParams();
            $normalizedFeatures = BPNNNeuralNetwork::normalizeInputs(
                $features,
                $normalizationParams['minValues'],
                $normalizationParams['maxValues']
            );
            
            // Make prediction
            $prediction = $nn->predict($normalizedFeatures);
            $predictedPreference = $prediction['prediction'] > 0.5 ? 1 : 0;
            
            // Calculate actual preference
            $actualPreference = $this->calculateActualPreference($behavior);
            
            $predictions[] = [
                'user_id' => $userId,
                'artwork_id' => $artworkId,
                'predicted' => $predictedPreference,
                'actual' => $actualPreference,
                'confidence' => $prediction['confidence']
            ];
            
            if ($predictedPreference == $actualPreference) {
                $correct++;
            }
            $total++;
        }
        
        $accuracy = $total > 0 ? $correct / $total : 0;
        
        return [
            'accuracy' => $accuracy,
            'correct_predictions' => $correct,
            'total_predictions' => $total,
            'predictions' => $predictions
        ];
    }

    /**
     * Calculate actual preference from behavior
     * 
     * @param array $behavior Behavior data
     * @return int Actual preference (0 or 1)
     */
    private function calculateActualPreference($behavior)
    {
        $behaviorType = $behavior['behavior_type'];
        $rating = $behavior['rating_value'] ? (float)$behavior['rating_value'] : null;
        
        switch ($behaviorType) {
            case 'purchase':
            case 'add_to_wishlist':
                return 1;
            case 'add_to_cart':
                return $rating && $rating >= 4 ? 1 : 0;
            case 'rating':
                return $rating && $rating >= 4 ? 1 : 0;
            case 'view':
                return 0;
            case 'remove_from_wishlist':
                return 0;
            default:
                return 0;
        }
    }
}
















