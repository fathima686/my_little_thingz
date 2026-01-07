<?php

class FeatureAccessControl {
    private $db;
    
    // Define exact features for each plan - NO INHERITANCE
    private const PLAN_FEATURES = [
        'basic' => [
            'basic_tutorials',
            'standard_video',
            'community_support',
            'mobile_access',
            'individual_purchases'  // Basic users can buy individual tutorials
        ],
        'premium' => [
            'unlimited_tutorials',  // All tutorials included
            'hd_video',
            'weekly_content',
            'priority_support',
            'download_videos',
            'mobile_access'
        ],
        'pro' => [
            'unlimited_tutorials',  // All tutorials included
            'hd_video', 
            'weekly_content',
            'priority_support',
            'download_videos',
            'live_workshops',
            'mentorship',
            'certificates',
            'early_access',
            'practice_uploads',
            'mobile_access'
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
     * Get user ID from email
     * @param string $email
     * @return int|null
     */
    public function getUserIdFromEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['id'] : null;
        } catch (Exception $e) {
            error_log("Error getting user ID from email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user's current active plan by email
     * @param string $email
     * @return string|null
     */
    public function getUserPlanByEmail($email) {
        try {
            // First try to get from subscriptions table directly by email
            $stmt = $this->db->prepare("
                SELECT s.plan_code 
                FROM subscriptions s 
                WHERE s.email = ? 
                AND s.is_active = 1
                ORDER BY s.created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return $result['plan_code'];
            }
            
            // Fallback to user ID lookup
            $userId = $this->getUserIdFromEmail($email);
            if ($userId) {
                return $this->getUserPlan($userId);
            }
            
            return 'basic'; // Default to basic if no subscription found
        } catch (Exception $e) {
            error_log("Error getting user plan by email: " . $e->getMessage());
            return 'basic'; // Fail safe to basic
        }
    }
    
    /**
     * Check if user has access to a specific feature by email
     * @param string $email
     * @param string $featureKey
     * @return bool
     */
    public function hasAccessByEmail($email, $featureKey) {
        $userPlan = $this->getUserPlanByEmail($email);
        
        if (!$userPlan) {
            return false; // No plan = no access
        }
        
        $allowedFeatures = self::PLAN_FEATURES[$userPlan] ?? [];
        return in_array($featureKey, $allowedFeatures);
    }
    
    /**
     * Get user's current active plan
     * @param int $userId
     * @return string|null
     */
    public function getUserPlan($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT sp.plan_code 
                FROM subscriptions s 
                JOIN subscription_plans sp ON s.plan_id = sp.id 
                WHERE s.user_id = ? 
                AND s.status IN ('active', 'authenticated', 'pending')
                ORDER BY s.created_at DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['plan_code'] : 'basic'; // Default to basic if no subscription
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
     * Calculate user's overall learning progress
     * @param int $userId
     * @return array
     */
    public function calculateLearningProgress($userId) {
        try {
            // Get total tutorials in the main course
            $totalStmt = $this->db->prepare("
                SELECT COUNT(*) as total_tutorials 
                FROM course_tutorials ct 
                JOIN courses c ON ct.course_id = c.id 
                WHERE c.is_active = 1 AND ct.is_required = 1
            ");
            $totalStmt->execute();
            $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
            $totalTutorials = $totalResult['total_tutorials'] ?? 0;
            
            if ($totalTutorials == 0) {
                return [
                    'total_tutorials' => 0,
                    'completed_tutorials' => 0,
                    'completion_percentage' => 0,
                    'certificate_eligible' => false
                ];
            }
            
            // Get user's completed tutorials (watched + approved practice)
            $completedStmt = $this->db->prepare("
                SELECT COUNT(*) as completed_tutorials
                FROM learning_progress lp
                JOIN course_tutorials ct ON lp.tutorial_id = ct.tutorial_id
                JOIN courses c ON ct.course_id = c.id
                WHERE lp.user_id = ? 
                AND c.is_active = 1 
                AND ct.is_required = 1
                AND lp.completion_percentage >= 80
                AND (lp.practice_approved = 1 OR lp.has_practice_upload = 0)
            ");
            $completedStmt->execute([$userId]);
            $completedResult = $completedStmt->fetch(PDO::FETCH_ASSOC);
            $completedTutorials = $completedResult['completed_tutorials'] ?? 0;
            
            $completionPercentage = ($completedTutorials / $totalTutorials) * 100;
            $certificateEligible = $completionPercentage >= 80;
            
            return [
                'total_tutorials' => $totalTutorials,
                'completed_tutorials' => $completedTutorials,
                'completion_percentage' => round($completionPercentage, 2),
                'certificate_eligible' => $certificateEligible
            ];
            
        } catch (Exception $e) {
            error_log("Error calculating learning progress: " . $e->getMessage());
            return [
                'total_tutorials' => 0,
                'completed_tutorials' => 0,
                'completion_percentage' => 0,
                'certificate_eligible' => false
            ];
        }
    }
    
    /**
     * Check if user is eligible for certificate (80% completion + Pro plan)
     * @param int $userId
     * @return bool
     */
    public function isCertificateEligible($userId) {
        // Must be Pro user
        if (!$this->canAccessCertificates($userId)) {
            return false;
        }
        
        $progress = $this->calculateLearningProgress($userId);
        return $progress['certificate_eligible'];
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
}