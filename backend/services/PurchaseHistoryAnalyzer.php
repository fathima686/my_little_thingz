<?php

/**
 * Purchase History Analyzer for BPNN Recommendations
 * 
 * This class analyzes purchase history patterns to provide
 * category-based and occasion-based recommendations.
 */
class PurchaseHistoryAnalyzer
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Analyze user's purchase history for recommendation patterns
     * 
     * @param int $userId User ID
     * @return array Purchase analysis results
     */
    public function analyzePurchaseHistory($userId)
    {
        // Get user's purchase history
        $purchases = $this->getUserPurchases($userId);
        
        if (empty($purchases)) {
            return $this->getDefaultRecommendations();
        }

        $analysis = [
            'total_purchases' => count($purchases),
            'categories_purchased' => [],
            'occasions_detected' => [],
            'price_preferences' => [],
            'seasonal_patterns' => [],
            'recommendation_patterns' => [],
            'next_likely_categories' => []
        ];

        // Analyze categories
        $analysis['categories_purchased'] = $this->analyzeCategories($purchases);
        
        // Detect occasions from purchase patterns
        $analysis['occasions_detected'] = $this->detectOccasions($purchases);
        
        // Analyze price preferences
        $analysis['price_preferences'] = $this->analyzePricePreferences($purchases);
        
        // Detect seasonal patterns
        $analysis['seasonal_patterns'] = $this->analyzeSeasonalPatterns($purchases);
        
        // Generate recommendation patterns
        $analysis['recommendation_patterns'] = $this->generateRecommendationPatterns($analysis);
        
        // Predict next likely categories
        $analysis['next_likely_categories'] = $this->predictNextCategories($analysis);

        return $analysis;
    }

    /**
     * Get user's purchase history
     * 
     * @param int $userId User ID
     * @return array Purchase history
     */
    private function getUserPurchases($userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                o.id as order_id,
                o.created_at as purchase_date,
                oi.artwork_id,
                oi.quantity,
                oi.price,
                a.title,
                a.category_id,
                c.name as category_name,
                a.price as original_price,
                a.description,
                a.image_url
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN artworks a ON oi.artwork_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE o.user_id = :user_id 
            AND o.status = 'delivered'
            AND o.payment_status = 'paid'
            ORDER BY o.created_at DESC
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Analyze purchased categories
     * 
     * @param array $purchases Purchase history
     * @return array Category analysis
     */
    private function analyzeCategories($purchases)
    {
        $categoryCounts = [];
        $categoryValues = [];
        $categoryTimestamps = [];

        foreach ($purchases as $purchase) {
            $categoryId = (int)$purchase['category_id'];
            $categoryName = $purchase['category_name'];
            
            if (!isset($categoryCounts[$categoryId])) {
                $categoryCounts[$categoryId] = 0;
                $categoryValues[$categoryId] = 0;
                $categoryTimestamps[$categoryId] = [];
            }
            
            $categoryCounts[$categoryId]++;
            $categoryValues[$categoryId] += (float)$purchase['price'];
            $categoryTimestamps[$categoryId][] = $purchase['purchase_date'];
        }

        // Calculate category preferences
        $categoryAnalysis = [];
        foreach ($categoryCounts as $categoryId => $count) {
            $categoryAnalysis[$categoryId] = [
                'name' => $this->getCategoryName($categoryId, $purchases),
                'purchase_count' => $count,
                'total_value' => $categoryValues[$categoryId],
                'avg_value' => $categoryValues[$categoryId] / $count,
                'last_purchase' => max($categoryTimestamps[$categoryId]),
                'first_purchase' => min($categoryTimestamps[$categoryId]),
                'frequency_score' => $count / count($purchases),
                'value_score' => $categoryValues[$categoryId] / array_sum($categoryValues)
            ];
        }

        // Sort by frequency and value
        uasort($categoryAnalysis, function($a, $b) {
            return ($b['frequency_score'] + $b['value_score']) <=> ($a['frequency_score'] + $a['value_score']);
        });

        return $categoryAnalysis;
    }

    /**
     * Detect occasions from purchase patterns
     * 
     * @param array $purchases Purchase history
     * @return array Detected occasions
     */
    private function detectOccasions($purchases)
    {
        $occasions = [];
        
        // Define occasion patterns
        $occasionPatterns = [
            'wedding' => [
                'keywords' => ['wedding', 'bride', 'groom', 'marriage', 'ceremony'],
                'categories' => [6, 1, 2], // Wedding cards, Gift boxes, Bouquets
                'time_patterns' => ['spring', 'summer']
            ],
            'birthday' => [
                'keywords' => ['birthday', 'party', 'celebration', 'cake'],
                'categories' => [1, 5, 3], // Gift boxes, Chocolates, Frames
                'time_patterns' => ['any']
            ],
            'anniversary' => [
                'keywords' => ['anniversary', 'romantic', 'love', 'couple'],
                'categories' => [1, 2, 5], // Gift boxes, Bouquets, Chocolates
                'time_patterns' => ['any']
            ],
            'valentine' => [
                'keywords' => ['valentine', 'love', 'romantic', 'heart'],
                'categories' => [2, 5, 1], // Bouquets, Chocolates, Gift boxes
                'time_patterns' => ['february']
            ],
            'christmas' => [
                'keywords' => ['christmas', 'holiday', 'festive', 'gift'],
                'categories' => [1, 5, 3], // Gift boxes, Chocolates, Frames
                'time_patterns' => ['december']
            ]
        ];

        foreach ($purchases as $purchase) {
            $title = strtolower($purchase['title']);
            $description = strtolower($purchase['description']);
            $categoryId = (int)$purchase['category_id'];
            $purchaseMonth = date('n', strtotime($purchase['purchase_date']));

            foreach ($occasionPatterns as $occasion => $pattern) {
                $score = 0;
                
                // Check keywords in title and description
                foreach ($pattern['keywords'] as $keyword) {
                    if (strpos($title, $keyword) !== false || strpos($description, $keyword) !== false) {
                        $score += 2;
                    }
                }
                
                // Check category match
                if (in_array($categoryId, $pattern['categories'])) {
                    $score += 1;
                }
                
                // Check time pattern
                if ($this->matchesTimePattern($purchaseMonth, $pattern['time_patterns'])) {
                    $score += 1;
                }
                
                if ($score >= 2) {
                    if (!isset($occasions[$occasion])) {
                        $occasions[$occasion] = [
                            'score' => 0,
                            'purchases' => [],
                            'confidence' => 0
                        ];
                    }
                    $occasions[$occasion]['score'] += $score;
                    $occasions[$occasion]['purchases'][] = $purchase;
                }
            }
        }

        // Calculate confidence scores
        foreach ($occasions as $occasion => $data) {
            $occasions[$occasion]['confidence'] = min(1.0, $data['score'] / 10.0);
        }

        // Sort by confidence
        uasort($occasions, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $occasions;
    }

    /**
     * Analyze price preferences
     * 
     * @param array $purchases Purchase history
     * @return array Price analysis
     */
    private function analyzePricePreferences($purchases)
    {
        $prices = array_column($purchases, 'price');
        
        if (empty($prices)) {
            return [
                'min_price' => 0,
                'max_price' => 1000,
                'avg_price' => 500,
                'price_range' => 'medium',
                'preferred_range' => [100, 1000]
            ];
        }

        $minPrice = min($prices);
        $maxPrice = max($prices);
        $avgPrice = array_sum($prices) / count($prices);
        
        // Determine price range preference
        $priceRange = 'low';
        if ($avgPrice > 1000) {
            $priceRange = 'high';
        } elseif ($avgPrice > 500) {
            $priceRange = 'medium';
        }

        // Calculate preferred range (80% of purchases within this range)
        sort($prices);
        $lowerBound = $prices[floor(count($prices) * 0.1)];
        $upperBound = $prices[floor(count($prices) * 0.9)];

        return [
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'avg_price' => $avgPrice,
            'price_range' => $priceRange,
            'preferred_range' => [$lowerBound, $upperBound],
            'price_consistency' => $this->calculatePriceConsistency($prices)
        ];
    }

    /**
     * Analyze seasonal patterns
     * 
     * @param array $purchases Purchase history
     * @return array Seasonal analysis
     */
    private function analyzeSeasonalPatterns($purchases)
    {
        $monthlyPurchases = [];
        $seasonalCategories = [];

        foreach ($purchases as $purchase) {
            $month = (int)date('n', strtotime($purchase['purchase_date']));
            $categoryId = (int)$purchase['category_id'];
            
            if (!isset($monthlyPurchases[$month])) {
                $monthlyPurchases[$month] = 0;
            }
            $monthlyPurchases[$month]++;
            
            if (!isset($seasonalCategories[$month])) {
                $seasonalCategories[$month] = [];
            }
            if (!isset($seasonalCategories[$month][$categoryId])) {
                $seasonalCategories[$month][$categoryId] = 0;
            }
            $seasonalCategories[$month][$categoryId]++;
        }

        // Find peak months
        $peakMonths = array_keys($monthlyPurchases, max($monthlyPurchases));
        
        // Determine seasonal preferences
        $seasons = [
            'spring' => [3, 4, 5],
            'summer' => [6, 7, 8],
            'autumn' => [9, 10, 11],
            'winter' => [12, 1, 2]
        ];

        $seasonalPreferences = [];
        foreach ($seasons as $season => $months) {
            $seasonalPreferences[$season] = 0;
            foreach ($months as $month) {
                $seasonalPreferences[$season] += $monthlyPurchases[$month] ?? 0;
            }
        }

        return [
            'peak_months' => $peakMonths,
            'monthly_distribution' => $monthlyPurchases,
            'seasonal_preferences' => $seasonalPreferences,
            'seasonal_categories' => $seasonalCategories,
            'most_active_season' => array_keys($seasonalPreferences, max($seasonalPreferences))[0]
        ];
    }

    /**
     * Generate recommendation patterns based on analysis
     * 
     * @param array $analysis Complete analysis
     * @return array Recommendation patterns
     */
    private function generateRecommendationPatterns($analysis)
    {
        $patterns = [];

        // Category progression patterns
        $patterns['category_progression'] = $this->getCategoryProgressionPatterns($analysis['categories_purchased']);
        
        // Occasion-based patterns
        $patterns['occasion_patterns'] = $this->getOccasionBasedPatterns($analysis['occasions_detected']);
        
        // Price progression patterns
        $patterns['price_progression'] = $this->getPriceProgressionPatterns($analysis['price_preferences']);
        
        // Seasonal patterns
        $patterns['seasonal_patterns'] = $this->getSeasonalRecommendationPatterns($analysis['seasonal_patterns']);

        return $patterns;
    }

    /**
     * Get category progression patterns
     * 
     * @param array $categories Category analysis
     * @return array Progression patterns
     */
    private function getCategoryProgressionPatterns($categories)
    {
        $progressionRules = [
            // If bought wedding cards, suggest wedding hampers
            6 => [1, 2], // Wedding cards -> Gift boxes, Bouquets
            
            // If bought frames, suggest albums
            3 => [8], // Frames -> Albums
            
            // If bought chocolates, suggest gift boxes
            5 => [1], // Chocolates -> Gift boxes
            
            // If bought bouquets, suggest gift boxes
            2 => [1, 5], // Bouquets -> Gift boxes, Chocolates
            
            // If bought albums, suggest frames
            8 => [3], // Albums -> Frames
            
            // If bought gift boxes, suggest related items
            1 => [2, 5, 3] // Gift boxes -> Bouquets, Chocolates, Frames
        ];

        $patterns = [];
        foreach ($categories as $categoryId => $data) {
            if (isset($progressionRules[$categoryId])) {
                $patterns[$categoryId] = [
                    'purchased_category' => $data['name'],
                    'suggested_categories' => $progressionRules[$categoryId],
                    'confidence' => $data['frequency_score']
                ];
            }
        }

        return $patterns;
    }

    /**
     * Get occasion-based patterns
     * 
     * @param array $occasions Occasion analysis
     * @return array Occasion patterns
     */
    private function getOccasionBasedPatterns($occasions)
    {
        $occasionRecommendations = [
            'wedding' => [
                'primary' => [1, 2], // Gift boxes, Bouquets
                'secondary' => [5, 3], // Chocolates, Frames
                'message' => 'Complete your wedding celebration with these items'
            ],
            'birthday' => [
                'primary' => [1, 5], // Gift boxes, Chocolates
                'secondary' => [3, 8], // Frames, Albums
                'message' => 'Perfect birthday gift ideas'
            ],
            'anniversary' => [
                'primary' => [2, 5], // Bouquets, Chocolates
                'secondary' => [1, 3], // Gift boxes, Frames
                'message' => 'Romantic anniversary gifts'
            ],
            'valentine' => [
                'primary' => [2, 5], // Bouquets, Chocolates
                'secondary' => [1], // Gift boxes
                'message' => 'Valentine\'s day specials'
            ],
            'christmas' => [
                'primary' => [1, 5], // Gift boxes, Chocolates
                'secondary' => [3, 8], // Frames, Albums
                'message' => 'Holiday gift collection'
            ]
        ];

        $patterns = [];
        foreach ($occasions as $occasion => $data) {
            if (isset($occasionRecommendations[$occasion])) {
                $patterns[$occasion] = array_merge(
                    $occasionRecommendations[$occasion],
                    ['confidence' => $data['confidence']]
                );
            }
        }

        return $patterns;
    }

    /**
     * Predict next likely categories to purchase
     * 
     * @param array $analysis Complete analysis
     * @return array Predicted categories
     */
    private function predictNextCategories($analysis)
    {
        $predictions = [];
        
        // Based on category progression
        foreach ($analysis['recommendation_patterns']['category_progression'] as $categoryId => $pattern) {
            foreach ($pattern['suggested_categories'] as $suggestedCategoryId) {
                if (!isset($predictions[$suggestedCategoryId])) {
                    $predictions[$suggestedCategoryId] = 0;
                }
                $predictions[$suggestedCategoryId] += $pattern['confidence'];
            }
        }
        
        // Based on occasions
        foreach ($analysis['recommendation_patterns']['occasion_patterns'] as $occasion => $pattern) {
            foreach ($pattern['primary'] as $categoryId) {
                if (!isset($predictions[$categoryId])) {
                    $predictions[$categoryId] = 0;
                }
                $predictions[$categoryId] += $pattern['confidence'] * 1.5; // Higher weight for primary
            }
            foreach ($pattern['secondary'] as $categoryId) {
                if (!isset($predictions[$categoryId])) {
                    $predictions[$categoryId] = 0;
                }
                $predictions[$categoryId] += $pattern['confidence'] * 1.0; // Normal weight for secondary
            }
        }
        
        // Sort by prediction score
        arsort($predictions);
        
        return $predictions;
    }

    /**
     * Get recommendations based on purchase history
     * 
     * @param int $userId User ID
     * @param int $limit Number of recommendations
     * @return array Recommendations
     */
    public function getHistoryBasedRecommendations($userId, $limit = 8)
    {
        $analysis = $this->analyzePurchaseHistory($userId);
        
        if (empty($analysis['next_likely_categories'])) {
            return $this->getDefaultRecommendations($limit);
        }

        // Get top predicted categories
        $topCategories = array_slice($analysis['next_likely_categories'], 0, 3, true);
        
        $recommendations = [];
        foreach ($topCategories as $categoryId => $score) {
            $categoryRecommendations = $this->getRecommendationsForCategory($categoryId, $analysis, $limit);
            $recommendations = array_merge($recommendations, $categoryRecommendations);
        }

        // Remove duplicates and sort by score
        $uniqueRecommendations = [];
        foreach ($recommendations as $rec) {
            $key = $rec['artwork_id'];
            if (!isset($uniqueRecommendations[$key]) || $uniqueRecommendations[$key]['score'] < $rec['score']) {
                $uniqueRecommendations[$key] = $rec;
            }
        }

        // Sort by score and limit
        usort($uniqueRecommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($uniqueRecommendations, 0, $limit);
    }

    /**
     * Get recommendations for specific category
     * 
     * @param int $categoryId Category ID
     * @param array $analysis User analysis
     * @param int $limit Number of recommendations
     * @return array Category recommendations
     */
    private function getRecommendationsForCategory($categoryId, $analysis, $limit)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id as artwork_id,
                a.title,
                a.description,
                a.price,
                a.image_url,
                a.category_id,
                c.name as category_name,
                a.offer_price,
                a.offer_percent,
                a.offer_starts_at,
                a.offer_ends_at
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.category_id = :category_id
            AND a.status = 'active'
            ORDER BY 
                CASE WHEN a.offer_price IS NOT NULL AND a.offer_starts_at <= NOW() AND a.offer_ends_at >= NOW() THEN 1 ELSE 0 END DESC,
                a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $recommendations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $score = $this->calculateRecommendationScore($row, $analysis);
            
            $recommendations[] = [
                'artwork_id' => (int)$row['artwork_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'image_url' => $row['image_url'],
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'],
                'score' => $score,
                'reason' => $this->getRecommendationReason($row, $analysis),
                'has_offer' => !empty($row['offer_price']),
                'offer_price' => $row['offer_price'] ? (float)$row['offer_price'] : null
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate recommendation score
     * 
     * @param array $artwork Artwork data
     * @param array $analysis User analysis
     * @return float Recommendation score
     */
    private function calculateRecommendationScore($artwork, $analysis)
    {
        $score = 0.5; // Base score
        
        // Price preference match
        $price = (float)$artwork['price'];
        $pricePrefs = $analysis['price_preferences'];
        if ($price >= $pricePrefs['preferred_range'][0] && $price <= $pricePrefs['preferred_range'][1]) {
            $score += 0.3;
        }
        
        // Offer bonus
        if (!empty($artwork['offer_price'])) {
            $score += 0.2;
        }
        
        // Category preference
        if (isset($analysis['categories_purchased'][$artwork['category_id']])) {
            $score += $analysis['categories_purchased'][$artwork['category_id']]['frequency_score'] * 0.5;
        }
        
        return min(1.0, $score);
    }

    /**
     * Get recommendation reason
     * 
     * @param array $artwork Artwork data
     * @param array $analysis User analysis
     * @return string Recommendation reason
     */
    private function getRecommendationReason($artwork, $analysis)
    {
        $categoryId = (int)$artwork['category_id'];
        $reasons = [];
        
        // Check category progression
        foreach ($analysis['recommendation_patterns']['category_progression'] as $purchasedCat => $pattern) {
            if (in_array($categoryId, $pattern['suggested_categories'])) {
                $reasons[] = "Based on your purchase of {$pattern['purchased_category']}";
            }
        }
        
        // Check occasion patterns
        foreach ($analysis['recommendation_patterns']['occasion_patterns'] as $occasion => $pattern) {
            if (in_array($categoryId, $pattern['primary']) || in_array($categoryId, $pattern['secondary'])) {
                $reasons[] = $pattern['message'];
            }
        }
        
        return !empty($reasons) ? implode(', ', $reasons) : 'Recommended for you';
    }

    /**
     * Get default recommendations when no history
     * 
     * @param int $limit Number of recommendations
     * @return array Default recommendations
     */
    private function getDefaultRecommendations($limit = 8)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id as artwork_id,
                a.title,
                a.description,
                a.price,
                a.image_url,
                a.category_id,
                c.name as category_name
            FROM artworks a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $recommendations = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recommendations[] = [
                'artwork_id' => (int)$row['artwork_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'image_url' => $row['image_url'],
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'],
                'score' => 0.5,
                'reason' => 'Popular items'
            ];
        }

        return $recommendations;
    }

    /**
     * Helper methods
     */
    private function getCategoryName($categoryId, $purchases)
    {
        foreach ($purchases as $purchase) {
            if ((int)$purchase['category_id'] === $categoryId) {
                return $purchase['category_name'];
            }
        }
        return 'Unknown';
    }

    private function matchesTimePattern($month, $patterns)
    {
        if (in_array('any', $patterns)) return true;
        
        $monthPatterns = [
            'spring' => [3, 4, 5],
            'summer' => [6, 7, 8],
            'autumn' => [9, 10, 11],
            'winter' => [12, 1, 2],
            'february' => [2],
            'december' => [12]
        ];
        
        foreach ($patterns as $pattern) {
            if (isset($monthPatterns[$pattern]) && in_array($month, $monthPatterns[$pattern])) {
                return true;
            }
        }
        
        return false;
    }

    private function calculatePriceConsistency($prices)
    {
        if (count($prices) < 2) return 1.0;
        
        $avg = array_sum($prices) / count($prices);
        $variance = array_sum(array_map(function($price) use ($avg) {
            return pow($price - $avg, 2);
        }, $prices)) / count($prices);
        
        $stdDev = sqrt($variance);
        return max(0, 1 - ($stdDev / $avg));
    }

    private function getPriceProgressionPatterns($pricePrefs)
    {
        return [
            'preferred_range' => $pricePrefs['preferred_range'],
            'price_consistency' => $pricePrefs['price_consistency'],
            'trend' => $pricePrefs['avg_price'] > 500 ? 'premium' : 'budget'
        ];
    }

    private function getSeasonalRecommendationPatterns($seasonal)
    {
        return [
            'peak_months' => $seasonal['peak_months'],
            'most_active_season' => $seasonal['most_active_season'],
            'seasonal_categories' => $seasonal['seasonal_categories']
        ];
    }
}
















