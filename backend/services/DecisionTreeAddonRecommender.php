<?php

/**
 * Decision Tree Add-on Recommender
 * 
 * This class implements a decision tree algorithm to suggest add-ons
 * like greeting cards or ribbons based on gift price and other factors.
 */
class DecisionTreeAddonRecommender
{
    private $db;
    private $decisionRules = [];

    public function __construct($database)
    {
        $this->db = $database;
        $this->initializeDecisionRules();
    }

    /**
     * Initialize decision tree rules
     */
    private function initializeDecisionRules()
    {
        $this->decisionRules = [
            // Main price-based rules
            'price_based' => [
                [
                    'condition' => 'gift_price > 1000',
                    'action' => 'include_greeting_card',
                    'confidence' => 0.9,
                    'reason' => 'High-value gifts benefit from personal greeting cards'
                ],
                [
                    'condition' => 'gift_price <= 1000',
                    'action' => 'optional_ribbon',
                    'confidence' => 0.7,
                    'reason' => 'Mid-range gifts can be enhanced with decorative ribbons'
                ],
                [
                    'condition' => 'gift_price < 500',
                    'action' => 'basic_packaging',
                    'confidence' => 0.6,
                    'reason' => 'Budget-friendly gifts with simple packaging'
                ]
            ],

            // Category-based rules
            'category_based' => [
                [
                    'condition' => 'category == "wedding"',
                    'action' => 'premium_greeting_card',
                    'confidence' => 0.95,
                    'reason' => 'Wedding gifts require elegant greeting cards'
                ],
                [
                    'condition' => 'category == "birthday"',
                    'action' => 'colorful_ribbon',
                    'confidence' => 0.8,
                    'reason' => 'Birthday gifts look great with colorful ribbons'
                ],
                [
                    'condition' => 'category == "anniversary"',
                    'action' => 'romantic_greeting_card',
                    'confidence' => 0.9,
                    'reason' => 'Anniversary gifts need romantic greeting cards'
                ],
                [
                    'condition' => 'category == "valentine"',
                    'action' => 'heart_ribbon',
                    'confidence' => 0.85,
                    'reason' => 'Valentine gifts are perfect with heart-shaped ribbons'
                ],
                [
                    'condition' => 'category == "christmas"',
                    'action' => 'festive_greeting_card',
                    'confidence' => 0.9,
                    'reason' => 'Christmas gifts require festive greeting cards'
                ]
            ],

            // Occasion-based rules
            'occasion_based' => [
                [
                    'condition' => 'is_formal_occasion == true',
                    'action' => 'elegant_greeting_card',
                    'confidence' => 0.9,
                    'reason' => 'Formal occasions require elegant presentation'
                ],
                [
                    'condition' => 'is_casual_occasion == true',
                    'action' => 'fun_ribbon',
                    'confidence' => 0.75,
                    'reason' => 'Casual occasions can use fun, colorful ribbons'
                ]
            ],

            // Customer preference rules
            'customer_preference' => [
                [
                    'condition' => 'customer_prefers_premium == true',
                    'action' => 'premium_greeting_card',
                    'confidence' => 0.95,
                    'reason' => 'Premium customers prefer high-quality greeting cards'
                ],
                [
                    'condition' => 'customer_budget_conscious == true',
                    'action' => 'basic_ribbon',
                    'confidence' => 0.8,
                    'reason' => 'Budget-conscious customers prefer simple ribbons'
                ]
            ],

            // Seasonal rules
            'seasonal' => [
                [
                    'condition' => 'season == "winter" AND month == 12',
                    'action' => 'christmas_greeting_card',
                    'confidence' => 0.95,
                    'reason' => 'December gifts should have Christmas-themed cards'
                ],
                [
                    'condition' => 'season == "spring" AND month == 2',
                    'action' => 'valentine_ribbon',
                    'confidence' => 0.9,
                    'reason' => 'February gifts benefit from Valentine-themed ribbons'
                ],
                [
                    'condition' => 'season == "summer"',
                    'action' => 'bright_ribbon',
                    'confidence' => 0.7,
                    'reason' => 'Summer gifts look great with bright, cheerful ribbons'
                ]
            ],

            // Gift type rules
            'gift_type' => [
                [
                    'condition' => 'gift_type == "hamper"',
                    'action' => 'premium_greeting_card',
                    'confidence' => 0.9,
                    'reason' => 'Hampers require premium greeting cards for presentation'
                ],
                [
                    'condition' => 'gift_type == "bouquet"',
                    'action' => 'elegant_ribbon',
                    'confidence' => 0.85,
                    'reason' => 'Bouquets are enhanced with elegant ribbons'
                ],
                [
                    'condition' => 'gift_type == "frame"',
                    'action' => 'simple_greeting_card',
                    'confidence' => 0.7,
                    'reason' => 'Frames work well with simple, personal greeting cards'
                ]
            ]
        ];
    }

