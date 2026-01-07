<?php

require_once __DIR__ . '/../models/FeatureAccessControl.php';
require_once __DIR__ . '/../config/database.php';

class FeatureGuard {
    private $featureControl;
    
    public function __construct() {
        $database = new Database();
        $db = $database->getConnection();
        $this->featureControl = new FeatureAccessControl($db);
    }
    
    /**
     * Guard API endpoint with feature requirement
     * @param int $userId
     * @param string $requiredFeature
     * @return array Success response or error
     */
    public function guardFeature($userId, $requiredFeature) {
        try {
            $this->featureControl->enforceAccess($userId, $requiredFeature);
            return ['allowed' => true];
        } catch (Exception $e) {
            return [
                'allowed' => false,
                'error' => $e->getMessage(),
                'required_plans' => $this->featureControl->getRequiredPlansForFeature($requiredFeature),
                'current_plan' => $this->featureControl->getUserPlan($userId)
            ];
        }
    }
    
    /**
     * Send JSON error response for feature access denial
     * @param array $guardResult
     */
    public function sendAccessDeniedResponse($guardResult) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => $guardResult['error'],
            'error_code' => 'FEATURE_ACCESS_DENIED',
            'current_plan' => $guardResult['current_plan'],
            'required_plans' => $guardResult['required_plans'],
            'upgrade_required' => true
        ]);
        exit;
    }
    
    /**
     * Get user's feature access summary
     * @param int $userId
     * @return array
     */
    public function getUserAccessSummary($userId) {
        $currentPlan = $this->featureControl->getUserPlan($userId);
        $features = $this->featureControl->getUserFeatures($userId);
        
        return [
            'current_plan' => $currentPlan,
            'features' => $features,
            'access_levels' => [
                'can_access_hd_video' => $this->featureControl->canAccessHDVideo($userId),
                'can_download_videos' => $this->featureControl->canDownloadVideos($userId),
                'can_access_live_workshops' => $this->featureControl->canAccessLiveWorkshops($userId),
                'can_access_mentorship' => $this->featureControl->canAccessMentorship($userId),
                'can_access_unlimited_tutorials' => $this->featureControl->canAccessUnlimitedTutorials($userId)
            ]
        ];
    }
}