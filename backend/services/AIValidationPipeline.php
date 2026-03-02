<?php
/**
 * AI Validation Pipeline Service
 * 
 * Comprehensive AI-assisted validation system for tutorial practice uploads
 * Implements explainable decision-making with multiple validation layers:
 * - Craft category classification using trained MobileNet
 * - AI-generated image detection via metadata analysis
 * - Perceptual hashing for duplicate detection
 * - Rule-based decision engine with confidence thresholds
 * 
 * Designed for academic demonstration and research purposes
 */

class AIValidationPipeline {
    private $pdo;
    private $aiServiceUrl;
    private $config;
    
    // Validation status constants
    const STATUS_AUTO_APPROVED = 'auto_approved';
    const STATUS_AUTO_REJECTED = 'auto_rejected';
    const STATUS_AI_FLAGGED = 'ai_flagged';
    const STATUS_PROCESSING_ERROR = 'processing_error';
    
    // Decision confidence thresholds
    const HIGH_CONFIDENCE_THRESHOLD = 0.85;
    const MEDIUM_CONFIDENCE_THRESHOLD = 0.60;
    const LOW_CONFIDENCE_THRESHOLD = 0.30;
    const CATEGORY_MISMATCH_THRESHOLD = 0.75;
    const DUPLICATE_SIMILARITY_THRESHOLD = 0.90;
    
    // Supported craft categories (matching trained model)
    const CRAFT_CATEGORIES = [
        'candle_making' => 'Candle Making',
        'clay_modeling' => 'Clay Modeling',
        'gift_making' => 'Gift Making',
        'hand_embroidery' => 'Hand Embroidery',
        'jewelry_making' => 'Jewelry Making',
        'mehandi_art' => 'Mylanchi / Mehandi Art',
        'resin_art' => 'Resin Art'
    ];
    
    // AI generator signatures for metadata detection
    const AI_GENERATOR_SIGNATURES = [
        'stable_diffusion' => ['stable diffusion', 'stablediffusion', 'automatic1111', 'sd-'],
        'midjourney' => ['midjourney', 'mj-', 'discord', '/imagine'],
        'dalle' => ['dall-e', 'dalle', 'openai', 'chatgpt'],
        'firefly' => ['adobe firefly', 'firefly', 'adobe'],
        'leonardo' => ['leonardo.ai', 'leonardo'],
        'runway' => ['runway ml', 'runwayml'],
        'artbreeder' => ['artbreeder'],
        'deepai' => ['deepai', 'deep-ai'],
        'nightcafe' => ['nightcafe', 'night cafe'],
        'craiyon' => ['craiyon', 'dall-e mini']
    ];
    
    public function __construct($pdo, $aiServiceUrl = null, $config = []) {
        $this->pdo = $pdo;
        $this->aiServiceUrl = $aiServiceUrl ?? getenv('AI_VALIDATION_SERVICE_URL') ?? 'http://localhost:5001';
        $this->config = array_merge([
            'enable_strict_validation' => true,
            'enable_duplicate_detection' => true,
            'enable_ai_generation_detection' => true,
            'log_decisions' => true,
            'academic_mode' => true // Enhanced logging for research purposes
        ], $config);
        
        $this->ensureValidationTables();
    }
    
