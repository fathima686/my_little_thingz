<?php

class FeatureAccessControl {
    private $db;
    
    // Define exact features for each plan - NO INHERITANCE
    private const PLAN_FEATURES = [
        'basic' => [
            'basic_tutorials',
            'standard_video',
            'community_support',
            'mobile_access'
        ],
        'premium' => [
            'unlimited_tutorials',
            'hd_video',
            'weekly_content',
            'priority_support',
            'download_videos'
        ],
        'pro' => [
            'unlimited_tutorials',
            'hd_video', 
            'weekly_content',
            'priority_support',
            'download_videos',
            'live_workshops',
            'mentorship',
            'certificates',
            'early_access',
            'practice_uploads'
        ]
    ];
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if user has access to a specific feature
     * @param int $userId
     * @param string $featureKey
     * @return bool
     */
    public function hasAccess($userId, $featureKey) {
        $userPlan = $this->getUserPlan($userId);
        
        if (!$userPlan) {
            return false; // No plan = no access
        }
        
        $allowedFeatures = self::PLAN_FEATURES[$userPlan] ?? [];
        return in_array($featureKey, $allowedFeatures);
    }
    
    /**
     * Get user's current active plan - FIXED VERSION
     * @param int $userId
     * @return string|null
     */
    public function getUserPlan($userId) {
        try {
            // First, get user email from user ID
            $emailStmt = $this->db->prepare("SELECT email FROM users WHERE id = ?");
            $emailStmt->execute([$userId]);
            $userResult = $emailStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$userResult) {
                error_log("User not found for ID: $userId");
                return 'basic';
            }
            
            $userEmail = $userResult['email'];
            
            // Get active subscription using email (matching our current structure)
            $stmt = $this->db->prepare("
                SELECT s.plan_code 
                FROM subscriptions s 
                WHERE s.email = ? 
                AND s.is_active = 1
                AND s.subscription_status = 'active'
                ORDER BY s.created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$userEmail]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $planCode = $result ? $result['plan_code'] : 'basic';
            
            // Log for debugging
            error_log("User ID: $userId, Email: $userEmail, Plan: $planCode");
            
            return $planCode;
            
        } catch (Exception $e) {
            error_log("Error getting user plan: " . $e->getMessage());
            return 'basic'; // Fail safe to basic
        }
    }
    
    /**
     * Get all features available to user's plan
     * @param int $userId
     * @return array
     */
    public function getUserFeatures($userId) {
        $userPlan = $this->getUserPlan($userId);
        return self::PLAN_FEATURES[$userPlan] ?? [];
    }
    
    /**
     * Check if user can access live workshops (Pro only)
     * @param int $userId
     * @return bool
     */
    public function canAccessLiveWorkshops($userId) {
        return $this->hasAccess($userId, 'live_workshops');
    }
    
    /**
     * Check if user can access HD video (Premium and Pro)
     * @param int $userId
     * @return bool
     */
    public function canAccessHDVideo($userId) {
        return $this->hasAccess($userId, 'hd_video');
    }
    
    /**
     * Check if user can download videos (Premium and Pro)
     * @param int $userId
     * @return bool
     */
    public function canDownloadVideos($userId) {
        return $this->hasAccess($userId, 'download_videos');
    }
    
    /**
     * Check if user can access unlimited tutorials (Premium and Pro)
     * @param int $userId
     * @return bool
     */
    public function canAccessUnlimitedTutorials($userId) {
        return $this->hasAccess($userId, 'unlimited_tutorials');
    }
    
    /**
     * Check if user can access mentorship (Pro only)
     * @param int $userId
     * @return bool
     */
    public function canAccessMentorship($userId) {
        return $this->hasAccess($userId, 'mentorship');
    }
    
    /**
     * Enforce feature access - throws exception if access denied
     * @param int $userId
     * @param string $featureKey
     * @throws Exception
     */
    public function enforceAccess($userId, $featureKey) {
        if (!$this->hasAccess($userId, $featureKey)) {
            $userPlan = $this->getUserPlan($userId);
            throw new Exception("Access denied. Feature '$featureKey' requires a higher subscription plan. Current plan: $userPlan");
        }
    }
    
    /**
     * Check if user can upload practice work (Pro only)
     * @param int $userId
     * @return bool
     */
    public function canUploadPracticeWork($userId) {
        return $this->hasAccess($userId, 'practice_uploads');
    }
    
    /**
     * Check if user can access certificates (Pro only)
     * @param int $userId
     * @return bool
     */
    public function canAccessCertificates($userId) {
        return $this->hasAccess($userId, 'certificates');
    }
    
    /**
     * Get plan upgrade suggestions for a feature
     * @param string $featureKey
     * @return array
     */
    public function getRequiredPlansForFeature($featureKey) {
        $requiredPlans = [];
        
        foreach (self::PLAN_FEATURES as $plan => $features) {
            if (in_array($featureKey, $features)) {
                $requiredPlans[] = $plan;
            }
        }
        
        return $requiredPlans;
    }
    
    /**
     * Debug method to check subscription status
     * @param int $userId
     * @return array
     */
    public function debugSubscriptionStatus($userId) {
        try {
            // Get user info
            $userStmt = $this->db->prepare("SELECT id, email, name FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['error' => 'User not found'];
            }
            
            // Get all subscriptions for this user
            $subStmt = $this->db->prepare("
                SELECT s.*, sp.plan_name 
                FROM subscriptions s 
                LEFT JOIN subscription_plans sp ON s.plan_code = sp.plan_code
                WHERE s.email = ? 
                ORDER BY s.created_at DESC
            ");
            $subStmt->execute([$user['email']]);
            $subscriptions = $subStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get current plan
            $currentPlan = $this->getUserPlan($userId);
            
            // Get available features
            $features = $this->getUserFeatures($userId);
            
            return [
                'user' => $user,
                'current_plan' => $currentPlan,
                'available_features' => $features,
                'all_subscriptions' => $subscriptions,
                'can_access_live_workshops' => $this->canAccessLiveWorkshops($userId),
                'debug_timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
?>