<?php
/**
 * Craft Image Validation Service V2 - Production Version
 * 
 * Enforces exclusive use of trained craft_image_classifier.keras model
 * No fallback logic - deterministic auto-approve/auto-reject/flag decisions
 * 
 * Features:
 * - Trained model ONLY - no MobileNet fallbacks
 * - Synchronous AI validation before database writes
 * - Strict decision rules: auto-approve, auto-reject, or flag for review
 * - Explainable confidence scores and reasoning
 * - Academic demonstration ready
 */

class CraftImageValidationServiceV2 {
    private $pdo;
    private $craftClassifierUrl;
    
    // Supported craft categories (must match training data)
    private const CRAFT_CATEGORIES = [
        'candle_making' => 'Candle Making',
        'clay_modeling' => 'Clay Modeling', 
        'gift_making' => 'Gift Making',
        'hand_embroidery' => 'Hand Embroidery',
        'jewelry_making' => 'Jewelry Making',
        'mehandi_art' => 'Mylanchi / Mehandi Art',
        'resin_art' => 'Resin Art'
    ];
    
    // FINAL: Very permissive thresholds to accept craft products
    private const HIGH_CONFIDENCE_THRESHOLD = 0.4;      // Auto-approve if category matches
    private const MEDIUM_CONFIDENCE_THRESHOLD = 0.3;    // Auto-approve if category matches  
    private const LOW_CONFIDENCE_THRESHOLD = 0.2;       // Flag for review if category matches
    private const REJECT_THRESHOLD = 0.2;               // Auto-reject if below this
    private const MISMATCH_REJECT_THRESHOLD = 0.6;      // Auto-reject if category mismatch with this confidence
    
    public function __construct($pdo, $craftClassifierUrl = null) {
        $this->pdo = $pdo;
        $this->craftClassifierUrl = $craftClassifierUrl ?? getenv('CRAFT_CLASSIFIER_URL') ?? 'http://localhost:5001';
        
        // Verify AI service is available at startup
        $this->verifyAIServiceAvailable();
    }
    