    /**
     * Main validation pipeline entry point
     * Processes uploaded image through complete AI validation workflow
     */
    public function validatePracticeUpload($imageData) {
        $validationId = $this->generateValidationId();
        $startTime = microtime(true);
        
        try {
            // Initialize validation record
            $validation = [
                'validation_id' => $validationId,
                'user_id' => $imageData['user_id'],
                'tutorial_id' => $imageData['tutorial_id'],
                'selected_category' => $imageData['selected_category'],
                'image_path' => $imageData['image_path'],
                'original_filename' => $imageData['original_filename'],
                'file_size' => $imageData['file_size'],
                'upload_timestamp' => date('Y-m-d H:i:s'),
                'processing_start_time' => $startTime,
                'pipeline_stages' => [],
                'final_decision' => null,
                'confidence_scores' => [],
                'explanation' => '',
                'flags' => [],
                'metadata' => []
            ];
            
            // Stage 1: Image preprocessing and basic validation
            $this->logStage($validation, 'preprocessing', 'Starting image preprocessing');
            $preprocessResult = $this->preprocessImage($imageData['image_path']);
            $validation['pipeline_stages']['preprocessing'] = $preprocessResult;
            
            if (!$preprocessResult['success']) {
                return $this->finalizeValidation($validation, self::STATUS_AUTO_REJECTED, 
                    'Image preprocessing failed: ' . $preprocessResult['error']);
            }
            
            // Stage 2: AI craft category classification
            $this->logStage($validation, 'classification', 'Performing AI craft classification');
            $classificationResult = $this->performCraftClassification($imageData['image_path']);
            $validation['pipeline_stages']['classification'] = $classificationResult;
            $validation['confidence_scores']['craft_classification'] = $classificationResult['confidence'] ?? 0.0;
            
            // Stage 3: Category matching analysis
            $this->logStage($validation, 'category_matching', 'Analyzing category match');
            $categoryMatchResult = $this->analyzeCategoryMatch(
                $classificationResult, 
                $imageData['selected_category'],
                $imageData['tutorial_id']
            );
            $validation['pipeline_stages']['category_matching'] = $categoryMatchResult;
            $validation['confidence_scores']['category_match'] = $categoryMatchResult['match_confidence'] ?? 0.0;
            
            // Stage 4: AI-generated image detection
            if ($this->config['enable_ai_generation_detection']) {
                $this->logStage($validation, 'ai_detection', 'Detecting AI-generated content');
                $aiDetectionResult = $this->detectAIGenerated($imageData['image_path']);
                $validation['pipeline_stages']['ai_detection'] = $aiDetectionResult;
                $validation['confidence_scores']['ai_detection'] = $aiDetectionResult['confidence_score'] ?? 0.0;
            }
            
            // Stage 5: Duplicate detection via perceptual hashing
            if ($this->config['enable_duplicate_detection']) {
                $this->logStage($validation, 'duplicate_detection', 'Checking for duplicates');
                $duplicateResult = $this->detectDuplicates(
                    $imageData['image_path'], 
                    $classificationResult['predicted_category'] ?? null,
                    $imageData['user_id']
                );
                $validation['pipeline_stages']['duplicate_detection'] = $duplicateResult;
                $validation['confidence_scores']['duplicate_similarity'] = $duplicateResult['max_similarity'] ?? 0.0;
            }
            
            // Stage 6: Rule-based decision engine
            $this->logStage($validation, 'decision_engine', 'Applying decision rules');
            $decisionResult = $this->applyDecisionRules($validation);
            $validation['pipeline_stages']['decision_engine'] = $decisionResult;
            
            // Stage 7: Finalize validation
            $processingTime = microtime(true) - $startTime;
            $validation['processing_time'] = $processingTime;
            
            return $this->finalizeValidation(
                $validation, 
                $decisionResult['final_status'], 
                $decisionResult['explanation'],
                $decisionResult['flags'] ?? []
            );
            
        } catch (Exception $e) {
            error_log("AI Validation Pipeline Error: " . $e->getMessage());
            return $this->finalizeValidation($validation, self::STATUS_PROCESSING_ERROR, 
                'Validation pipeline error: ' . $e->getMessage());
        }
    }
    
