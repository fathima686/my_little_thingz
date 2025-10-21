<?php

/**
 * User Behavior Tracker for BPNN Recommendation System
 * 
 * This class handles tracking user interactions with artworks
 * to build training data for the neural network.
 */
class UserBehaviorTracker
{
    private $db;

    public function __construct($database)
    {
        $this->db = $database;
    }

    /**
     * Track user behavior
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param string $behaviorType Type of behavior
     * @param array $additionalData Additional data (rating, session_id, etc.)
     * @return bool Success status
     */
    public function trackBehavior($userId, $artworkId, $behaviorType, $additionalData = [])
    {
        try {
            // Validate behavior type
            $validTypes = ['view', 'add_to_cart', 'add_to_wishlist', 'purchase', 'rating', 'remove_from_wishlist'];
            if (!in_array($behaviorType, $validTypes)) {
                throw new Exception('Invalid behavior type');
            }

            // Validate user and artwork exist
            if (!$this->validateUserAndArtwork($userId, $artworkId)) {
                throw new Exception('Invalid user or artwork');
            }

            // Prepare data
            $ratingValue = isset($additionalData['rating']) ? (float)$additionalData['rating'] : null;
            $sessionId = $additionalData['session_id'] ?? $this->generateSessionId();
            $ipAddress = $additionalData['ip_address'] ?? $this->getClientIP();
            $userAgent = $additionalData['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? null;

            // Validate rating if provided
            if ($ratingValue !== null && ($ratingValue < 1.0 || $ratingValue > 5.0)) {
                throw new Exception('Rating must be between 1.0 and 5.0');
            }

            // Insert behavior record
            $stmt = $this->db->prepare("
                INSERT INTO user_behavior 
                (user_id, artwork_id, behavior_type, rating_value, session_id, ip_address, user_agent)
                VALUES (:user_id, :artwork_id, :behavior_type, :rating_value, :session_id, :ip_address, :user_agent)
            ");

            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
            $stmt->bindValue(':behavior_type', $behaviorType, PDO::PARAM_STR);
            $stmt->bindValue(':rating_value', $ratingValue, PDO::PARAM_STR);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_STR);
            $stmt->bindValue(':ip_address', $ipAddress, PDO::PARAM_STR);
            $stmt->bindValue(':user_agent', $userAgent, PDO::PARAM_STR);

            $result = $stmt->execute();

            if ($result) {
                // Update user preference profile
                $this->updateUserProfile($userId);
                
                // Clear prediction cache for this user
                $this->clearUserPredictions($userId);
                
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("UserBehaviorTracker Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Track multiple behaviors in batch
     * 
     * @param array $behaviors Array of behavior data
     * @return array Results for each behavior
     */
    public function trackBatchBehaviors($behaviors)
    {
        $results = [];
        
        foreach ($behaviors as $behavior) {
            $results[] = $this->trackBehavior(
                $behavior['user_id'],
                $behavior['artwork_id'],
                $behavior['behavior_type'],
                $behavior['additional_data'] ?? []
            );
        }
        
        return $results;
    }

    /**
     * Track page view
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackView($userId, $artworkId, $context = [])
    {
        return $this->trackBehavior($userId, $artworkId, 'view', $context);
    }

    /**
     * Track add to cart
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackAddToCart($userId, $artworkId, $context = [])
    {
        return $this->trackBehavior($userId, $artworkId, 'add_to_cart', $context);
    }

    /**
     * Track add to wishlist
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackAddToWishlist($userId, $artworkId, $context = [])
    {
        return $this->trackBehavior($userId, $artworkId, 'add_to_wishlist', $context);
    }

    /**
     * Track purchase
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackPurchase($userId, $artworkId, $context = [])
    {
        return $this->trackBehavior($userId, $artworkId, 'purchase', $context);
    }

    /**
     * Track rating
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param float $rating Rating value (1.0-5.0)
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackRating($userId, $artworkId, $rating, $context = [])
    {
        $context['rating'] = $rating;
        return $this->trackBehavior($userId, $artworkId, 'rating', $context);
    }

    /**
     * Track remove from wishlist
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @param array $context Additional context
     * @return bool Success status
     */
    public function trackRemoveFromWishlist($userId, $artworkId, $context = [])
    {
        return $this->trackBehavior($userId, $artworkId, 'remove_from_wishlist', $context);
    }

    /**
     * Get user behavior summary
     * 
     * @param int $userId User ID
     * @param int $days Number of days to look back
     * @return array Behavior summary
     */
    public function getUserBehaviorSummary($userId, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                behavior_type,
                COUNT(*) as count,
                AVG(rating_value) as avg_rating,
                COUNT(DISTINCT artwork_id) as unique_artworks
            FROM user_behavior 
            WHERE user_id = :user_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY behavior_type
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $behaviors = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $behaviors[$row['behavior_type']] = [
                'count' => (int)$row['count'],
                'avg_rating' => $row['avg_rating'] ? (float)$row['avg_rating'] : null,
                'unique_artworks' => (int)$row['unique_artworks']
            ];
        }

        return $behaviors;
    }

    /**
     * Get artwork behavior summary
     * 
     * @param int $artworkId Artwork ID
     * @param int $days Number of days to look back
     * @return array Behavior summary
     */
    public function getArtworkBehaviorSummary($artworkId, $days = 30)
    {
        $stmt = $this->db->prepare("
            SELECT 
                behavior_type,
                COUNT(*) as count,
                AVG(rating_value) as avg_rating,
                COUNT(DISTINCT user_id) as unique_users
            FROM user_behavior 
            WHERE artwork_id = :artwork_id 
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY behavior_type
        ");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->bindValue(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $behaviors = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $behaviors[$row['behavior_type']] = [
                'count' => (int)$row['count'],
                'avg_rating' => $row['avg_rating'] ? (float)$row['avg_rating'] : null,
                'unique_users' => (int)$row['unique_users']
            ];
        }

        return $behaviors;
    }

    /**
     * Get recent user behaviors
     * 
     * @param int $userId User ID
     * @param int $limit Number of behaviors to retrieve
     * @return array Recent behaviors
     */
    public function getRecentBehaviors($userId, $limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT 
                ub.*,
                a.title as artwork_title,
                a.price as artwork_price,
                c.name as category_name
            FROM user_behavior ub
            JOIN artworks a ON ub.artwork_id = a.id
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE ub.user_id = :user_id
            ORDER BY ub.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate user and artwork exist
     * 
     * @param int $userId User ID
     * @param int $artworkId Artwork ID
     * @return bool Validation result
     */
    private function validateUserAndArtwork($userId, $artworkId)
    {
        // Check user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE id = :user_id");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $userExists = $stmt->fetch() !== false;

        // Check artwork exists and is active
        $stmt = $this->db->prepare("SELECT id FROM artworks WHERE id = :artwork_id AND status = 'active'");
        $stmt->bindValue(':artwork_id', $artworkId, PDO::PARAM_INT);
        $stmt->execute();
        $artworkExists = $stmt->fetch() !== false;

        return $userExists && $artworkExists;
    }

    /**
     * Generate session ID
     * 
     * @return string Session ID
     */
    private function generateSessionId()
    {
        return session_id() ?: uniqid('sess_', true);
    }

    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private function getClientIP()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Update user preference profile
     * 
     * @param int $userId User ID
     */
    private function updateUserProfile($userId)
    {
        try {
            // Get user's recent behavior data
            $stmt = $this->db->prepare("
                SELECT 
                    a.category_id,
                    a.price,
                    ub.rating_value,
                    ub.behavior_type,
                    COUNT(*) as interaction_count
                FROM user_behavior ub
                JOIN artworks a ON ub.artwork_id = a.id
                WHERE ub.user_id = :user_id
                AND ub.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                GROUP BY a.category_id, a.price, ub.rating_value, ub.behavior_type
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $behaviors = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($behaviors)) {
                return;
            }

            // Calculate preferences
            $categoryCounts = [];
            $prices = [];
            $ratings = [];
            $totalInteractions = 0;

            foreach ($behaviors as $behavior) {
                if ($behavior['category_id']) {
                    $categoryCounts[(int)$behavior['category_id']] = 
                        ($categoryCounts[(int)$behavior['category_id']] ?? 0) + (int)$behavior['interaction_count'];
                }
                $prices[] = (float)$behavior['price'];
                if ($behavior['rating_value']) {
                    $ratings[] = (float)$behavior['rating_value'];
                }
                $totalInteractions += (int)$behavior['interaction_count'];
            }

            // Get top categories
            arsort($categoryCounts);
            $preferredCategories = array_slice(array_keys($categoryCounts), 0, 5);

            // Calculate price range
            $minPrice = $prices ? min($prices) : 0;
            $maxPrice = $prices ? max($prices) : 1000;

            // Calculate average rating
            $avgRating = $ratings ? array_sum($ratings) / count($ratings) : 3.0;

            // Calculate activity score
            $activityScore = min(1.0, $totalInteractions / 100.0);

            // Update or insert profile
            $stmt = $this->db->prepare("
                INSERT INTO user_preference_profiles 
                (user_id, preferred_categories, price_range_min, price_range_max, avg_rating_preference, activity_score)
                VALUES (:user_id, :categories, :min_price, :max_price, :avg_rating, :activity_score)
                ON DUPLICATE KEY UPDATE
                preferred_categories = VALUES(preferred_categories),
                price_range_min = VALUES(price_range_min),
                price_range_max = VALUES(price_range_max),
                avg_rating_preference = VALUES(avg_rating_preference),
                activity_score = VALUES(activity_score),
                last_updated = CURRENT_TIMESTAMP
            ");

            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':categories', json_encode($preferredCategories), PDO::PARAM_STR);
            $stmt->bindValue(':min_price', $minPrice, PDO::PARAM_STR);
            $stmt->bindValue(':max_price', $maxPrice, PDO::PARAM_STR);
            $stmt->bindValue(':avg_rating', $avgRating, PDO::PARAM_STR);
            $stmt->bindValue(':activity_score', $activityScore, PDO::PARAM_STR);
            $stmt->execute();

        } catch (Exception $e) {
            error_log("UserBehaviorTracker Profile Update Error: " . $e->getMessage());
        }
    }

    /**
     * Clear user prediction cache
     * 
     * @param int $userId User ID
     */
    private function clearUserPredictions($userId)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM bpnn_predictions WHERE user_id = :user_id");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            error_log("UserBehaviorTracker Cache Clear Error: " . $e->getMessage());
        }
    }

    /**
     * Clean up old behavior data
     * 
     * @param int $days Number of days to keep
     * @return int Number of records deleted
     */
    public function cleanupOldData($days = 365)
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM user_behavior 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("UserBehaviorTracker Cleanup Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get behavior statistics
     * 
     * @return array Statistics
     */
    public function getBehaviorStatistics()
    {
        $stats = [];

        // Total behaviors
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM user_behavior");
        $stmt->execute();
        $stats['total_behaviors'] = (int)$stmt->fetchColumn();

        // Behaviors by type
        $stmt = $this->db->prepare("
            SELECT behavior_type, COUNT(*) as count 
            FROM user_behavior 
            GROUP BY behavior_type
        ");
        $stmt->execute();
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        // Recent activity (last 24 hours)
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM user_behavior 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $stats['recent_24h'] = (int)$stmt->fetchColumn();

        // Unique users
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT user_id) as count FROM user_behavior");
        $stmt->execute();
        $stats['unique_users'] = (int)$stmt->fetchColumn();

        // Unique artworks
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT artwork_id) as count FROM user_behavior");
        $stmt->execute();
        $stats['unique_artworks'] = (int)$stmt->fetchColumn();

        return $stats;
    }
}