    /**
     * Verify AI service is available and using trained model
     */
    private function verifyAIServiceAvailable() {
        try {
            $url = $this->craftClassifierUrl . '/health';
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError || $httpCode !== 200) {
                throw new Exception("AI service not available: HTTP $httpCode, $curlError");
            }
            
            $result = json_decode($response, true);
            
            if (!$result || $result['status'] !== 'healthy') {
                throw new Exception("AI service unhealthy");
            }
            
            // Verify it's using the trained model
            if (!isset($result['model_type']) || $result['model_type'] !== 'trained_keras_model') {
                throw new Exception("AI service not using trained model");
            }
            
            if (!isset($result['fallback_disabled']) || !$result['fallback_disabled']) {
                throw new Exception("AI service fallback not disabled");
            }
            
            error_log("✓ Craft AI service verified: trained model active, fallbacks disabled");
            
        } catch (Exception $e) {
            error_log("✗ CRITICAL: Craft AI service verification failed: " . $e->getMessage());
            throw new Exception("Craft validation service unavailable - trained model required");
        }
    }
    
    /**
     * Main validation method - synchronous AI validation before database writes
     */
    public function validatePracticeImageSync($filePath, $userId, $tutorialId, $selectedCategory) {
        try {
            // Step 1: Classify image using trained model (synchronous)
            $classification = $this->classifyImageWithTrainedModel($filePath);
            
            if (!$classification['success']) {
                return $this->createErrorResult('CLASSIFICATION_FAILED', $classification['error_message']);
            }
            
            // Step 2: Make deterministic validation decision
            $decision = $this->makeStrictValidationDecision($classification, $selectedCategory);
            
            // Step 3: Create comprehensive result
            $result = [
                'success' => true,
                'ai_decision' => $decision['status'], // auto-approve, auto-reject, or flag-for-review
                'requires_admin_review' => $decision['requires_review'],
                'classification' => $classification,
                'validation_decision' => $decision,
                'selected_category' => $selectedCategory,
                'tutorial_id' => $tutorialId,
                'user_id' => $userId,
                'model_used' => 'trained_keras_model',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Craft validation error: " . $e->getMessage());
            return $this->createErrorResult('VALIDATION_EXCEPTION', $e->getMessage());
        }
    }
    
    /**
     * Classify image using trained model ONLY
     */
    private function classifyImageWithTrainedModel($filePath) {
        $classification = [
            'success' => false,
            'predicted_category' => null,
            'confidence' => 0.0,
            'all_predictions' => [],
            'is_craft_related' => false,
            'error_message' => null,
            'model_used' => 'trained_keras_model'
        ];
        
        try {
            // Verify file exists
            if (!file_exists($filePath)) {
                $classification['error_message'] = 'Image file not found';
                return $classification;
            }
            
            // Get absolute path for classifier
            $absolutePath = realpath($filePath);
            if (!$absolutePath) {
                $classification['error_message'] = 'Could not resolve file path';
                return $classification;
            }
            
            // Call trained model service
            $url = $this->craftClassifierUrl . '/classify-craft';
            
            $requestData = json_encode([
                'image_path' => $absolutePath
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);  // Longer timeout for model inference
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError || $httpCode !== 200) {
                $classification['error_message'] = "AI service error: HTTP $httpCode, $curlError";
                return $classification;
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['success'])) {
                $classification['error_message'] = 'Invalid response from AI service';
                return $classification;
            }
            
            if (!$result['success']) {
                $classification['error_message'] = $result['error_message'] ?? 'Classification failed';
                return $classification;
            }
            
            // Extract results from trained model
            $classification['success'] = true;
            $classification['predicted_category'] = $result['predicted_category'] ?? null;
            $classification['confidence'] = $result['confidence'] ?? 0.0;
            $classification['all_predictions'] = $result['all_predictions'] ?? [];
            $classification['is_craft_related'] = $result['is_craft_related'] ?? false;
            $classification['explanation'] = $result['explanation'] ?? '';
            
        } catch (Exception $e) {
            error_log("Trained model classification exception: " . $e->getMessage());
            $classification['error_message'] = "Classification exception: " . $e->getMessage();
        }
        
        return $classification;
    }
    
    /**
     * Make strict validation decision based on trained model results
     */
    private function makeStrictValidationDecision($classification, $selectedCategory) {
        $decision = [
            'status' => 'flag-for-review',  // auto-approve, auto-reject, or flag-for-review
            'requires_review' => true,
            'category_match' => false,
            'confidence_level' => 'unknown',
            'reasons' => [],
            'explanation' => '',
            'decision_logic' => 'strict_trained_model'
        ];
        
        try {
            $predictedCategory = $classification['predicted_category'] ?? '';
            $confidence = $classification['confidence'] ?? 0.0;
            $isCraftRelated = $classification['is_craft_related'] ?? false;
            
            // Normalize category names for comparison
            $selectedNormalized = $this->normalizeCategoryName($selectedCategory);
            $predictedNormalized = $this->normalizeCategoryName($predictedCategory);
            
            // Check category match
            $decision['category_match'] = ($selectedNormalized === $predictedNormalized);
            
            // Determine confidence level
            if ($confidence >= self::HIGH_CONFIDENCE_THRESHOLD) {
                $decision['confidence_level'] = 'high';
            } elseif ($confidence >= self::MEDIUM_CONFIDENCE_THRESHOLD) {
                $decision['confidence_level'] = 'medium';
            } elseif ($confidence >= self::LOW_CONFIDENCE_THRESHOLD) {
                $decision['confidence_level'] = 'low';
            } else {
                $decision['confidence_level'] = 'very_low';
            }
            
            // STRICT DECISION RULES - Deterministic outcomes
            
            // Rule 1: AUTO-REJECT - Very low confidence or not craft-related
            if ($confidence < self::REJECT_THRESHOLD || !$isCraftRelated) {
                $decision['status'] = 'auto-reject';
                $decision['requires_review'] = false;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = $isCraftRelated 
                    ? "Very low AI confidence: {$confidencePercent}%" 
                    : "Image appears unrelated to crafts";
                $decision['explanation'] = "Automatically rejected: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Rule 2: AUTO-REJECT - Medium confidence category mismatch (LOWERED threshold)
            if (!$decision['category_match'] && $confidence >= self::MISMATCH_REJECT_THRESHOLD) {
                $decision['status'] = 'auto-reject';
                $decision['requires_review'] = false;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = "Category mismatch with medium confidence: predicted {$predictedCategory} ({$confidencePercent}%), selected {$selectedCategory}";
                $decision['explanation'] = "Automatically rejected: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Rule 3: AUTO-APPROVE - Good confidence category match (LOWERED threshold)
            if ($decision['category_match'] && $confidence >= self::HIGH_CONFIDENCE_THRESHOLD) {
                $decision['status'] = 'auto-approve';
                $decision['requires_review'] = false;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = "Good confidence category match: {$predictedCategory} ({$confidencePercent}%)";
                $decision['explanation'] = "Automatically approved: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Rule 4: AUTO-APPROVE - Medium confidence category match (NEW RULE for craft products)
            if ($decision['category_match'] && $confidence >= self::MEDIUM_CONFIDENCE_THRESHOLD) {
                $decision['status'] = 'auto-approve';
                $decision['requires_review'] = false;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = "Medium confidence category match - accepting craft products: {$predictedCategory} ({$confidencePercent}%)";
                $decision['explanation'] = "Automatically approved: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Rule 5: FLAG FOR REVIEW - Low confidence category match
            if ($decision['category_match'] && $confidence >= self::LOW_CONFIDENCE_THRESHOLD) {
                $decision['status'] = 'flag-for-review';
                $decision['requires_review'] = true;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = "Low confidence category match needs review: {$predictedCategory} ({$confidencePercent}%)";
                $decision['explanation'] = "Flagged for review: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Rule 6: AUTO-REJECT - Very low confidence
            if ($confidence < self::REJECT_THRESHOLD) {
                $decision['status'] = 'auto-reject';
                $decision['requires_review'] = false;
                $confidencePercent = round($confidence * 100, 1);
                $decision['reasons'][] = "Very low confidence: {$confidencePercent}%";
                $decision['explanation'] = "Automatically rejected: " . implode('; ', $decision['reasons']);
                return $decision;
            }
            
            // Default: FLAG FOR REVIEW (should rarely reach here)
            $decision['status'] = 'flag-for-review';
            $decision['requires_review'] = true;
            $decision['reasons'][] = "Ambiguous classification result";
            $decision['explanation'] = "Flagged for review: " . implode('; ', $decision['reasons']);
            
        } catch (Exception $e) {
            error_log("Decision logic error: " . $e->getMessage());
            $decision['status'] = 'flag-for-review';
            $decision['requires_review'] = true;
            $decision['reasons'][] = "Decision logic error: " . $e->getMessage();
            $decision['explanation'] = "Flagged for review due to error";
        }
        
        return $decision;
    }
    
    /**
     * Normalize category names for comparison
     */
    private function normalizeCategoryName($category) {
        // Convert display names to internal keys
        $categoryMap = array_flip(self::CRAFT_CATEGORIES);
        
        if (isset($categoryMap[$category])) {
            return $categoryMap[$category];
        }
        
        // Fallback: normalize string
        return strtolower(str_replace([' ', '/', '-'], '_', $category));
    }
    
    /**
     * Store validation results in database
     */
    public function storeValidationResult($imageId, $imageType, $validationResult, $userId, $tutorialId) {
        try {
            $this->ensureCraftValidationTable();
            
            $classification = $validationResult['classification'];
            $decision = $validationResult['validation_decision'];
            $aiDetection = $validationResult['ai_detection'] ?? null;
            
            // Extract AI detection data
            $aiRiskScore = 0;
            $aiRiskLevel = 'unknown';
            $aiDetectionDecision = 'pass';
            $aiDetectionEvidence = null;
            $metadataAiKeywords = null;
            $exifCameraPresent = null;
            $textureLaplacianVariance = null;
            $watermarkDetected = 0;
            
            if ($aiDetection && isset($aiDetection['success']) && $aiDetection['success']) {
                $aiRiskScore = $aiDetection['ai_risk_score'] ?? 0;
                $aiRiskLevel = $aiDetection['risk_level'] ?? 'unknown';
                $aiDetectionDecision = $aiDetection['decision'] ?? 'pass';
                $aiDetectionEvidence = json_encode($aiDetection['detection_evidence']);
                
                // Extract specific evidence fields
                $evidence = $aiDetection['detection_evidence'];
                
                if (isset($evidence['metadata_analysis']['ai_keywords_found'])) {
                    $metadataAiKeywords = json_encode($evidence['metadata_analysis']['ai_keywords_found']);
                }
                
                if (isset($evidence['exif_analysis']['has_camera_metadata'])) {
                    $exifCameraPresent = $evidence['exif_analysis']['has_camera_metadata'] ? 1 : 0;
                }
                
                if (isset($evidence['texture_analysis']['laplacian_variance'])) {
                    $textureLaplacianVariance = $evidence['texture_analysis']['laplacian_variance'];
                }
                
                if (isset($evidence['watermark_analysis']['watermark_detected'])) {
                    $watermarkDetected = $evidence['watermark_analysis']['watermark_detected'] ? 1 : 0;
                }
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO craft_image_validation_v2 
                (image_id, image_type, user_id, tutorial_id, 
                 predicted_category, prediction_confidence, category_matches, 
                 ai_decision, requires_review, decision_reasons,
                 all_predictions, classification_data,
                 ai_risk_score, ai_risk_level, ai_detection_decision, ai_detection_evidence,
                 metadata_ai_keywords, exif_camera_present, texture_laplacian_variance, watermark_detected,
                 created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                predicted_category = VALUES(predicted_category),
                prediction_confidence = VALUES(prediction_confidence),
                category_matches = VALUES(category_matches),
                ai_decision = VALUES(ai_decision),
                requires_review = VALUES(requires_review),
                decision_reasons = VALUES(decision_reasons),
                all_predictions = VALUES(all_predictions),
                classification_data = VALUES(classification_data),
                ai_risk_score = VALUES(ai_risk_score),
                ai_risk_level = VALUES(ai_risk_level),
                ai_detection_decision = VALUES(ai_detection_decision),
                ai_detection_evidence = VALUES(ai_detection_evidence),
                metadata_ai_keywords = VALUES(metadata_ai_keywords),
                exif_camera_present = VALUES(exif_camera_present),
                texture_laplacian_variance = VALUES(texture_laplacian_variance),
                watermark_detected = VALUES(watermark_detected),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $classification['predicted_category'] ?? null,
                $classification['confidence'] ?? 0.0,
                $decision['category_match'] ? 1 : 0,
                $decision['status'],
                $decision['requires_review'] ? 1 : 0,
                json_encode($decision['reasons']),
                json_encode($classification['all_predictions'] ?? []),
                json_encode($validationResult),
                $aiRiskScore,
                $aiRiskLevel,
                $aiDetectionDecision,
                $aiDetectionEvidence,
                $metadataAiKeywords,
                $exifCameraPresent,
                $textureLaplacianVariance,
                $watermarkDetected
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing validation result: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure validation table exists
     */
    private function ensureCraftValidationTable() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `craft_image_validation_v2` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `image_id` varchar(255) NOT NULL,
                  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `tutorial_id` int(11) DEFAULT NULL,
                  `predicted_category` varchar(50) DEFAULT NULL,
                  `prediction_confidence` decimal(5,4) DEFAULT 0.0000,
                  `category_matches` tinyint(1) DEFAULT 0,
                  `ai_decision` enum('auto-approve', 'auto-reject', 'flag-for-review') DEFAULT 'flag-for-review',
                  `requires_review` tinyint(1) DEFAULT 1,
                  `decision_reasons` json DEFAULT NULL,
                  `all_predictions` json DEFAULT NULL,
                  `classification_data` json DEFAULT NULL,
                  `ai_risk_score` int(11) DEFAULT 0,
                  `ai_risk_level` enum('low', 'medium', 'high', 'unknown') DEFAULT 'unknown',
                  `ai_detection_decision` enum('pass', 'flag', 'reject') DEFAULT 'pass',
                  `ai_detection_evidence` json DEFAULT NULL,
                  `metadata_ai_keywords` json DEFAULT NULL,
                  `exif_camera_present` tinyint(1) DEFAULT NULL,
                  `texture_laplacian_variance` decimal(10,2) DEFAULT NULL,
                  `watermark_detected` tinyint(1) DEFAULT 0,
                  `admin_decision` enum('approved', 'rejected') DEFAULT NULL,
                  `admin_notes` text DEFAULT NULL,
                  `reviewed_by` int(11) DEFAULT NULL,
                  `reviewed_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_image_validation_v2` (`image_id`, `image_type`),
                  KEY `idx_ai_decision` (`ai_decision`),
                  KEY `idx_requires_review` (`requires_review`),
                  KEY `idx_predicted_category` (`predicted_category`),
                  KEY `idx_user_tutorial` (`user_id`, `tutorial_id`),
                  KEY `idx_ai_risk_level` (`ai_risk_level`),
                  KEY `idx_ai_detection_decision` (`ai_detection_decision`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Validation table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Create error result
     */
    private function createErrorResult($errorCode, $errorMessage) {
        return [
            'success' => false,
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'ai_decision' => 'flag-for-review',
            'requires_admin_review' => true,
            'classification' => [
                'success' => false,
                'error_message' => $errorMessage
            ],
            'validation_decision' => [
                'status' => 'flag-for-review',
                'requires_review' => true,
                'reasons' => [$errorMessage],
                'explanation' => "Error occurred: $errorMessage"
            ]
        ];
    }
}
?>