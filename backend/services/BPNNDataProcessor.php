<?php

require_once 'BPNNNeuralNetwork.php';
require_once 'PurchaseHistoryAnalyzer.php';

/**
 * BPNN Data Processor for Gift Preference Prediction
 * 
 * This class handles data preprocessing, feature extraction,
 * and training data preparation for the BPNN recommendation system.
 */
class BPNNDataProcessor
{
    private $db;
    private $normalizationParams = [];
    private $purchaseAnalyzer;

    public function __construct($database)
    {
        $this->db = $database;
        $this->purchaseAnalyzer = new PurchaseHistoryAnalyzer($database);
    }

    /**
     * Extract features from user behavior and artwork data
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @return array Feature vector
     */
    public function extractFeatures($userId, $artworkId)
    {
        $features = [];
        
        // Get artwork information
        $artworkData = $this->getArtworkData($artworkId);
        if (!$artworkData) {
            return null;
        }
        
        // Get user behavior data
        $userBehavior = $this->getUserBehaviorData($userId, $artworkId);
        
        // Get user preference profile
        $userProfile = $this->getUserProfile($userId);
        
        // Feature 1: Category preference score (0-1)
        $features[] = $this->calculateCategoryPreference($userId, $artworkData['category_id']);
        
        // Feature 2: Price preference score (0-1)
        $features[] = $this->calculatePricePreference($userId, $artworkData['price']);
        
        // Feature 3: User activity level (0-1)
        $features[] = $this->calculateUserActivityLevel($userId);
        
        // Feature 4: Artwork popularity score (0-1)
        $features[] = $this->calculateArtworkPopularity($artworkId);
        
        // Feature 5: Price range preference (normalized)
        $features[] = $this->normalizePrice($artworkData['price']);
        
        // Feature 6: Category affinity (based on past purchases)
        $features[] = $this->calculateCategoryAffinity($userId, $artworkData['category_id']);
        
        // Feature 7: Seasonal preference (if applicable)
        $features[] = $this->calculateSeasonalPreference($artworkData['category_id']);
        
        // Feature 8: User engagement with similar items
        $features[] = $this->calculateSimilarItemEngagement($userId, $artworkId);
        
        // Feature 9: Offer preference
        $features[] = $artworkData['has_offer'] ? 1.0 : 0.0;
        
        // Feature 10: Trending preference
        $features[] = $artworkData['is_trending'] ? 1.0 : 0.0;
        
        // Feature 11: Purchase history category progression
        $features[] = $this->calculateCategoryProgressionScore($userId, $artworkData['category_id']);
        
        // Feature 12: Occasion-based recommendation score
        $features[] = $this->calculateOccasionRecommendationScore($userId, $artworkData);
        
        // Feature 13: Purchase pattern consistency
        $features[] = $this->calculatePurchasePatternConsistency($userId, $artworkData);
        
        return $features;
    }

    /**
     * Get artwork data with additional computed fields
     * 
     * @param int $artworkId Artwork ID
     * @return array Artwork data
     */
    private function getArtworkData($artworkId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.price, a.category_id, a.created_at,
                a.offer_price, a.offer_percent, a.offer_starts_at, a.offer_ends_at,
                a.force_offer_badge,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_starts_at <= NOW() AND a.offer_ends_at >= NOW() 
                    THEN 1 
                    ELSE 0 
                END as has_offer,
                CASE 
                    WHEN a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                    THEN 1 
                    ELSE 0 
                END as is_trending
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id = :artwork_id AND a.status = 'active'
        ");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get user behavior data for specific artwork
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @return array Behavior data
     */
    private function getUserBehaviorData($userId, $artworkId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                behavior_type,
                COUNT(*) as count,
                AVG(rating_value) as avg_rating
            FROM user_behavior 
            WHERE user_id = :user_id AND artwork_id = :artwork_id
            GROUP BY behavior_type
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        
        $behaviors = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $behaviors[$row['behavior_type']] = [
                'count' => (int)$row['count'],
                'avg_rating' => $row['avg_rating'] ? (float)$row['avg_rating'] : null
            ];
        }
        
        return $behaviors;
    }

