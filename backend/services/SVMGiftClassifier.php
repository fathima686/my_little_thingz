<?php

/**
 * Support Vector Machine (SVM) Gift Classifier
 * 
 * This class implements a simplified SVM-like algorithm for classifying gifts
 * as Budget vs Premium based on multiple features.
 */
class SVMGiftClassifier
{
    private $db;
    private $weights = [];
    private $bias = 0;
    private $isTrained = false;
    private $featureWeights = [
        'price' => 0.4,
        'category_luxury' => 0.2,
        'title_keywords' => 0.15,
        'description_keywords' => 0.15,
        'availability' => 0.1
    ];
    
    public function __construct($database)
    {
        $this->db = $database;
        $this->initializeWeights();
    }
    
    /**
     * Initialize SVM weights and bias
     */
    private function initializeWeights()
    {
        // Initialize with default weights based on feature importance
        $this->weights = [
            'price' => 0.8,
            'category_luxury' => 0.6,
            'title_keywords' => 0.4,
            'description_keywords' => 0.3,
            'availability' => 0.2
        ];
        $this->bias = -0.5; // Threshold for classification
    }
    
    /**
     * Classify a gift as Budget or Premium
     * 
     * @param array $giftData Gift information
     * @return array Classification result
     */
    public function classifyGift($giftData)
    {
        $features = $this->extractFeatures($giftData);
        $score = $this->calculateScore($features);
        $prediction = $score > 0 ? 'Premium' : 'Budget';
        $confidence = abs($score);
        
        return [
            'prediction' => $prediction,
            'confidence' => min(1.0, $confidence),
            'score' => $score,
            'features' => $features,
            'reasoning' => $this->getReasoning($features, $score)
        ];
    }
    
    /**
     * Extract features from gift data
     */
    private function extractFeatures($giftData)
    {
        $features = [];
        
        // Price feature (normalized)
        $price = (float)($giftData['price'] ?? 0);
        $features['price'] = $this->normalizePrice($price);
        
        // Category luxury indicator
        $categoryId = (int)($giftData['category_id'] ?? 0);
        $features['category_luxury'] = $this->getCategoryLuxuryScore($categoryId);
        
        // Title keywords
        $title = strtolower($giftData['title'] ?? '');
        $features['title_keywords'] = $this->getLuxuryKeywordScore($title);
        
        // Description keywords
        $description = strtolower($giftData['description'] ?? '');
        $features['description_keywords'] = $this->getLuxuryKeywordScore($description);
        
        // Availability (limited availability = more premium)
        $availability = $giftData['availability'] ?? 'in_stock';
        $features['availability'] = $this->getAvailabilityScore($availability);
        
        return $features;
    }
    
    /**
     * Normalize price to 0-1 scale
     */
    private function normalizePrice($price)
    {
        // Use sigmoid normalization for price
        $maxPrice = 5000; // Maximum expected price
        return 1 / (1 + exp(-($price - 1000) / 500));
    }
    
    /**
     * Get category luxury score
     */
    private function getCategoryLuxuryScore($categoryId)
    {
        $luxuryCategories = [
            // High luxury categories
            'gift box' => 0.9,
            'boquetes' => 0.8,
            'custom chocolate' => 0.7,
            'frames' => 0.6,
            'poloroid' => 0.5,
            'Wedding card' => 0.4,
            'drawings' => 0.3,
            'album' => 0.2,
            'Greeting Card' => 0.1
        ];
        
        // Get category name
        try {
            $stmt = $this->db->prepare("SELECT name FROM categories WHERE id = :id");
            $stmt->bindValue(':id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($category) {
                $categoryName = strtolower($category['name']);
                return $luxuryCategories[$categoryName] ?? 0.5; // Default medium luxury
            }
        } catch (Exception $e) {
            // Database error, use default
        }
        
        return 0.5; // Default medium luxury
    }
    
    /**
     * Get luxury keyword score from text
     */
    private function getLuxuryKeywordScore($text)
    {
        $luxuryKeywords = [
            'luxury' => 0.9,
            'premium' => 0.8,
            'exclusive' => 0.8,
            'designer' => 0.8,
            'handmade' => 0.7,
            'custom' => 0.7,
            'artisan' => 0.7,
            'deluxe' => 0.6,
            'elegant' => 0.6,
            'sophisticated' => 0.6,
            'gourmet' => 0.5,
            'special' => 0.4,
            'unique' => 0.4,
            'beautiful' => 0.3,
            'stunning' => 0.3
        ];
        
        $score = 0;
        $wordCount = 0;
        $words = preg_split('/\W+/', $text);
        
        foreach ($words as $word) {
            $word = strtolower(trim($word));
            if (strlen($word) > 2) {
                $wordCount++;
                if (isset($luxuryKeywords[$word])) {
                    $score += $luxuryKeywords[$word];
                }
            }
        }
        
        return $wordCount > 0 ? $score / $wordCount : 0;
    }
    
    /**
     * Get availability score
     */
    private function getAvailabilityScore($availability)
    {
        $availabilityScores = [
            'limited' => 0.9,
            'rare' => 0.8,
            'exclusive' => 0.8,
            'out_of_stock' => 0.7,
            'pre_order' => 0.6,
            'in_stock' => 0.3,
            'available' => 0.2
        ];
        
        return $availabilityScores[$availability] ?? 0.3;
    }
    
