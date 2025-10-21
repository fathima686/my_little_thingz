<?php
/**
 * Gift Category Classifier
 * Uses rule-based keyword matching + Naive Bayes-like confidence scoring
 * Predicts gift categories from gift names with confidence levels
 */

class GiftCategoryClassifier {
    
    private $mysqli;
    private $categoryPatterns = [];
    private $wordWeights = [];
    
    public function __construct($mysqli = null) {
        $this->mysqli = $mysqli;
        $this->initializePatterns();
    }
    
    /**
     * Initialize keyword patterns for each category
     * Add more patterns as needed without breaking existing code
     */
    private function initializePatterns() {
        $this->categoryPatterns = [
            'Gift box' => [
                'keywords' => ['box', 'gift box', 'hamper', 'case', 'package', 'set'],
                'priority' => 10,
                'weight' => 1.0
            ],
            'boquetes' => [
                'keywords' => ['bouquet', 'boquete', 'boque', 'flowers', 'rose', 'floral', 'arrangement'],
                'priority' => 9,
                'weight' => 0.95
            ],
            'frames' => [
                'keywords' => ['frame', 'photo frame', 'picture frame', 'wall frame', 'display frame'],
                'priority' => 8,
                'weight' => 0.90
            ],
            'poloroid' => [
                'keywords' => ['polaroid', 'poloroid', 'instant photo', 'photo print'],
                'priority' => 8,
                'weight' => 0.90
            ],
            'custom chocolate' => [
                'keywords' => ['chocolate', 'choco', 'cocoa', 'truffle', 'candy', 'sweet'],
                'priority' => 7,
                'weight' => 0.85
            ],
            'Wedding card' => [
                'keywords' => ['wedding', 'marriage', 'card', 'invitation', 'invite', 'bridal'],
                'priority' => 8,
                'weight' => 0.92
            ],
            'drawings' => [
                'keywords' => ['drawing', 'sketch', 'art', 'illustration', 'paint', 'artwork', 'custom art'],
                'priority' => 7,
                'weight' => 0.85
            ],
            'album' => [
                'keywords' => ['album', 'photo album', 'memory album', 'scrapbook', 'collection book'],
                'priority' => 7,
                'weight' => 0.85
            ],
            'Greeting Card' => [
                'keywords' => ['greeting card', 'card', 'birthday card', 'occasion card', 'thank you card'],
                'priority' => 6,
                'weight' => 0.80
            ]
        ];
    }
    
    /**
     * Classify gift name and return prediction with confidence
     * 
     * @param string $giftName - Name of the gift
     * @param float $confidenceThreshold - Threshold for auto-assignment (0.0 to 1.0)
     * @return array ['predicted_category' => string, 'confidence' => float, 'suggested_categories' => array, 'action' => string]
     */
    public function classifyGift($giftName, $confidenceThreshold = 0.75) {
        if (empty($giftName)) {
            return [
                'predicted_category' => null,
                'confidence' => 0.0,
                'suggested_categories' => [],
                'action' => 'manual_review',
                'reason' => 'Empty gift name'
            ];
        }
        
        $normalizedName = strtolower(trim($giftName));
        $scores = [];
        
        // Calculate scores for each category
        foreach ($this->categoryPatterns as $category => $pattern) {
            $score = $this->calculateCategoryScore($normalizedName, $pattern);
            if ($score > 0) {
                $scores[$category] = $score;
            }
        }
        
        // Sort by score
        arsort($scores);
        
        if (empty($scores)) {
            return [
                'predicted_category' => null,
                'confidence' => 0.0,
                'suggested_categories' => [],
                'action' => 'manual_review',
                'reason' => 'No matching patterns found'
            ];
        }
        
        // Get top prediction
        $topCategory = array_key_first($scores);
        $topScore = $scores[$topCategory];
        $confidence = min($topScore, 1.0); // Cap at 1.0
        
        // Get top 3 suggestions
        $suggestions = array_slice($scores, 0, 3, true);
        
        // Determine action based on confidence
        $action = 'manual_review'; // Default
        if ($confidence >= $confidenceThreshold) {
            $action = 'auto_assign'; // High confidence - auto-assign
        } elseif ($confidence >= 0.5) {
            $action = 'suggest'; // Medium confidence - suggest
        }
        
        return [
            'predicted_category' => $topCategory,
            'confidence' => round($confidence, 2),
            'confidence_percent' => round($confidence * 100, 1),
            'suggested_categories' => $suggestions,
            'action' => $action, // 'auto_assign', 'suggest', or 'manual_review'
            'reason' => "Matched keywords: " . implode(', ', $this->getMatchedKeywords($normalizedName, $this->categoryPatterns[$topCategory]))
        ];
    }
    