    /**
     * Image preprocessing and basic validation
     */
    private function preprocessImage($imagePath) {
        try {
            if (!file_exists($imagePath)) {
                return ['success' => false, 'error' => 'Image file not found'];
            }
            
            // Get image information
            $imageInfo = getimagesize($imagePath);
            if (!$imageInfo) {
                return ['success' => false, 'error' => 'Invalid image file'];
            }
            
            $metadata = [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'type' => $imageInfo[2],
                'mime_type' => $imageInfo['mime'],
                'file_size' => filesize($imagePath)
            ];
            
            // Basic validation checks
            $issues = [];
            
            // Check minimum dimensions
            if ($metadata['width'] < 100 || $metadata['height'] < 100) {
                $issues[] = 'Image too small (minimum 100x100 pixels)';
            }
            
            // Check maximum file size (10MB)
            if ($metadata['file_size'] > 10 * 1024 * 1024) {
                $issues[] = 'File size too large (maximum 10MB)';
            }
            
            // Check aspect ratio
            $aspectRatio = $metadata['width'] / $metadata['height'];
            if ($aspectRatio > 5.0 || $aspectRatio < 0.2) {
                $issues[] = 'Extreme aspect ratio detected';
            }
            
            return [
                'success' => empty($issues),
                'metadata' => $metadata,
                'issues' => $issues,
                'warnings' => []
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Preprocessing exception: ' . $e->getMessage()];
        }
    }
    
    /**
     * AI craft category classification using trained MobileNet model
     */
    private function performCraftClassification($imagePath) {
        try {
            $url = $this->aiServiceUrl . '/classify-craft';
            
            $requestData = json_encode([
                'image_path' => realpath($imagePath),
                'return_all_predictions' => true,
                'explain_decision' => true
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                return $this->handleClassificationFallback($imagePath, "Service unavailable: $curlError");
            }
            
            if ($httpCode !== 200) {
                return $this->handleClassificationFallback($imagePath, "Service returned HTTP $httpCode");
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !$result['success']) {
                return $this->handleClassificationFallback($imagePath, 
                    $result['error_message'] ?? 'Classification failed');
            }
            
            return [
                'success' => true,
                'predicted_category' => $result['predicted_category'],
                'confidence' => $result['confidence'],
                'all_predictions' => $result['all_predictions'] ?? [],
                'is_craft_related' => $result['is_craft_related'] ?? true,
                'non_craft_confidence' => $result['non_craft_confidence'] ?? 0.0,
                'explanation' => $result['explanation'] ?? '',
                'model_used' => $result['model_used'] ?? 'unknown',
                'processing_time' => $result['processing_time'] ?? 0
            ];
            
        } catch (Exception $e) {
            return $this->handleClassificationFallback($imagePath, 'Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Fallback classification when AI service is unavailable
     */
    private function handleClassificationFallback($imagePath, $reason) {
        error_log("AI Classification fallback triggered: $reason");
        
        // Use heuristic-based classification
        $imageInfo = getimagesize($imagePath);
        $fileSize = filesize($imagePath);
        
        // Simple heuristics for craft-relatedness
        $isCraftRelated = true;
        $confidence = 0.5; // Neutral confidence
        
        // Check for obviously non-craft patterns
        if ($imageInfo) {
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            $aspectRatio = $width / $height;
            
            // Very small images (likely icons)
            if ($width < 100 || $height < 100) {
                $isCraftRelated = false;
                $confidence = 0.2;
            }
            // Extreme aspect ratios (likely screenshots/banners)
            elseif ($aspectRatio > 4.0 || $aspectRatio < 0.25) {
                $isCraftRelated = false;
                $confidence = 0.3;
            }
            // Very small files (likely simple graphics)
            elseif ($fileSize < 15000) {
                $isCraftRelated = false;
                $confidence = 0.25;
            }
        }
        
        return [
            'success' => true,
            'predicted_category' => 'hand_embroidery', // Default fallback
            'confidence' => $confidence,
            'all_predictions' => [
                ['category' => 'hand_embroidery', 'confidence' => $confidence]
            ],
            'is_craft_related' => $isCraftRelated,
            'non_craft_confidence' => $isCraftRelated ? 0.0 : 0.7,
            'explanation' => "Fallback classification used due to: $reason",
            'model_used' => 'heuristic_fallback',
            'fallback_reason' => $reason
        ];
    }
    
    /**
     * Analyze category match between AI prediction and selected tutorial
     */
    private function analyzeCategoryMatch($classificationResult, $selectedCategory, $tutorialId) {
        try {
            // Get tutorial information
            $stmt = $this->pdo->prepare("SELECT title, category, description FROM tutorials WHERE id = ?");
            $stmt->execute([$tutorialId]);
            $tutorial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tutorial) {
                return [
                    'success' => false,
                    'error' => 'Tutorial not found',
                    'match_confidence' => 0.0
                ];
            }
            
            $predictedCategory = $classificationResult['predicted_category'] ?? '';
            $confidence = $classificationResult['confidence'] ?? 0.0;
            
            // Normalize category names for comparison
            $normalizedSelected = $this->normalizeCategoryName($selectedCategory);
            $normalizedPredicted = $this->normalizeCategoryName($predictedCategory);
            
            // Check for exact match
            $exactMatch = ($normalizedSelected === $normalizedPredicted);
            
            // Check for related categories (fuzzy matching)
            $relatedMatch = $this->checkRelatedCategories($normalizedSelected, $normalizedPredicted);
            
            // Calculate match confidence
            $matchConfidence = 0.0;
            $matchType = 'no_match';
            $explanation = '';
            
            if ($exactMatch) {
                $matchConfidence = min($confidence, 0.95); // Cap at 95% for exact matches
                $matchType = 'exact_match';
                $explanation = "Exact category match: {$predictedCategory}";
            } elseif ($relatedMatch['is_related']) {
                $matchConfidence = min($confidence * 0.8, 0.85); // Reduce confidence for related matches
                $matchType = 'related_match';
                $explanation = "Related category match: {$relatedMatch['explanation']}";
            } else {
                $matchConfidence = 0.0;
                $matchType = 'mismatch';
                $explanation = "Category mismatch: predicted '{$predictedCategory}' vs selected '{$selectedCategory}'";
            }
            
            return [
                'success' => true,
                'tutorial_info' => $tutorial,
                'selected_category' => $selectedCategory,
                'predicted_category' => $predictedCategory,
                'ai_confidence' => $confidence,
                'exact_match' => $exactMatch,
                'related_match' => $relatedMatch['is_related'],
                'match_type' => $matchType,
                'match_confidence' => $matchConfidence,
                'explanation' => $explanation,
                'mismatch_severity' => $this->calculateMismatchSeverity($confidence, $exactMatch, $relatedMatch['is_related'])
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Category matching error: ' . $e->getMessage(),
                'match_confidence' => 0.0
            ];
        }
    }
    
    /**
     * Detect AI-generated images through metadata analysis
     */
    private function detectAIGenerated($imagePath) {
        try {
            $detection = [
                'is_ai_generated' => false,
                'confidence_level' => 'unknown',
                'confidence_score' => 0.0,
                'detected_generator' => null,
                'evidence' => [],
                'metadata_analysis' => [],
                'suspicious_patterns' => []
            ];
            
            // Extract comprehensive metadata
            $metadata = $this->extractImageMetadata($imagePath);
            $detection['metadata_analysis'] = $metadata;
            
            // Check for AI generator signatures
            $aiSignatures = $this->checkAIGeneratorSignatures($metadata);
            
            if ($aiSignatures['found']) {
                $detection['is_ai_generated'] = true;
                $detection['confidence_level'] = 'high';
                $detection['confidence_score'] = 0.9;
                $detection['detected_generator'] = $aiSignatures['generator'];
                $detection['evidence'] = $aiSignatures['evidence'];
            } else {
                // Check for suspicious patterns
                $suspiciousPatterns = $this->analyzeSuspiciousPatterns($metadata);
                $detection['suspicious_patterns'] = $suspiciousPatterns;
                
                if (count($suspiciousPatterns) >= 3) {
                    $detection['confidence_level'] = 'suspicious';
                    $detection['confidence_score'] = 0.6;
                    $detection['evidence'] = $suspiciousPatterns;
                } elseif (count($suspiciousPatterns) >= 1) {
                    $detection['confidence_level'] = 'low_suspicious';
                    $detection['confidence_score'] = 0.3;
                    $detection['evidence'] = $suspiciousPatterns;
                } else {
                    $detection['confidence_level'] = 'likely_authentic';
                    $detection['confidence_score'] = 0.1;
                }
            }
            
            return $detection;
            
        } catch (Exception $e) {
            error_log("AI detection error: " . $e->getMessage());
            return [
                'is_ai_generated' => false,
                'confidence_level' => 'error',
                'confidence_score' => 0.0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Detect duplicate images using perceptual hashing
     */
    private function detectDuplicates($imagePath, $category, $userId) {
        try {
            // Generate perceptual hash for the uploaded image
            $currentHash = $this->generatePerceptualHash($imagePath);
            
            if (!$currentHash) {
                return [
                    'success' => false,
                    'error' => 'Could not generate perceptual hash',
                    'max_similarity' => 0.0
                ];
            }
            
            // Query existing approved images in the same category
            $stmt = $this->pdo->prepare("
                SELECT ph.image_id, ph.phash, pu.user_id, pu.upload_date, pu.images
                FROM practice_image_hashes ph
                JOIN practice_uploads pu ON ph.upload_id = pu.id
                WHERE ph.category = ? AND pu.status = 'approved'
                ORDER BY pu.upload_date DESC
                LIMIT 100
            ");
            $stmt->execute([$category]);
            $existingHashes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $duplicates = [];
            $maxSimilarity = 0.0;
            
            foreach ($existingHashes as $existing) {
                $similarity = $this->calculateHashSimilarity($currentHash, $existing['phash']);
                
                if ($similarity > self::DUPLICATE_SIMILARITY_THRESHOLD) {
                    $duplicates[] = [
                        'image_id' => $existing['image_id'],
                        'user_id' => $existing['user_id'],
                        'upload_date' => $existing['upload_date'],
                        'similarity' => $similarity,
                        'is_same_user' => ($existing['user_id'] == $userId)
                    ];
                }
                
                $maxSimilarity = max($maxSimilarity, $similarity);
            }
            
            // Store the hash for future comparisons
            $this->storePerceptualHash($imagePath, $currentHash, $category, $userId);
            
            return [
                'success' => true,
                'current_hash' => $currentHash,
                'duplicates_found' => count($duplicates),
                'duplicates' => $duplicates,
                'max_similarity' => $maxSimilarity,
                'images_compared' => count($existingHashes),
                'threshold_used' => self::DUPLICATE_SIMILARITY_THRESHOLD
            ];
            
        } catch (Exception $e) {
            error_log("Duplicate detection error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'max_similarity' => 0.0
            ];
        }
    }
    
    /**
     * Apply rule-based decision engine
     */
    private function applyDecisionRules($validation) {
        $rules = [];
        $flags = [];
        $finalStatus = self::STATUS_AUTO_APPROVED;
        $explanation = '';
        $confidence = 1.0;
        
        // Extract validation results
        $classification = $validation['pipeline_stages']['classification'] ?? [];
        $categoryMatch = $validation['pipeline_stages']['category_matching'] ?? [];
        $aiDetection = $validation['pipeline_stages']['ai_detection'] ?? [];
        $duplicateDetection = $validation['pipeline_stages']['duplicate_detection'] ?? [];
        
        // Rule 1: Reject confirmed AI-generated images
        if ($aiDetection['is_ai_generated'] ?? false) {
            $rules[] = 'REJECT: AI-generated image detected';
            $finalStatus = self::STATUS_AUTO_REJECTED;
            $explanation = "AI-generated image detected: {$aiDetection['detected_generator']}";
            return ['final_status' => $finalStatus, 'explanation' => $explanation, 'rules_applied' => $rules, 'flags' => $flags];
        }
        
        // Rule 2: Reject clearly non-craft images
        if (!($classification['is_craft_related'] ?? true)) {
            $nonCraftConfidence = $classification['non_craft_confidence'] ?? 0.0;
            if ($nonCraftConfidence > 0.7) {
                $rules[] = 'REJECT: Non-craft content detected';
                $finalStatus = self::STATUS_AUTO_REJECTED;
                $explanation = "Image appears unrelated to crafts (confidence: " . round($nonCraftConfidence * 100, 1) . "%)";
                return ['final_status' => $finalStatus, 'explanation' => $explanation, 'rules_applied' => $rules, 'flags' => $flags];
            }
        }
        
        // Rule 3: Reject high-confidence category mismatches
        if (($categoryMatch['mismatch_severity'] ?? '') === 'high') {
            $rules[] = 'REJECT: High-confidence category mismatch';
            $finalStatus = self::STATUS_AUTO_REJECTED;
            $explanation = "Wrong practice type: {$categoryMatch['explanation']}";
            return ['final_status' => $finalStatus, 'explanation' => $explanation, 'rules_applied' => $rules, 'flags' => $flags];
        }
        
        // Rule 4: Reject exact duplicates
        if (($duplicateDetection['max_similarity'] ?? 0.0) > 0.95) {
            $rules[] = 'REJECT: Exact duplicate detected';
            $finalStatus = self::STATUS_AUTO_REJECTED;
            $explanation = "Duplicate image detected (similarity: " . round($duplicateDetection['max_similarity'] * 100, 1) . "%)";
            return ['final_status' => $finalStatus, 'explanation' => $explanation, 'rules_applied' => $rules, 'flags' => $flags];
        }
        
        // Rule 5: Flag suspicious AI-generated images
        if (($aiDetection['confidence_level'] ?? '') === 'suspicious') {
            $flags[] = 'Suspicious AI generation patterns';
            $finalStatus = self::STATUS_AI_FLAGGED;
            $confidence *= 0.7;
        }
        
        // Rule 6: Flag medium confidence category mismatches
        if (($categoryMatch['mismatch_severity'] ?? '') === 'medium') {
            $flags[] = 'Possible category mismatch';
            $finalStatus = self::STATUS_AI_FLAGGED;
            $confidence *= 0.8;
        }
        
        // Rule 7: Flag potential duplicates
        if (($duplicateDetection['max_similarity'] ?? 0.0) > 0.85) {
            $flags[] = 'Potential duplicate detected';
            $finalStatus = self::STATUS_AI_FLAGGED;
            $confidence *= 0.9;
        }
        
        // Rule 8: Flag low confidence classifications
        if (($classification['confidence'] ?? 0.0) < self::LOW_CONFIDENCE_THRESHOLD) {
            $flags[] = 'Low AI classification confidence';
            $finalStatus = self::STATUS_AI_FLAGGED;
            $confidence *= 0.6;
        }
        
        // Generate explanation
        if ($finalStatus === self::STATUS_AUTO_APPROVED) {
            $explanation = "Image passed all validation checks and was automatically approved";
            $rules[] = 'APPROVE: All validation checks passed';
        } elseif ($finalStatus === self::STATUS_AI_FLAGGED) {
            $explanation = "Image flagged for manual review: " . implode(', ', $flags);
            $rules[] = 'FLAG: Manual review required';
        }
        
        return [
            'final_status' => $finalStatus,
            'explanation' => $explanation,
            'flags' => $flags,
            'rules_applied' => $rules,
            'overall_confidence' => $confidence,
            'decision_factors' => [
                'craft_classification' => $classification['confidence'] ?? 0.0,
                'category_match' => $categoryMatch['match_confidence'] ?? 0.0,
                'ai_detection' => 1.0 - ($aiDetection['confidence_score'] ?? 0.0),
                'duplicate_check' => 1.0 - ($duplicateDetection['max_similarity'] ?? 0.0)
            ]
        ];
    }
    
    // Helper methods continue...
    
    /**
     * Generate perceptual hash for duplicate detection
     */
    private function generatePerceptualHash($imagePath) {
        try {
            // Simple perceptual hash implementation
            // In production, consider using more sophisticated algorithms like dHash or aHash
            
            $image = imagecreatefrompng($imagePath);
            if (!$image) {
                $image = imagecreatefromjpeg($imagePath);
            }
            if (!$image) {
                return null;
            }
            
            // Resize to 8x8 for hash generation
            $resized = imagecreatetruecolor(8, 8);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, 8, 8, imagesx($image), imagesy($image));
            
            // Convert to grayscale and generate hash
            $hash = '';
            $pixels = [];
            
            for ($y = 0; $y < 8; $y++) {
                for ($x = 0; $x < 8; $x++) {
                    $rgb = imagecolorat($resized, $x, $y);
                    $gray = (($rgb >> 16) & 0xFF) * 0.299 + (($rgb >> 8) & 0xFF) * 0.587 + ($rgb & 0xFF) * 0.114;
                    $pixels[] = $gray;
                }
            }
            
            // Calculate average
            $average = array_sum($pixels) / count($pixels);
            
            // Generate binary hash
            foreach ($pixels as $pixel) {
                $hash .= ($pixel > $average) ? '1' : '0';
            }
            
            imagedestroy($image);
            imagedestroy($resized);
            
            return $hash;
            
        } catch (Exception $e) {
            error_log("Perceptual hash generation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate similarity between two perceptual hashes
     */
    private function calculateHashSimilarity($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) {
            return 0.0;
        }
        
        $differences = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $differences++;
            }
        }
        
        return 1.0 - ($differences / $length);
    }
    
    /**
     * Store perceptual hash for future comparisons
     */
    private function storePerceptualHash($imagePath, $hash, $category, $userId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO practice_image_hashes (image_path, phash, category, user_id, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                phash = VALUES(phash),
                updated_at = NOW()
            ");
            
            $stmt->execute([basename($imagePath), $hash, $category, $userId]);
            
        } catch (Exception $e) {
            error_log("Error storing perceptual hash: " . $e->getMessage());
        }
    }
    
    /**
     * Extract comprehensive image metadata
     */
    private function extractImageMetadata($imagePath) {
        $metadata = [
            'file_info' => [],
            'exif_data' => [],
            'technical_info' => [],
            'software_info' => [],
            'creation_info' => []
        ];
        
        try {
            // Basic file information
            $metadata['file_info'] = [
                'size' => filesize($imagePath),
                'mime_type' => mime_content_type($imagePath),
                'extension' => pathinfo($imagePath, PATHINFO_EXTENSION),
                'basename' => basename($imagePath)
            ];
            
            // Image dimensions and technical info
            $imageInfo = getimagesize($imagePath);
            if ($imageInfo) {
                $metadata['technical_info'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'type' => $imageInfo[2],
                    'bits' => $imageInfo['bits'] ?? null,
                    'channels' => $imageInfo['channels'] ?? null,
                    'aspect_ratio' => $imageInfo[0] / max($imageInfo[1], 1)
                ];
            }
            
            // EXIF data extraction
            if (function_exists('exif_read_data') && in_array(strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)), ['jpg', 'jpeg'])) {
                $exif = @exif_read_data($imagePath);
                if ($exif) {
                    $metadata['exif_data'] = $exif;
                    
                    // Extract key software and creation info
                    $metadata['software_info'] = [
                        'make' => $exif['Make'] ?? null,
                        'model' => $exif['Model'] ?? null,
                        'software' => $exif['Software'] ?? null,
                        'artist' => $exif['Artist'] ?? null,
                        'copyright' => $exif['Copyright'] ?? null,
                        'user_comment' => $exif['UserComment'] ?? null,
                        'image_description' => $exif['ImageDescription'] ?? null
                    ];
                    
                    $metadata['creation_info'] = [
                        'date_time' => $exif['DateTime'] ?? null,
                        'date_time_original' => $exif['DateTimeOriginal'] ?? null,
                        'date_time_digitized' => $exif['DateTimeDigitized'] ?? null
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("Metadata extraction error: " . $e->getMessage());
            $metadata['error'] = $e->getMessage();
        }
        
        return $metadata;
    }
    
    /**
     * Check for AI generator signatures in metadata
     */
    private function checkAIGeneratorSignatures($metadata) {
        $result = [
            'found' => false,
            'generator' => null,
            'evidence' => []
        ];
        
        // Fields to check for AI signatures
        $fieldsToCheck = [
            'software_info.software',
            'software_info.artist',
            'software_info.copyright',
            'software_info.user_comment',
            'software_info.image_description'
        ];
        
        foreach ($fieldsToCheck as $field) {
            $value = $this->getNestedValue($metadata, $field);
            if ($value) {
                $value = strtolower($value);
                
                foreach (self::AI_GENERATOR_SIGNATURES as $generator => $signatures) {
                    foreach ($signatures as $signature) {
                        if (strpos($value, $signature) !== false) {
                            $result['found'] = true;
                            $result['generator'] = $generator;
                            $result['evidence'][] = "Found '{$signature}' in {$field}";
                        }
                    }
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Analyze suspicious patterns that might indicate AI generation
     */
    private function analyzeSuspiciousPatterns($metadata) {
        $patterns = [];
        
        try {
            // Check for missing EXIF data
            if (empty($metadata['exif_data']) || count($metadata['exif_data']) < 5) {
                $patterns[] = 'Minimal or missing EXIF data';
            }
            
            // Check for unusual dimensions
            $width = $metadata['technical_info']['width'] ?? 0;
            $height = $metadata['technical_info']['height'] ?? 0;
            
            $commonAISizes = [512, 768, 1024, 1536, 2048];
            if (in_array($width, $commonAISizes) && in_array($height, $commonAISizes)) {
                $patterns[] = "Common AI output dimensions: {$width}x{$height}";
            }
            
            // Check for perfect square dimensions
            if ($width === $height && $width > 0) {
                $patterns[] = "Perfect square dimensions: {$width}x{$height}";
            }
            
            // Check for missing camera information
            $make = $metadata['software_info']['make'] ?? '';
            $model = $metadata['software_info']['model'] ?? '';
            if (empty($make) && empty($model)) {
                $patterns[] = 'No camera make/model information';
            }
            
            // Check file size patterns
            $fileSize = $metadata['file_info']['size'] ?? 0;
            if ($fileSize > 0 && $fileSize < 50000) { // Less than 50KB
                $patterns[] = 'Unusually small file size for image dimensions';
            }
            
        } catch (Exception $e) {
            error_log("Suspicious pattern analysis error: " . $e->getMessage());
        }
        
        return $patterns;
    }
    
    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue($array, $key) {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return null;
            }
        }
        
        return $value;
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
     * Check for related categories (fuzzy matching)
     */
    private function checkRelatedCategories($selected, $predicted) {
        // Define related category groups
        $relatedGroups = [
            'textile_arts' => ['hand_embroidery', 'gift_making'],
            'decorative_arts' => ['candle_making', 'resin_art', 'mehandi_art'],
            'sculptural_arts' => ['clay_modeling', 'jewelry_making'],
            'handmade_crafts' => ['gift_making', 'jewelry_making', 'hand_embroidery']
        ];
        
        // Check if both categories are in the same group
        foreach ($relatedGroups as $group => $categories) {
            if (in_array($selected, $categories) && in_array($predicted, $categories)) {
                return [
                    'is_related' => true,
                    'group' => $group,
                    'explanation' => "Both categories belong to {$group}"
                ];
            }
        }
        
        return ['is_related' => false, 'explanation' => 'No relationship found'];
    }
    
    /**
     * Calculate mismatch severity
     */
    private function calculateMismatchSeverity($confidence, $exactMatch, $relatedMatch) {
        if ($exactMatch) {
            return 'none';
        } elseif ($relatedMatch) {
            return 'low';
        } elseif ($confidence >= 0.8) {
            return 'high';
        } elseif ($confidence >= 0.5) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Store validation record in database
     */
    private function storeValidationRecord($validation) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO ai_validation_records (
                    validation_id, user_id, tutorial_id, selected_category, image_path,
                    original_filename, file_size, final_status, explanation, flags,
                    confidence_scores, pipeline_stages, processing_time, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $validation['validation_id'],
                $validation['user_id'],
                $validation['tutorial_id'],
                $validation['selected_category'],
                $validation['image_path'],
                $validation['original_filename'],
                $validation['file_size'],
                $validation['final_decision']['status'],
                $validation['final_decision']['explanation'],
                json_encode($validation['final_decision']['flags']),
                json_encode($validation['confidence_scores']),
                json_encode($validation['pipeline_stages']),
                $validation['processing_time'] ?? 0
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing validation record: " . $e->getMessage());
        }
    }
    
    /**
     * Update learning progress for auto-approved submissions
     */
    private function updateLearningProgress($userId, $tutorialId, $approved) {
        try {
            if ($approved) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO learning_progress (
                        user_id, tutorial_id, practice_uploaded, practice_completed, 
                        practice_admin_approved, last_accessed
                    ) VALUES (?, ?, 1, 1, 1, NOW())
                    ON DUPLICATE KEY UPDATE 
                    practice_uploaded = 1, 
                    practice_completed = 1,
                    practice_admin_approved = 1,
                    last_accessed = NOW()
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    INSERT INTO learning_progress (
                        user_id, tutorial_id, practice_uploaded, practice_completed, 
                        practice_admin_approved, last_accessed
                    ) VALUES (?, ?, 1, 0, 0, NOW())
                    ON DUPLICATE KEY UPDATE 
                    practice_uploaded = 1, 
                    practice_completed = 0,
                    practice_admin_approved = 0,
                    last_accessed = NOW()
                ");
            }
            
            $stmt->execute([$userId, $tutorialId]);
            
        } catch (Exception $e) {
            error_log("Error updating learning progress: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure all required database tables exist
     */
    private function ensureValidationTables() {
        try {
            // AI validation records table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `ai_validation_records` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `validation_id` varchar(255) NOT NULL UNIQUE,
                    `user_id` int(11) NOT NULL,
                    `tutorial_id` int(11) NOT NULL,
                    `selected_category` varchar(100) NOT NULL,
                    `image_path` varchar(500) NOT NULL,
                    `original_filename` varchar(255) NOT NULL,
                    `file_size` int(11) NOT NULL,
                    `final_status` enum('auto_approved', 'auto_rejected', 'ai_flagged', 'processing_error') NOT NULL,
                    `explanation` text NOT NULL,
                    `flags` json DEFAULT NULL,
                    `confidence_scores` json DEFAULT NULL,
                    `pipeline_stages` json DEFAULT NULL,
                    `processing_time` decimal(8,4) DEFAULT 0.0000,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `idx_validation_id` (`validation_id`),
                    KEY `idx_user_tutorial` (`user_id`, `tutorial_id`),
                    KEY `idx_status` (`final_status`),
                    KEY `idx_created_at` (`created_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Practice image hashes table for duplicate detection
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `practice_image_hashes` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `upload_id` int(11) DEFAULT NULL,
                    `image_id` varchar(255) DEFAULT NULL,
                    `image_path` varchar(500) NOT NULL,
                    `phash` varchar(64) NOT NULL,
                    `category` varchar(100) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `unique_image_path` (`image_path`),
                    KEY `idx_category` (`category`),
                    KEY `idx_user_id` (`user_id`),
                    KEY `idx_phash` (`phash`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Enhanced practice uploads table
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `practice_uploads_enhanced` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `validation_id` varchar(255) DEFAULT NULL,
                    `user_id` int(11) NOT NULL,
                    `tutorial_id` int(11) NOT NULL,
                    `description` text DEFAULT NULL,
                    `images` json DEFAULT NULL,
                    `status` enum('auto_approved', 'auto_rejected', 'ai_flagged', 'processing_error', 'pending') DEFAULT 'pending',
                    `ai_validation_status` enum('passed', 'failed', 'flagged', 'error') DEFAULT 'passed',
                    `predicted_category` varchar(100) DEFAULT NULL,
                    `classification_confidence` decimal(5,4) DEFAULT 0.0000,
                    `category_match` tinyint(1) DEFAULT 0,
                    `duplicate_detected` tinyint(1) DEFAULT 0,
                    `ai_generated_detected` tinyint(1) DEFAULT 0,
                    `admin_feedback` text DEFAULT NULL,
                    `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
                    `reviewed_date` timestamp NULL DEFAULT NULL,
                    `auto_approved_date` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_validation_id` (`validation_id`),
                    KEY `idx_user_tutorial` (`user_id`, `tutorial_id`),
                    KEY `idx_status` (`status`),
                    KEY `idx_ai_validation` (`ai_validation_status`),
                    KEY `idx_upload_date` (`upload_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
        } catch (Exception $e) {
            error_log("Database table creation error: " . $e->getMessage());
        }
    }
}
}
?>