    /**
     * Calculate SVM score
     */
    private function calculateScore($features)
    {
        $score = $this->bias;
        
        foreach ($features as $feature => $value) {
            if (isset($this->weights[$feature])) {
                $score += $this->weights[$feature] * $value;
            }
        }
        
        return $score;
    }
    
    /**
     * Get reasoning for classification
     */
    private function getReasoning($features, $score)
    {
        $reasons = [];
        
        if ($features['price'] > 0.7) {
            $reasons[] = 'High price indicates premium quality';
        } elseif ($features['price'] < 0.3) {
            $reasons[] = 'Lower price suggests budget category';
        }
        
        if ($features['category_luxury'] > 0.7) {
            $reasons[] = 'Category is associated with luxury items';
        } elseif ($features['category_luxury'] < 0.3) {
            $reasons[] = 'Category is typically budget-friendly';
        }
        
        if ($features['title_keywords'] > 0.5) {
            $reasons[] = 'Title contains luxury keywords';
        }
        
        if ($features['description_keywords'] > 0.5) {
            $reasons[] = 'Description emphasizes premium features';
        }
        
        if ($features['availability'] > 0.6) {
            $reasons[] = 'Limited availability suggests exclusivity';
        }
        
        if (empty($reasons)) {
            $reasons[] = 'Classification based on overall feature analysis';
        }
        
        return $reasons;
    }
    
    /**
     * Train the SVM model (simplified training)
     */
    public function trainModel($trainingData = null)
    {
        if ($trainingData === null) {
            $trainingData = $this->getTrainingData();
        }
        
        if (empty($trainingData)) {
            throw new Exception('No training data available');
        }
        
        // Simple gradient descent training
        $learningRate = 0.01;
        $epochs = 100;
        
        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $totalError = 0;
            
            foreach ($trainingData as $sample) {
                $features = $this->extractFeatures($sample);
                $predicted = $this->calculateScore($features);
                $actual = $sample['is_premium'] ? 1 : -1;
                
                $error = $actual - $predicted;
                $totalError += abs($error);
                
                // Update weights
                foreach ($features as $feature => $value) {
                    if (isset($this->weights[$feature])) {
                        $this->weights[$feature] += $learningRate * $error * $value;
                    }
                }
                
                // Update bias
                $this->bias += $learningRate * $error;
            }
            
            // Early stopping if error is small
            if ($totalError < 0.1) {
                break;
            }
        }
        
        $this->isTrained = true;
        
        return [
            'epochs' => $epoch + 1,
            'final_error' => $totalError,
            'weights' => $this->weights,
            'bias' => $this->bias
        ];
    }
    
    /**
     * Get training data from database
     */
    private function getTrainingData()
    {
        $trainingData = [];
        
        try {
            // Get products with price-based classification
            $stmt = $this->db->prepare("
                SELECT 
                    a.id, a.title, a.description, a.price, a.category_id, 
                    a.availability, a.image_url,
                    c.name as category_name,
                    CASE 
                        WHEN a.price >= 1000 THEN 1
                        ELSE 0
                    END as is_premium
                FROM artworks a
                LEFT JOIN categories c ON a.category_id = c.id
                WHERE a.status = 'active'
                ORDER BY a.created_at DESC
                LIMIT 500
            ");
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $trainingData[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'price' => $row['price'],
                    'category_id' => $row['category_id'],
                    'category_name' => $row['category_name'],
                    'availability' => $row['availability'],
                    'image_url' => $row['image_url'],
                    'is_premium' => (bool)$row['is_premium']
                ];
            }
        } catch (Exception $e) {
            error_log("SVM Training data error: " . $e->getMessage());
        }
        
        return $trainingData;
    }
    
    /**
     * Batch classify multiple gifts
     */
    public function batchClassify($gifts)
    {
        $results = [];
        
        foreach ($gifts as $gift) {
            $results[] = $this->classifyGift($gift);
        }
        
        return $results;
    }
    
    /**
     * Get model statistics
     */
    public function getModelStats()
    {
        return [
            'is_trained' => $this->isTrained,
            'weights' => $this->weights,
            'bias' => $this->bias,
            'feature_weights' => $this->featureWeights
        ];
    }
    
    /**
     * Save model to database
     */
    public function saveModel($modelName = 'svm_gift_classifier')
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO svm_models (model_name, weights, bias, is_active, created_at)
                VALUES (:name, :weights, :bias, 1, NOW())
                ON DUPLICATE KEY UPDATE
                weights = VALUES(weights),
                bias = VALUES(bias),
                is_active = 1,
                updated_at = NOW()
            ");
            
            $stmt->bindValue(':name', $modelName);
            $stmt->bindValue(':weights', json_encode($this->weights));
            $stmt->bindValue(':bias', $this->bias);
            $stmt->execute();
            
            return true;
        } catch (Exception $e) {
            error_log("SVM Model save error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load model from database
     */
    public function loadModel($modelName = 'svm_gift_classifier')
    {
        try {
            $stmt = $this->db->prepare("
                SELECT weights, bias FROM svm_models 
                WHERE model_name = :name AND is_active = 1
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->bindValue(':name', $modelName);
            $stmt->execute();
            
            $model = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($model) {
                $this->weights = json_decode($model['weights'], true);
                $this->bias = (float)$model['bias'];
                $this->isTrained = true;
                return true;
            }
        } catch (Exception $e) {
            error_log("SVM Model load error: " . $e->getMessage());
        }
        
        return false;
    }
}