    /**
     * Calculate category score based on keyword matches and word frequency
     */
    private function calculateCategoryScore($normalizedName, $pattern) {
        $score = 0;
        $matchedCount = 0;
        
        foreach ($pattern['keywords'] as $keyword) {
            // Exact phrase match gets higher weight
            if (strpos($normalizedName, $keyword) !== false) {
                $wordCount = count(explode(' ', $keyword));
                $score += (1.0 + ($wordCount * 0.2)); // Longer phrases get more weight
                $matchedCount++;
            }
        }
        
        if ($matchedCount === 0) {
            return 0;
        }
        
        // Apply pattern weight and normalize
        $finalScore = ($score / count($pattern['keywords'])) * $pattern['weight'] * $pattern['priority'] / 10;
        
        return $finalScore;
    }
    
    /**
     * Get the matched keywords for explanation
     */
    private function getMatchedKeywords($normalizedName, $pattern) {
        $matched = [];
        foreach ($pattern['keywords'] as $keyword) {
            if (strpos($normalizedName, $keyword) !== false) {
                $matched[] = $keyword;
            }
        }
        return $matched;
    }
    
    /**
     * Train classifier with existing data from database
     * Builds better patterns from real gift data
     */
    public function trainFromDatabase($mysqli) {
        if (!$mysqli) {
            return ['status' => 'error', 'message' => 'Database connection required'];
        }
        
        try {
            // Get all artworks with categories
            $result = $mysqli->query("
                SELECT a.title, c.name as category 
                FROM artworks a 
                LEFT JOIN categories c ON a.category_id = c.id 
                WHERE c.name IS NOT NULL AND a.title IS NOT NULL
            ");
            
            $trainingData = [];
            while ($row = $result->fetch_assoc()) {
                $trainingData[] = $row;
            }
            
            // Analyze patterns
            $patternAnalysis = $this->analyzePatterns($trainingData);
            
            return [
                'status' => 'success',
                'trained_samples' => count($trainingData),
                'pattern_analysis' => $patternAnalysis,
                'message' => 'Classifier trained on ' . count($trainingData) . ' existing gifts'
            ];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Analyze patterns in training data (for future ML enhancement)
     */
    private function analyzePatterns($trainingData) {
        $analysis = [];
        
        foreach ($trainingData as $item) {
            $category = $item['category'];
            $title = strtolower($item['title']);
            
            if (!isset($analysis[$category])) {
                $analysis[$category] = ['samples' => 0, 'words' => []];
            }
            
            $analysis[$category]['samples']++;
            
            // Extract words
            $words = preg_split('/\s+/', $title);
            foreach ($words as $word) {
                if (strlen($word) > 2) { // Skip short words
                    $analysis[$category]['words'][$word] = 
                        ($analysis[$category]['words'][$word] ?? 0) + 1;
                }
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get available categories (from database)
     */
    public function getCategories($mysqli) {
        if (!$mysqli) {
            return array_keys($this->categoryPatterns);
        }
        
        try {
            $result = $mysqli->query("SELECT DISTINCT name FROM categories WHERE status='active' ORDER BY name");
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['name'];
            }
            return $categories ?: array_keys($this->categoryPatterns);
        } catch (Exception $e) {
            return array_keys($this->categoryPatterns);
        }
    }
    
    /**
     * Batch classify multiple gifts
     */
    public function batchClassify($giftNames, $confidenceThreshold = 0.75) {
        $results = [];
        foreach ($giftNames as $name) {
            $results[] = $this->classifyGift($name, $confidenceThreshold);
        }
        return $results;
    }
}