    /**
     * Get add-on recommendations based on decision tree
     * 
     * @param array $giftData Gift information
     * @return array Add-on recommendations
     */
    public function getAddonRecommendations($giftData)
    {
        $recommendations = [];
        $totalConfidence = 0;
        $ruleCount = 0;

        // Process each rule category
        foreach ($this->decisionRules as $category => $rules) {
            foreach ($rules as $rule) {
                if ($this->evaluateCondition($rule['condition'], $giftData)) {
                    $recommendation = [
                        'addon_type' => $rule['action'],
                        'confidence' => $rule['confidence'],
                        'reason' => $rule['reason'],
                        'category' => $category,
                        'priority' => $this->calculatePriority($category, $rule['confidence'])
                    ];

                    $recommendations[] = $recommendation;
                    $totalConfidence += $rule['confidence'];
                    $ruleCount++;
                }
            }
        }

        // Calculate overall confidence and sort by priority
        $overallConfidence = $ruleCount > 0 ? $totalConfidence / $ruleCount : 0;
        
        // Sort by priority (higher priority first)
        usort($recommendations, function($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        // Get add-on details
        $recommendations = $this->enrichRecommendations($recommendations);

        return [
            'recommendations' => $recommendations,
            'overall_confidence' => $overallConfidence,
            'rule_count' => $ruleCount,
            'decision_path' => $this->generateDecisionPath($giftData)
        ];
    }

    /**
     * Evaluate a condition against gift data
     * 
     * @param string $condition Condition string
     * @param array $giftData Gift data
     * @return bool Evaluation result
     */
    private function evaluateCondition($condition, $giftData)
    {
        // Replace variables with actual values
        $evaluatedCondition = $condition;
        
        // Price conditions
        if (isset($giftData['price'])) {
            $evaluatedCondition = str_replace('gift_price', $giftData['price'], $evaluatedCondition);
        }

        // Category conditions
        if (isset($giftData['category_name'])) {
            $evaluatedCondition = str_replace('category', '"' . strtolower($giftData['category_name']) . '"', $evaluatedCondition);
        }

        // Occasion conditions
        if (isset($giftData['occasion'])) {
            $evaluatedCondition = str_replace('is_formal_occasion', $giftData['occasion'] === 'wedding' || $giftData['occasion'] === 'anniversary' ? 'true' : 'false', $evaluatedCondition);
            $evaluatedCondition = str_replace('is_casual_occasion', $giftData['occasion'] === 'birthday' || $giftData['occasion'] === 'casual' ? 'true' : 'false', $evaluatedCondition);
        }

        // Customer preference conditions
        if (isset($giftData['customer_preferences'])) {
            $prefs = $giftData['customer_preferences'];
            $evaluatedCondition = str_replace('customer_prefers_premium', isset($prefs['premium']) && $prefs['premium'] ? 'true' : 'false', $evaluatedCondition);
            $evaluatedCondition = str_replace('customer_budget_conscious', isset($prefs['budget_conscious']) && $prefs['budget_conscious'] ? 'true' : 'false', $evaluatedCondition);
        }

        // Seasonal conditions
        $currentMonth = (int)date('n');
        $currentSeason = $this->getCurrentSeason($currentMonth);
        $evaluatedCondition = str_replace('month', $currentMonth, $evaluatedCondition);
        $evaluatedCondition = str_replace('season', '"' . $currentSeason . '"', $evaluatedCondition);

        // Gift type conditions
        if (isset($giftData['gift_type'])) {
            $evaluatedCondition = str_replace('gift_type', '"' . strtolower($giftData['gift_type']) . '"', $evaluatedCondition);
        }

        // Evaluate the condition safely
        try {
            // Use a simple evaluation for basic conditions
            if (preg_match('/(\w+)\s*([><=!]+)\s*([0-9.]+)/', $evaluatedCondition, $matches)) {
                $variable = $matches[1];
                $operator = $matches[2];
                $value = (float)$matches[3];
                
                $actualValue = $this->getVariableValue($variable, $giftData);
                
                switch ($operator) {
                    case '>':
                        return $actualValue > $value;
                    case '>=':
                        return $actualValue >= $value;
                    case '<':
                        return $actualValue < $value;
                    case '<=':
                        return $actualValue <= $value;
                    case '==':
                        return $actualValue == $value;
                    case '!=':
                        return $actualValue != $value;
                }
            }
            
            // Handle string comparisons
            if (preg_match('/(\w+)\s*==\s*"([^"]+)"/', $evaluatedCondition, $matches)) {
                $variable = $matches[1];
                $expectedValue = $matches[2];
                $actualValue = $this->getVariableValue($variable, $giftData);
                return strtolower($actualValue) === strtolower($expectedValue);
            }
            
            // Handle boolean conditions
            if (strpos($evaluatedCondition, '== true') !== false) {
                $variable = str_replace(' == true', '', $evaluatedCondition);
                return $this->getVariableValue($variable, $giftData) === true;
            }
            
            if (strpos($evaluatedCondition, '== false') !== false) {
                $variable = str_replace(' == false', '', $evaluatedCondition);
                return $this->getVariableValue($variable, $giftData) === false;
            }
            
        } catch (Exception $e) {
            error_log("Decision tree condition evaluation error: " . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Get variable value from gift data
     * 
     * @param string $variable Variable name
     * @param array $giftData Gift data
     * @return mixed Variable value
     */
    private function getVariableValue($variable, $giftData)
    {
        switch ($variable) {
            case 'gift_price':
                return isset($giftData['price']) ? (float)$giftData['price'] : 0;
            case 'category':
                return isset($giftData['category_name']) ? strtolower($giftData['category_name']) : '';
            case 'is_formal_occasion':
                return isset($giftData['occasion']) && in_array($giftData['occasion'], ['wedding', 'anniversary']);
            case 'is_casual_occasion':
                return isset($giftData['occasion']) && in_array($giftData['occasion'], ['birthday', 'casual']);
            case 'customer_prefers_premium':
                return isset($giftData['customer_preferences']['premium']) && $giftData['customer_preferences']['premium'];
            case 'customer_budget_conscious':
                return isset($giftData['customer_preferences']['budget_conscious']) && $giftData['customer_preferences']['budget_conscious'];
            case 'gift_type':
                return isset($giftData['gift_type']) ? strtolower($giftData['gift_type']) : '';
            default:
                return null;
        }
    }

    /**
     * Calculate priority for recommendation
     * 
     * @param string $category Rule category
     * @param float $confidence Confidence score
     * @return float Priority score
     */
    private function calculatePriority($category, $confidence)
    {
        $categoryWeights = [
            'price_based' => 1.0,
            'category_based' => 0.9,
            'occasion_based' => 0.8,
            'customer_preference' => 0.7,
            'seasonal' => 0.6,
            'gift_type' => 0.5
        ];

        $weight = $categoryWeights[$category] ?? 0.5;
        return $weight * $confidence;
    }

    /**
     * Get current season based on month
     * 
     * @param int $month Month number
     * @return string Season name
     */
    private function getCurrentSeason($month)
    {
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        if (in_array($month, [9, 10, 11])) return 'autumn';
        return 'unknown';
    }

    /**
     * Enrich recommendations with add-on details
     * 
     * @param array $recommendations Basic recommendations
     * @return array Enriched recommendations
     */
    private function enrichRecommendations($recommendations)
    {
        $addonTypes = [
            'include_greeting_card' => [
                'name' => 'Greeting Card',
                'description' => 'Personal greeting card with your message',
                'price' => 25,
                'type' => 'card',
                'image' => 'greeting_card.jpg'
            ],
            'optional_ribbon' => [
                'name' => 'Decorative Ribbon',
                'description' => 'Beautiful ribbon to enhance your gift',
                'price' => 15,
                'type' => 'ribbon',
                'image' => 'ribbon.jpg'
            ],
            'basic_packaging' => [
                'name' => 'Basic Packaging',
                'description' => 'Simple gift wrapping',
                'price' => 10,
                'type' => 'packaging',
                'image' => 'basic_packaging.jpg'
            ],
            'premium_greeting_card' => [
                'name' => 'Premium Greeting Card',
                'description' => 'High-quality greeting card with elegant design',
                'price' => 50,
                'type' => 'card',
                'image' => 'premium_card.jpg'
            ],
            'colorful_ribbon' => [
                'name' => 'Colorful Ribbon',
                'description' => 'Bright and cheerful ribbon for celebrations',
                'price' => 20,
                'type' => 'ribbon',
                'image' => 'colorful_ribbon.jpg'
            ],
            'romantic_greeting_card' => [
                'name' => 'Romantic Greeting Card',
                'description' => 'Romantic greeting card perfect for special occasions',
                'price' => 40,
                'type' => 'card',
                'image' => 'romantic_card.jpg'
            ],
            'heart_ribbon' => [
                'name' => 'Heart Ribbon',
                'description' => 'Heart-shaped ribbon for romantic gifts',
                'price' => 25,
                'type' => 'ribbon',
                'image' => 'heart_ribbon.jpg'
            ],
            'festive_greeting_card' => [
                'name' => 'Festive Greeting Card',
                'description' => 'Holiday-themed greeting card',
                'price' => 35,
                'type' => 'card',
                'image' => 'festive_card.jpg'
            ],
            'elegant_greeting_card' => [
                'name' => 'Elegant Greeting Card',
                'description' => 'Sophisticated greeting card for formal occasions',
                'price' => 45,
                'type' => 'card',
                'image' => 'elegant_card.jpg'
            ],
            'fun_ribbon' => [
                'name' => 'Fun Ribbon',
                'description' => 'Playful ribbon for casual celebrations',
                'price' => 18,
                'type' => 'ribbon',
                'image' => 'fun_ribbon.jpg'
            ],
            'premium_greeting_card' => [
                'name' => 'Premium Greeting Card',
                'description' => 'Luxury greeting card with premium materials',
                'price' => 75,
                'type' => 'card',
                'image' => 'premium_card.jpg'
            ],
            'basic_ribbon' => [
                'name' => 'Basic Ribbon',
                'description' => 'Simple ribbon for budget-conscious customers',
                'price' => 12,
                'type' => 'ribbon',
                'image' => 'basic_ribbon.jpg'
            ],
            'christmas_greeting_card' => [
                'name' => 'Christmas Greeting Card',
                'description' => 'Festive Christmas greeting card',
                'price' => 30,
                'type' => 'card',
                'image' => 'christmas_card.jpg'
            ],
            'valentine_ribbon' => [
                'name' => 'Valentine Ribbon',
                'description' => 'Romantic ribbon perfect for Valentine\'s Day',
                'price' => 22,
                'type' => 'ribbon',
                'image' => 'valentine_ribbon.jpg'
            ],
            'bright_ribbon' => [
                'name' => 'Bright Ribbon',
                'description' => 'Vibrant ribbon perfect for summer gifts',
                'price' => 20,
                'type' => 'ribbon',
                'image' => 'bright_ribbon.jpg'
            ],
            'elegant_ribbon' => [
                'name' => 'Elegant Ribbon',
                'description' => 'Sophisticated ribbon for elegant gifts',
                'price' => 35,
                'type' => 'ribbon',
                'image' => 'elegant_ribbon.jpg'
            ],
            'simple_greeting_card' => [
                'name' => 'Simple Greeting Card',
                'description' => 'Clean and simple greeting card',
                'price' => 20,
                'type' => 'card',
                'image' => 'simple_card.jpg'
            ]
        ];

        foreach ($recommendations as &$recommendation) {
            $addonType = $recommendation['addon_type'];
            if (isset($addonTypes[$addonType])) {
                $recommendation = array_merge($recommendation, $addonTypes[$addonType]);
            }
        }

        return $recommendations;
    }

    /**
     * Generate decision path explanation
     * 
     * @param array $giftData Gift data
     * @return array Decision path
     */
    private function generateDecisionPath($giftData)
    {
        $path = [];
        
        // Price-based decision
        if (isset($giftData['price'])) {
            if ($giftData['price'] > 1000) {
                $path[] = "Price ₹{$giftData['price']} > ₹1000 → Include Greeting Card";
            } else {
                $path[] = "Price ₹{$giftData['price']} ≤ ₹1000 → Optional Ribbon";
            }
        }

        // Category-based decision
        if (isset($giftData['category_name'])) {
            $category = strtolower($giftData['category_name']);
            $path[] = "Category: {$giftData['category_name']} → Category-specific add-on";
        }

        // Occasion-based decision
        if (isset($giftData['occasion'])) {
            $path[] = "Occasion: {$giftData['occasion']} → Occasion-appropriate add-on";
        }

        return $path;
    }

    /**
     * Get add-on recommendations for a specific gift
     * 
     * @param int $artworkId Artwork ID
     * @param int $userId User ID (optional)
     * @return array Add-on recommendations
     */
    public function getGiftAddonRecommendations($artworkId, $userId = null)
    {
        // Get artwork data
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.price, a.category_id, a.description,
                c.name as category_name
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.id = :artwork_id AND a.status = 'active'
        ");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        $artwork = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$artwork) {
            return ['error' => 'Artwork not found'];
        }

        // Prepare gift data
        $giftData = [
            'price' => (float)$artwork['price'],
            'category_name' => $artwork['category_name'],
            'title' => $artwork['title'],
            'description' => $artwork['description']
        ];

        // Add user preferences if available
        if ($userId) {
            $giftData['customer_preferences'] = $this->getCustomerPreferences($userId);
            $giftData['occasion'] = $this->detectOccasion($userId);
        }

        // Add gift type based on category
        $giftData['gift_type'] = $this->getGiftType($artwork['category_name']);

        return $this->getAddonRecommendations($giftData);
    }

    /**
     * Get customer preferences
     * 
     * @param int $userId User ID
     * @return array Customer preferences
     */
    private function getCustomerPreferences($userId)
    {
        // Get customer's average order value
        $stmt = $this->db->prepare("
            SELECT AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE user_id = :user_id AND status = 'delivered'
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $avgOrderValue = $result['avg_order_value'] ? (float)$result['avg_order_value'] : 0;
        
        return [
            'premium' => $avgOrderValue > 1000,
            'budget_conscious' => $avgOrderValue < 500
        ];
    }

    /**
     * Detect occasion from user's recent purchases
     * 
     * @param int $userId User ID
     * @return string Detected occasion
     */
    private function detectOccasion($userId)
    {
        // Simple occasion detection based on recent purchases
        $stmt = $this->db->prepare("
            SELECT c.name as category_name, COUNT(*) as count
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN artworks a ON oi.artwork_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE o.user_id = :user_id 
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY c.name
            ORDER BY count DESC
            LIMIT 1
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) return 'casual';
        
        $category = strtolower($result['category_name']);
        
        // Map categories to occasions
        $categoryOccasions = [
            'wedding card' => 'wedding',
            'gift box' => 'birthday',
            'bouquets' => 'anniversary',
            'custom chocolate' => 'valentine'
        ];
        
        return $categoryOccasions[$category] ?? 'casual';
    }

    /**
     * Get gift type based on category
     * 
     * @param string $categoryName Category name
     * @return string Gift type
     */
    private function getGiftType($categoryName)
    {
        $categoryTypes = [
            'Gift box' => 'hamper',
            'boquetes' => 'bouquet',
            'frames' => 'frame',
            'Wedding card' => 'card',
            'custom chocolate' => 'chocolate',
            'album' => 'album'
        ];
        
        return $categoryTypes[$categoryName] ?? 'gift';
    }
}