    /**
     * Get user preference profile
     * 
     * @param int $userId User ID
     * @return array User profile
     */
    private function getUserProfile($userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM user_preference_profiles 
            WHERE user_id = :user_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$profile) {
            // Create default profile if doesn't exist
            $this->createUserProfile($userId);
            return $this->getUserProfile($userId);
        }
        
        return $profile;
    }

    /**
     * Calculate category preference score
     * 
     * @param int $userId User ID
     * @param int $categoryId Category ID
     * @return float Preference score (0-1)
     */
    private function calculateCategoryPreference($userId, $categoryId)
    {
        // Get user's interaction with this category
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT ub.artwork_id) as unique_items,
                COUNT(ub.id) as total_interactions,
                AVG(ub.rating_value) as avg_rating
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.user_id = :user_id AND a.category_id = :category_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data || $data['total_interactions'] == 0) {
            return 0.5; // Neutral preference
        }
        
        // Calculate preference based on interactions and ratings
        $interactionScore = min(1.0, $data['total_interactions'] / 10.0); // Normalize to 0-1
        $ratingScore = $data['avg_rating'] ? ($data['avg_rating'] - 1) / 4 : 0.5; // Normalize 1-5 to 0-1
        
        return ($interactionScore + $ratingScore) / 2;
    }

    /**
     * Calculate price preference score
     * 
     * @param int $userId User ID
     * @param float $price Item price
     * @return float Preference score (0-1)
     */
    private function calculatePricePreference($userId, $price)
    {
        // Get user's price range from past purchases
        $stmt = $this->db->prepare("
            SELECT 
                MIN(a.price) as min_price,
                MAX(a.price) as max_price,
                AVG(a.price) as avg_price
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.user_id = :user_id AND ub.behavior_type = 'purchase'
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data || !$data['min_price']) {
            return 0.5; // Neutral preference
        }
        
        // Calculate how well the price fits user's range
        $minPrice = (float)$data['min_price'];
        $maxPrice = (float)$data['max_price'];
        $avgPrice = (float)$data['avg_price'];
        
        if ($price < $minPrice) {
            return 0.2; // Below user's typical range
        } elseif ($price > $maxPrice) {
            return 0.8; // Above user's typical range
        } else {
            // Within range, calculate based on distance from average
            $distance = abs($price - $avgPrice) / $avgPrice;
            return max(0.3, 1.0 - $distance);
        }
    }

    /**
     * Calculate user activity level
     * 
     * @param int $userId User ID
     * @return float Activity level (0-1)
     */
    private function calculateUserActivityLevel($userId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_actions
            FROM user_behavior 
            WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalActions = (int)$data['total_actions'];
        
        // Normalize to 0-1 (assuming 100+ actions = very active)
        return min(1.0, $totalActions / 100.0);
    }

    /**
     * Calculate artwork popularity score
     * 
     * @param int $artworkId Artwork ID
     * @return float Popularity score (0-1)
     */
    private function calculateArtworkPopularity($artworkId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT ub.user_id) as unique_users,
                COUNT(ub.id) as total_interactions,
                AVG(ub.rating_value) as avg_rating
            FROM user_behavior ub
            WHERE ub.artwork_id = :artwork_id
        ");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data || $data['total_interactions'] == 0) {
            return 0.1; // Low popularity for new items
        }
        
        // Combine unique users and total interactions
        $userScore = min(1.0, $data['unique_users'] / 50.0); // Normalize unique users
        $interactionScore = min(1.0, $data['total_interactions'] / 100.0); // Normalize interactions
        $ratingScore = $data['avg_rating'] ? ($data['avg_rating'] - 1) / 4 : 0.5;
        
        return ($userScore + $interactionScore + $ratingScore) / 3;
    }

    /**
     * Normalize price to 0-1 range
     * 
     * @param float $price Item price
     * @return float Normalized price
     */
    private function normalizePrice($price)
    {
        // Get price range from all artworks
        $stmt = $this->db->prepare("
            SELECT MIN(price) as min_price, MAX(price) as max_price
            FROM artworks WHERE status = 'active'
        ");
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $minPrice = (float)$data['min_price'];
        $maxPrice = (float)$data['max_price'];
        
        if ($maxPrice == $minPrice) {
            return 0.5;
        }
        
        return ($price - $minPrice) / ($maxPrice - $minPrice);
    }

    /**
     * Calculate category affinity based on past purchases
     * 
     * @param int $userId User ID
     * @param int $categoryId Category ID
     * @return float Affinity score (0-1)
     */
    private function calculateCategoryAffinity($userId, $categoryId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as purchase_count
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.user_id = :user_id AND a.category_id = :category_id AND ub.behavior_type = 'purchase'
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $purchaseCount = (int)$data['purchase_count'];
        
        // Normalize based on total purchases
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_purchases
            FROM user_behavior ub
            WHERE ub.user_id = :user_id AND ub.behavior_type = 'purchase'
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $totalData = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPurchases = (int)$totalData['total_purchases'];
        
        if ($totalPurchases == 0) {
            return 0.5; // Neutral affinity
        }
        
        return min(1.0, $purchaseCount / $totalPurchases * 5); // Scale up for better sensitivity
    }

    /**
     * Calculate seasonal preference
     * 
     * @param int $categoryId Category ID
     * @return float Seasonal score (0-1)
     */
    private function calculateSeasonalPreference($categoryId)
    {
        $month = (int)date('n');
        
        // Define seasonal preferences for categories
        $seasonalCategories = [
            // Wedding cards and gifts are popular in spring/summer
            6 => [3, 4, 5, 6, 7, 8], // Wedding card
            1 => [3, 4, 5, 6, 7, 8], // Gift box
            // Chocolates are popular in winter
            5 => [11, 12, 1, 2], // Custom chocolate
            // Frames and albums are year-round
            3 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], // Frames
            8 => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], // Album
        ];
        
        if (isset($seasonalCategories[$categoryId])) {
            return in_array($month, $seasonalCategories[$categoryId]) ? 1.0 : 0.3;
        }
        
        return 0.7; // Default moderate preference
    }

    /**
     * Calculate engagement with similar items
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @return float Engagement score (0-1)
     */
    private function calculateSimilarItemEngagement($userId, $artworkId)
    {
        // Get the artwork's category and price range
        $artworkData = $this->getArtworkData($artworkId);
        if (!$artworkData) {
            return 0.5;
        }
        
        $categoryId = $artworkData['category_id'];
        $price = (float)$artworkData['price'];
        $priceRange = $price * 0.2; // 20% price range
        
        // Find similar items user has interacted with
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as similar_interactions
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.user_id = :user_id 
            AND a.category_id = :category_id 
            AND a.price BETWEEN :min_price AND :max_price
            AND a.id != :artwork_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_price', $price - $priceRange, PDO::PARAM_STR);
        $stmt->bindValue(':max_price', $price + $priceRange, PDO::PARAM_STR);
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $similarInteractions = (int)$data['similar_interactions'];
        
        return min(1.0, $similarInteractions / 5.0); // Normalize to 0-1
    }

    /**
     * Create user preference profile
     * 
     * @param int $userId User ID
     */
    private function createUserProfile($userId)
    {
        // Analyze user's behavior to create profile
        $stmt = $this->db->prepare("
            SELECT 
                a.category_id,
                a.price,
                ub.rating_value,
                COUNT(*) as interaction_count
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE ub.user_id = :user_id
            GROUP BY a.category_id, a.price, ub.rating_value
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $behaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $preferredCategories = [];
        $prices = [];
        $ratings = [];
        
        foreach ($behaviors as $behavior) {
            if ($behavior['category_id']) {
                $preferredCategories[] = (int)$behavior['category_id'];
            }
            $prices[] = (float)$behavior['price'];
            if ($behavior['rating_value']) {
                $ratings[] = (float)$behavior['rating_value'];
            }
        }
        
        $categoryCounts = array_count_values($preferredCategories);
        arsort($categoryCounts);
        $topCategories = array_slice(array_keys($categoryCounts), 0, 3);
        
        $minPrice = $prices ? min($prices) : 0;
        $maxPrice = $prices ? max($prices) : 1000;
        $avgRating = $ratings ? array_sum($ratings) / count($ratings) : 3.0;
        
        // Insert profile
        $stmt = $this->db->prepare("
            INSERT INTO user_preference_profiles 
            (user_id, preferred_categories, price_range_min, price_range_max, avg_rating_preference, activity_score)
            VALUES (:user_id, :categories, :min_price, :max_price, :avg_rating, :activity_score)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':categories', json_encode($topCategories), PDO::PARAM_STR);
        $stmt->bindValue(':min_price', $minPrice, PDO::PARAM_STR);
        $stmt->bindValue(':max_price', $maxPrice, PDO::PARAM_STR);
        $stmt->bindValue(':avg_rating', $avgRating, PDO::PARAM_STR);
        $stmt->bindValue(':activity_score', min(1.0, count($behaviors) / 50.0), PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Prepare training data for BPNN
     * 
     * @param int $limit Maximum number of training samples
     * @return array Training data
     */
    public function prepareTrainingData($limit = 1000)
    {
        // Get user behavior data with targets
        $stmt = $this->db->prepare("
            SELECT 
                ub.user_id,
                ub.artwork_id,
                ub.behavior_type,
                ub.rating_value,
                a.category_id,
                a.price,
                a.created_at
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            WHERE a.status = 'active'
            ORDER BY ub.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $behaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $trainingData = [];
        $processedPairs = [];
        
        foreach ($behaviors as $behavior) {
            $userId = (int)$behavior['user_id'];
            $artworkId = (int)$behavior['artwork_id'];
            $pairKey = $userId . '_' . $artworkId;
            
            // Avoid duplicate processing
            if (in_array($pairKey, $processedPairs)) {
                continue;
            }
            $processedPairs[] = $pairKey;
            
            // Extract features
            $features = $this->extractFeatures($userId, $artworkId);
            if (!$features) {
                continue;
            }
            
            // Calculate target based on behavior
            $target = $this->calculateTarget($behavior);
            
            $trainingData[] = [$features, [$target]];
        }
        
        return $trainingData;
    }

    /**
     * Calculate target value for training
     * 
     * @param array $behavior Behavior data
     * @return float Target value (0-1)
     */
    private function calculateTarget($behavior)
    {
        $behaviorType = $behavior['behavior_type'];
        $rating = $behavior['rating_value'] ? (float)$behavior['rating_value'] : null;
        
        switch ($behaviorType) {
            case 'purchase':
                return 1.0; // Highest preference
            case 'add_to_wishlist':
                return 0.8; // High preference
            case 'add_to_cart':
                return 0.7; // Good preference
            case 'rating':
                return $rating ? ($rating - 1) / 4 : 0.5; // Normalize 1-5 to 0-1
            case 'view':
                return 0.3; // Low preference
            case 'remove_from_wishlist':
                return 0.1; // Very low preference
            default:
                return 0.5; // Neutral
        }
    }

    /**
     * Get normalization parameters for features
     * 
     * @return array Normalization parameters
     */
    public function getNormalizationParams()
    {
        if (empty($this->normalizationParams)) {
            $this->calculateNormalizationParams();
        }
        
        return $this->normalizationParams;
    }

    /**
     * Calculate normalization parameters from training data
     */
    private function calculateNormalizationParams()
    {
        $trainingData = $this->prepareTrainingData(5000); // Use more data for better normalization
        
        $featureCount = count($trainingData[0][0]);
        $minValues = array_fill(0, $featureCount, PHP_FLOAT_MAX);
        $maxValues = array_fill(0, $featureCount, PHP_FLOAT_MIN);
        
        foreach ($trainingData as $sample) {
            $features = $sample[0];
            for ($i = 0; $i < $featureCount; $i++) {
                $minValues[$i] = min($minValues[$i], $features[$i]);
                $maxValues[$i] = max($maxValues[$i], $features[$i]);
            }
        }
        
        $this->normalizationParams = [
            'minValues' => $minValues,
            'maxValues' => $maxValues
        ];
    }

    /**
     * Calculate category progression score based on purchase history
     * 
     * @param int $userId User ID
     * @param int $categoryId Category ID
     * @return float Progression score (0-1)
     */
    private function calculateCategoryProgressionScore($userId, $categoryId)
    {
        $analysis = $this->purchaseAnalyzer->analyzePurchaseHistory($userId);
        
        if (empty($analysis['recommendation_patterns']['category_progression'])) {
            return 0.5; // Neutral score
        }
        
        $maxScore = 0;
        foreach ($analysis['recommendation_patterns']['category_progression'] as $purchasedCat => $pattern) {
            if (in_array($categoryId, $pattern['suggested_categories'])) {
                $maxScore = max($maxScore, $pattern['confidence']);
            }
        }
        
        return $maxScore;
    }

    /**
     * Calculate occasion-based recommendation score
     * 
     * @param int $userId User ID
     * @param array $artworkData Artwork data
     * @return float Occasion score (0-1)
     */
    private function calculateOccasionRecommendationScore($userId, $artworkData)
    {
        $analysis = $this->purchaseAnalyzer->analyzePurchaseHistory($userId);
        
        if (empty($analysis['recommendation_patterns']['occasion_patterns'])) {
            return 0.5; // Neutral score
        }
        
        $categoryId = (int)$artworkData['category_id'];
        $maxScore = 0;
        
        foreach ($analysis['recommendation_patterns']['occasion_patterns'] as $occasion => $pattern) {
            if (in_array($categoryId, $pattern['primary']) || in_array($categoryId, $pattern['secondary'])) {
                $weight = in_array($categoryId, $pattern['primary']) ? 1.0 : 0.7;
                $maxScore = max($maxScore, $pattern['confidence'] * $weight);
            }
        }
        
        return $maxScore;
    }

    /**
     * Calculate purchase pattern consistency score
     * 
     * @param int $userId User ID
     * @param array $artworkData Artwork data
     * @return float Consistency score (0-1)
     */
    private function calculatePurchasePatternConsistency($userId, $artworkData)
    {
        $analysis = $this->purchaseAnalyzer->analyzePurchaseHistory($userId);
        
        if (empty($analysis['categories_purchased'])) {
            return 0.5; // Neutral score for new users
        }
        
        $categoryId = (int)$artworkData['category_id'];
        $price = (float)$artworkData['price'];
        
        $consistencyScore = 0;
        $factors = 0;
        
        // Category consistency
        if (isset($analysis['categories_purchased'][$categoryId])) {
            $consistencyScore += $analysis['categories_purchased'][$categoryId]['frequency_score'];
            $factors++;
        }
        
        // Price consistency
        $pricePrefs = $analysis['price_preferences'];
        if ($price >= $pricePrefs['preferred_range'][0] && $price <= $pricePrefs['preferred_range'][1]) {
            $consistencyScore += $pricePrefs['price_consistency'];
            $factors++;
        }
        
        // Seasonal consistency
        $currentMonth = (int)date('n');
        $seasonal = $analysis['seasonal_patterns'];
        if (isset($seasonal['monthly_distribution'][$currentMonth]) && 
            $seasonal['monthly_distribution'][$currentMonth] > 0) {
            $consistencyScore += 0.8; // High score for seasonal consistency
            $factors++;
        }
        
        return $factors > 0 ? $consistencyScore / $factors : 0.5;
    }
}
