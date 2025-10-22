<?php

/**
 * K-Nearest Neighbors (KNN) Recommendation Engine
 * 
 * This class implements KNN algorithm for finding similar gifts and products
 * based on user preferences, product features, and collaborative filtering.
 */
class KNNRecommendationEngine
{
    private $db;
    private $k = 5; // Number of nearest neighbors
    private $similarityThreshold = 0.3;
    
    public function __construct($database, $k = 5)
    {
        $this->db = $database;
        $this->k = $k;
    }
    
    /**
     * Find similar products based on product features
     * 
     * @param int $productId Target product ID
     * @param int $limit Number of recommendations
     * @return array Similar products with similarity scores
     */
    public function findSimilarProducts($productId, $limit = 8)
    {
        // Get target product features
        $targetProduct = $this->getProductFeatures($productId);
        if (!$targetProduct) {
            return [];
        }
        
        // Get all other products
        $allProducts = $this->getAllProducts($productId);
        
        // Calculate similarities
        $similarities = [];
        foreach ($allProducts as $product) {
            $similarity = $this->calculateProductSimilarity($targetProduct, $product);
            if ($similarity >= $this->similarityThreshold) {
                $similarities[] = [
                    'product' => $product,
                    'similarity' => $similarity
                ];
            }
        }
        
        // Sort by similarity and return top K
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similarities, 0, $limit);
    }
    
    /**
     * Find products similar to user's preferences
     * 
     * @param int $userId User ID
     * @param int $limit Number of recommendations
     * @return array Recommended products
     */
    public function findUserBasedRecommendations($userId, $limit = 8)
    {
        // Get user's purchase/view history
        $userHistory = $this->getUserHistory($userId);
        if (empty($userHistory)) {
            return $this->getPopularProducts($limit);
        }
        
        // Find similar users
        $similarUsers = $this->findSimilarUsers($userId);
        
        // Get products liked by similar users
        $recommendations = $this->getCollaborativeRecommendations($similarUsers, $userHistory, $limit);
        
        return $recommendations;
    }
    
    /**
     * Get product features for similarity calculation
     */
    private function getProductFeatures($productId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.image_url, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id = :id AND a.status = 'active'
        ");
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            return null;
        }
        
        // Extract features
        return [
            'id' => (int)$product['id'],
            'title' => $product['title'],
            'description' => $product['description'],
            'price' => (float)$product['price'],
            'effective_price' => (float)$product['effective_price'],
            'category_id' => (int)$product['category_id'],
            'category_name' => $product['category_name'],
            'image_url' => $product['image_url'],
            'availability' => $product['availability'],
            'created_at' => $product['created_at'],
            'price_tier' => $this->getPriceTier($product['effective_price']),
            'title_tokens' => $this->tokenizeText($product['title']),
            'description_tokens' => $this->tokenizeText($product['description'])
        ];
    }
    
    /**
     * Get all products except the target
     */
    private function getAllProducts($excludeId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.image_url, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id != :exclude_id AND a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT 200
        ");
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'effective_price' => (float)$row['effective_price'],
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'],
                'image_url' => $row['image_url'],
                'availability' => $row['availability'],
                'created_at' => $row['created_at'],
                'price_tier' => $this->getPriceTier($row['effective_price']),
                'title_tokens' => $this->tokenizeText($row['title']),
                'description_tokens' => $this->tokenizeText($row['description'])
            ];
        }
        
        return $products;
    }
    
    /**
     * Calculate similarity between two products
     */
    private function calculateProductSimilarity($product1, $product2)
    {
        $similarity = 0.0;
        $weights = [
            'category' => 0.4,
            'price' => 0.3,
            'title' => 0.2,
            'description' => 0.1
        ];
        
        // Category similarity
        if ($product1['category_id'] === $product2['category_id']) {
            $similarity += $weights['category'];
        }
        
        // Price similarity (inverse distance)
        $priceDiff = abs($product1['effective_price'] - $product2['effective_price']);
        $maxPrice = max($product1['effective_price'], $product2['effective_price']);
        if ($maxPrice > 0) {
            $priceSimilarity = 1 - ($priceDiff / $maxPrice);
            $similarity += $weights['price'] * $priceSimilarity;
        }
        
        // Title similarity (Jaccard similarity)
        $titleSimilarity = $this->calculateJaccardSimilarity(
            $product1['title_tokens'], 
            $product2['title_tokens']
        );
        $similarity += $weights['title'] * $titleSimilarity;
        
        // Description similarity
        $descSimilarity = $this->calculateJaccardSimilarity(
            $product1['description_tokens'], 
            $product2['description_tokens']
        );
        $similarity += $weights['description'] * $descSimilarity;
        
        return min(1.0, $similarity);
    }
    
    /**
     * Calculate Jaccard similarity between two sets of tokens
     */
    private function calculateJaccardSimilarity($tokens1, $tokens2)
    {
        if (empty($tokens1) && empty($tokens2)) {
            return 1.0;
        }
        
        $intersection = count(array_intersect($tokens1, $tokens2));
        $union = count(array_unique(array_merge($tokens1, $tokens2)));
        
        return $union > 0 ? $intersection / $union : 0.0;
    }
    
    /**
     * Get user's purchase/view history
     */
    private function getUserHistory($userId)
    {
        $history = [];
        
        // Get wishlist items
        try {
            $stmt = $this->db->prepare("
                SELECT w.artwork_id, 'wishlist' as type, w.created_at
                FROM wishlist w
                WHERE w.user_id = :user_id
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $history = array_merge($history, $wishlist);
        } catch (Exception $e) {
            // Wishlist table might not exist
        }
        
        // Get order history
        try {
            $stmt = $this->db->prepare("
                SELECT oi.artwork_id, 'purchase' as type, o.created_at
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE o.user_id = :user_id AND o.status != 'cancelled'
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $history = array_merge($history, $orders);
        } catch (Exception $e) {
            // Orders table might not exist
        }
        
        return $history;
    }
    
    /**
     * Find similar users based on purchase patterns
     */
    private function findSimilarUsers($userId)
    {
        $userHistory = $this->getUserHistory($userId);
        if (empty($userHistory)) {
            return [];
        }
        
        $userProducts = array_column($userHistory, 'artwork_id');
        
        // Get all other users with similar product preferences
        $stmt = $this->db->prepare("
            SELECT DISTINCT u.id, u.name, u.email
            FROM users u
            WHERE u.id != :user_id
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $similarUsers = [];
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $otherUserHistory = $this->getUserHistory($user['id']);
            $otherUserProducts = array_column($otherUserHistory, 'artwork_id');
            
            if (!empty($otherUserProducts)) {
                $similarity = $this->calculateJaccardSimilarity($userProducts, $otherUserProducts);
                if ($similarity > 0.1) { // Minimum similarity threshold
                    $similarUsers[] = [
                        'user' => $user,
                        'similarity' => $similarity,
                        'products' => $otherUserProducts
                    ];
                }
            }
        }
        
        // Sort by similarity
        usort($similarUsers, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        return array_slice($similarUsers, 0, $this->k);
    }
    
    /**
     * Get collaborative recommendations from similar users
     */
    private function getCollaborativeRecommendations($similarUsers, $userHistory, $limit)
    {
        $userProductIds = array_column($userHistory, 'artwork_id');
        $recommendations = [];
        
        foreach ($similarUsers as $similarUser) {
            foreach ($similarUser['products'] as $productId) {
                if (!in_array($productId, $userProductIds)) {
                    if (!isset($recommendations[$productId])) {
                        $recommendations[$productId] = [
                            'product_id' => $productId,
                            'score' => 0,
                            'similarity' => $similarUser['similarity']
                        ];
                    }
                    $recommendations[$productId]['score'] += $similarUser['similarity'];
                }
            }
        }
        
        // Sort by score
        usort($recommendations, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Get product details
        $productIds = array_slice(array_column($recommendations, 'product_id'), 0, $limit);
        if (empty($productIds)) {
            return $this->getPopularProducts($limit);
        }
        
        $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.image_url, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.id IN ($placeholders) AND a.status = 'active'
        ");
        $stmt->execute($productIds);
        
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'effective_price' => (float)$row['effective_price'],
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'],
                'image_url' => $row['image_url'],
                'availability' => $row['availability'],
                'created_at' => $row['created_at'],
                'recommendation_type' => 'collaborative',
                'similarity_score' => $recommendations[$row['id']]['score'] ?? 0
            ];
        }
        
        return $products;
    }
    
    /**
     * Get popular products as fallback
     */
    private function getPopularProducts($limit)
    {
        $stmt = $this->db->prepare("
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.image_url, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'price' => (float)$row['price'],
                'effective_price' => (float)$row['effective_price'],
                'category_id' => (int)$row['category_id'],
                'category_name' => $row['category_name'],
                'image_url' => $row['image_url'],
                'availability' => $row['availability'],
                'created_at' => $row['created_at'],
                'recommendation_type' => 'popular'
            ];
        }
        
        return $products;
    }
    
    /**
     * Get price tier for a product
     */
    private function getPriceTier($price)
    {
        if ($price < 500) return 'budget';
        if ($price < 1000) return 'mid';
        if ($price < 2000) return 'premium';
        return 'luxury';
    }
    
    /**
     * Tokenize text for similarity calculation
     */
    private function tokenizeText($text)
    {
        $text = strtolower($text);
        $tokens = preg_split('/\W+/', $text);
        return array_filter($tokens, function($token) {
            return strlen($token) > 2; // Filter out short words
        });
    }
    
    /**
     * Set K value for KNN
     */
    public function setK($k)
    {
        $this->k = max(1, $k);
    }
    
    /**
     * Set similarity threshold
     */
    public function setSimilarityThreshold($threshold)
    {
        $this->similarityThreshold = max(0.0, min(1.0, $threshold));
    }
}



