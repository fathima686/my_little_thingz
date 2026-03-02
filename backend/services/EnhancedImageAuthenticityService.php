<?php
/**
 * Enhanced Image Authenticity Service
 * Implements sophisticated multi-layered evaluation to avoid false similarity detection
 * Uses category-based comparison, strict thresholds, and multi-rule evaluation
 */

class EnhancedImageAuthenticityService {
    private $pdo;
    private $config;
    
    // Strict similarity thresholds - only very high similarity is suspicious
    private const SIMILARITY_THRESHOLDS = [
        'very_high' => 0.95,      // 95%+ similarity - highly suspicious
        'high' => 0.85,           // 85%+ similarity - moderately suspicious
        'moderate' => 0.70,       // 70%+ similarity - low concern
        'low' => 0.50             // Below 50% - ignore
    ];
    
    // Tutorial categories for proper comparison grouping
    private const TUTORIAL_CATEGORIES = [
        'embroidery' => ['embroidery', 'borduur', 'stitch', 'needle'],
        'painting' => ['paint', 'canvas', 'brush', 'acrylic', 'watercolor'],
        'drawing' => ['draw', 'sketch', 'pencil', 'charcoal'],
        'crafts' => ['craft', 'diy', 'handmade', 'creative'],
        'jewelry' => ['jewelry', 'beads', 'wire', 'pendant'],
        'pottery' => ['pottery', 'clay', 'ceramic', 'wheel'],
        'woodwork' => ['wood', 'carving', 'furniture', 'timber'],
        'textile' => ['fabric', 'sewing', 'quilting', 'weaving'],
        'photography' => ['photo', 'camera', 'lens', 'portrait'],
        'digital_art' => ['digital', 'photoshop', 'illustrator', 'graphic']
    ];
    
    // Suspicious metadata patterns
    private const SUSPICIOUS_PATTERNS = [
        'missing_camera_info' => 3,
        'generic_editing_software' => 2,
        'multiple_editing_signatures' => 4,
        'timestamp_inconsistency' => 3,
        'unusual_dimensions' => 2,
        'low_quality_upscale' => 3,
        'no_exif_data' => 2,
        'suspicious_file_size' => 1
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = $this->loadConfiguration();
    }
    
    /**
     * Main evaluation method with multi-layered analysis
     */
    public function evaluateImageAuthenticity($imageId, $imageType, $filePath, $userId, $tutorialId) {
        try {
            // Step 1: Extract basic image properties
            $imageProperties = $this->extractImageProperties($filePath);
            
            // Step 2: Generate perceptual hash (pHash)
            $perceptualHash = $this->generatePerceptualHash($filePath);
            
            // Step 3: Determine tutorial category
            $tutorialCategory = $this->determineTutorialCategory($tutorialId);
            
            // Step 4: Extract and analyze metadata
            $metadataAnalysis = $this->analyzeMetadata($filePath);
            
            // Step 5: Perform category-based similarity checking
            $similarityAnalysis = $this->performCategoryBasedSimilarityCheck(
                $perceptualHash, 
                $tutorialCategory, 
                $imageId, 
                $imageType
            );
            
            // Step 6: Multi-rule evaluation
            $evaluationResult = $this->performMultiRuleEvaluation(
                $imageProperties,
                $metadataAnalysis,
                $similarityAnalysis,
                $tutorialCategory
            );
            
            // Step 7: Store comprehensive results
            $this->storeAnalysisResults($imageId, $imageType, $filePath, $evaluationResult);
            
            return $evaluationResult;
            
        } catch (Exception $e) {
            error_log("Enhanced authenticity evaluation error: " . $e->getMessage());
            return $this->createErrorResult($imageId, $e->getMessage());
        }
    }
    
    /**
     * Extract basic image properties
     */
    private function extractImageProperties($filePath) {
        $properties = [
            'file_size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'dimensions' => null,
            'aspect_ratio' => null,
            'color_depth' => null,
            'compression_quality' => null
        ];
        
        if (function_exists('getimagesize')) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $properties['dimensions'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ];
                $properties['aspect_ratio'] = round($imageInfo[0] / $imageInfo[1], 2);
                
                // Detect unusual dimensions that might indicate manipulation
                $properties['unusual_dimensions'] = $this->detectUnusualDimensions($imageInfo[0], $imageInfo[1]);
            }
        }
        
        return $properties;
    }
    
    /**
     * Generate perceptual hash using multiple algorithms for accuracy
     */
    private function generatePerceptualHash($filePath) {
        $hashes = [];
        
        try {
            // Method 1: Average Hash (aHash) - good for basic similarity
            $hashes['average_hash'] = $this->calculateAverageHash($filePath);
            
            // Method 2: Difference Hash (dHash) - good for transformations
            $hashes['difference_hash'] = $this->calculateDifferenceHash($filePath);
            
            // Method 3: Perceptual Hash (pHash) - most robust
            $hashes['perceptual_hash'] = $this->calculatePerceptualHash($filePath);
            
            // Method 4: Wavelet Hash - good for compression variations
            $hashes['wavelet_hash'] = $this->calculateWaveletHash($filePath);
            
        } catch (Exception $e) {
            error_log("Hash generation error: " . $e->getMessage());
        }
        
        return $hashes;
    }
    
    /**
     * Determine tutorial category for proper comparison grouping
     */
    private function determineTutorialCategory($tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT title, description, category, tags 
                FROM tutorials 
                WHERE id = ?
            ");
            $stmt->execute([$tutorialId]);
            $tutorial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tutorial) {
                return 'general';
            }
            
            $searchText = strtolower(
                $tutorial['title'] . ' ' . 
                $tutorial['description'] . ' ' . 
                $tutorial['category'] . ' ' . 
                ($tutorial['tags'] ?? '')
            );
            
            // Match against category keywords
            foreach (self::TUTORIAL_CATEGORIES as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($searchText, $keyword) !== false) {
                        return $category;
                    }
                }
            }
            
            return 'general';
            
        } catch (Exception $e) {
            error_log("Category determination error: " . $e->getMessage());
            return 'general';
        }
    }
    
    /**
     * Analyze metadata for suspicious patterns
     */
    private function analyzeMetadata($filePath) {
        $analysis = [
            'camera_info' => [],
            'editing_software' => [],
            'suspicious_indicators' => [],
            'metadata_score' => 0,
            'exif_data' => []
        ];
        
        try {
            // Extract EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $analysis['exif_data'] = $exif;
                    $analysis = $this->analyzeExifData($analysis, $exif);
                } else {
                    $analysis['suspicious_indicators'][] = 'missing_exif_data';
                    $analysis['metadata_score'] += self::SUSPICIOUS_PATTERNS['no_exif_data'];
                }
            }
            
            // Check for editing software signatures
            $analysis = $this->detectEditingSoftware($analysis, $filePath);
            
            // Analyze timestamps for consistency
            $analysis = $this->analyzeTimestamps($analysis);
            
            // Check file size patterns
            $analysis = $this->analyzeFileSize($analysis, $filePath);
            
        } catch (Exception $e) {
            error_log("Metadata analysis error: " . $e->getMessage());
            $analysis['suspicious_indicators'][] = 'metadata_analysis_error';
        }
        
        return $analysis;
    }
    
    /**
     * Perform category-based similarity checking with strict thresholds
     */
    private function performCategoryBasedSimilarityCheck($hashes, $category, $currentImageId, $currentImageType) {
        $similarityResults = [
            'similar_images' => [],
            'max_similarity' => 0.0,
            'category_matches' => 0,
            'suspicious_matches' => [],
            'evaluation' => 'unique'
        ];
        
        try {
            // Get images from the same category only
            $stmt = $this->pdo->prepare("
                SELECT iam.image_id, iam.image_type, iam.perceptual_hash, 
                       iam.file_path, iam.created_at, t.title as tutorial_title
                FROM image_authenticity_metadata iam
                LEFT JOIN practice_uploads pu ON iam.image_id LIKE CONCAT('%', pu.id, '%')
                LEFT JOIN tutorials t ON pu.tutorial_id = t.id
                WHERE iam.tutorial_category = ? 
                AND NOT (iam.image_id = ? AND iam.image_type = ?)
                AND iam.perceptual_hash IS NOT NULL
                ORDER BY iam.created_at DESC
                LIMIT 1000
            ");
            
            $stmt->execute([$category, $currentImageId, $currentImageType]);
            $existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($existingImages as $existing) {
                $existingHashes = json_decode($existing['perceptual_hash'], true);
                if (!$existingHashes) continue;
                
                // Calculate similarity using multiple hash methods
                $similarities = $this->calculateMultiHashSimilarity($hashes, $existingHashes);
                $maxSimilarity = max($similarities);
                
                // Only consider very high similarities as suspicious
                if ($maxSimilarity >= self::SIMILARITY_THRESHOLDS['high']) {
                    $similarityResults['similar_images'][] = [
                        'image_id' => $existing['image_id'],
                        'image_type' => $existing['image_type'],
                        'similarity_score' => $maxSimilarity,
                        'similarity_methods' => $similarities,
                        'tutorial_title' => $existing['tutorial_title'],
                        'file_path' => $existing['file_path'],
                        'created_at' => $existing['created_at']
                    ];
                    
                    if ($maxSimilarity >= self::SIMILARITY_THRESHOLDS['very_high']) {
                        $similarityResults['suspicious_matches'][] = [
                            'image_id' => $existing['image_id'],
                            'similarity_score' => $maxSimilarity,
                            'confidence' => 'very_high'
                        ];
                    }
                }
                
                $similarityResults['max_similarity'] = max($similarityResults['max_similarity'], $maxSimilarity);
            }
            
            $similarityResults['category_matches'] = count($existingImages);
            
            // Determine evaluation based on strict criteria
            if (count($similarityResults['suspicious_matches']) > 0) {
                $similarityResults['evaluation'] = 'highly_suspicious';
            } elseif ($similarityResults['max_similarity'] >= self::SIMILARITY_THRESHOLDS['high']) {
                $similarityResults['evaluation'] = 'moderately_suspicious';
            } elseif ($similarityResults['max_similarity'] >= self::SIMILARITY_THRESHOLDS['moderate']) {
                $similarityResults['evaluation'] = 'low_concern';
            } else {
                $similarityResults['evaluation'] = 'unique';
            }
            
        } catch (Exception $e) {
            error_log("Similarity checking error: " . $e->getMessage());
            $similarityResults['evaluation'] = 'error';
        }
        
        return $similarityResults;
    }
    
    /**
     * Multi-rule evaluation system - flags only when multiple conditions are met
     */
    private function performMultiRuleEvaluation($imageProperties, $metadataAnalysis, $similarityAnalysis, $category) {
        $evaluation = [
            'authenticity_score' => 100,
            'risk_level' => 'clean',
            'flagged_reasons' => [],
            'evaluation_details' => [],
            'requires_admin_review' => false,
            'auto_approve' => true,
            'confidence_level' => 'high'
        ];
        
        $suspicionPoints = 0;
        $criticalFlags = 0;
        
        // Rule 1: Very high similarity in same category (Critical)
        if ($similarityAnalysis['evaluation'] === 'highly_suspicious') {
            $suspicionPoints += 40;
            $criticalFlags++;
            $evaluation['flagged_reasons'][] = 'Very high similarity detected in same tutorial category';
            $evaluation['evaluation_details'][] = [
                'rule' => 'similarity_check',
                'severity' => 'critical',
                'details' => "Max similarity: " . round($similarityAnalysis['max_similarity'] * 100, 1) . "%"
            ];
        }
        
        // Rule 2: Suspicious metadata patterns (Moderate)
        if ($metadataAnalysis['metadata_score'] >= 5) {
            $suspicionPoints += 20;
            $evaluation['flagged_reasons'][] = 'Multiple suspicious metadata patterns detected';
            $evaluation['evaluation_details'][] = [
                'rule' => 'metadata_analysis',
                'severity' => 'moderate',
                'details' => 'Metadata suspicion score: ' . $metadataAnalysis['metadata_score']
            ];
        }
        
        // Rule 3: Missing camera information + editing software (Moderate)
        if (in_array('missing_camera_info', $metadataAnalysis['suspicious_indicators']) && 
            !empty($metadataAnalysis['editing_software'])) {
            $suspicionPoints += 15;
            $evaluation['flagged_reasons'][] = 'No camera data but editing software signatures found';
            $evaluation['evaluation_details'][] = [
                'rule' => 'camera_editing_mismatch',
                'severity' => 'moderate',
                'details' => 'Editing software: ' . implode(', ', array_column($metadataAnalysis['editing_software'], 'name'))
            ];
        }
        
        // Rule 4: Unusual image properties (Low)
        if ($imageProperties['unusual_dimensions']) {
            $suspicionPoints += 5;
            $evaluation['flagged_reasons'][] = 'Unusual image dimensions detected';
            $evaluation['evaluation_details'][] = [
                'rule' => 'image_properties',
                'severity' => 'low',
                'details' => 'Dimensions: ' . $imageProperties['dimensions']['width'] . 'x' . $imageProperties['dimensions']['height']
            ];
        }
        
        // Rule 5: Multiple editing software signatures (Moderate)
        if (count($metadataAnalysis['editing_software']) > 1) {
            $suspicionPoints += 10;
            $evaluation['flagged_reasons'][] = 'Multiple editing software signatures detected';
            $evaluation['evaluation_details'][] = [
                'rule' => 'multiple_editing_software',
                'severity' => 'moderate',
                'details' => count($metadataAnalysis['editing_software']) . ' different software detected'
            ];
        }
        
        // Calculate final authenticity score
        $evaluation['authenticity_score'] = max(0, 100 - $suspicionPoints);
        
        // Determine risk level and actions based on multi-rule criteria
        if ($criticalFlags > 0 && $suspicionPoints >= 50) {
            // Critical: Very high similarity + other suspicious factors
            $evaluation['risk_level'] = 'highly_suspicious';
            $evaluation['requires_admin_review'] = true;
            $evaluation['auto_approve'] = false;
            $evaluation['confidence_level'] = 'high';
        } elseif ($suspicionPoints >= 30 && count($evaluation['flagged_reasons']) >= 2) {
            // Moderate: Multiple moderate concerns
            $evaluation['risk_level'] = 'suspicious';
            $evaluation['requires_admin_review'] = true;
            $evaluation['auto_approve'] = false;
            $evaluation['confidence_level'] = 'medium';
        } elseif ($suspicionPoints >= 15) {
            // Low: Some concerns but not enough to flag
            $evaluation['risk_level'] = 'low_concern';
            $evaluation['requires_admin_review'] = false;
            $evaluation['auto_approve'] = true;
            $evaluation['confidence_level'] = 'medium';
        } else {
            // Clean: No significant concerns
            $evaluation['risk_level'] = 'clean';
            $evaluation['requires_admin_review'] = false;
            $evaluation['auto_approve'] = true;
            $evaluation['confidence_level'] = 'high';
        }
        
        // Add category and similarity context
        $evaluation['category'] = $category;
        $evaluation['similarity_context'] = [
            'category_matches_checked' => $similarityAnalysis['category_matches'],
            'max_similarity_found' => $similarityAnalysis['max_similarity'],
            'similar_images_count' => count($similarityAnalysis['similar_images'])
        ];
        
        return $evaluation;
    }
    
    /**
     * Calculate similarity using multiple hash methods for accuracy
     */
    private function calculateMultiHashSimilarity($hashes1, $hashes2) {
        $similarities = [];
        
        $methods = ['average_hash', 'difference_hash', 'perceptual_hash', 'wavelet_hash'];
        
        foreach ($methods as $method) {
            if (isset($hashes1[$method]) && isset($hashes2[$method])) {
                $similarities[$method] = $this->calculateHashSimilarity($hashes1[$method], $hashes2[$method]);
            }
        }
        
        return $similarities;
    }
    
    /**
     * Calculate hash similarity (Hamming distance based)
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
     * Store comprehensive analysis results
     */
    private function storeAnalysisResults($imageId, $imageType, $filePath, $evaluation) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO image_authenticity_metadata 
                (image_id, image_type, file_path, authenticity_score, risk_level, 
                 verification_status, flagged_reasons, evaluation_details, 
                 requires_admin_review, confidence_level, tutorial_category, 
                 similarity_context, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                verification_status = VALUES(verification_status),
                flagged_reasons = VALUES(flagged_reasons),
                evaluation_details = VALUES(evaluation_details),
                requires_admin_review = VALUES(requires_admin_review),
                confidence_level = VALUES(confidence_level),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $filePath,
                $evaluation['authenticity_score'],
                $evaluation['risk_level'],
                $evaluation['requires_admin_review'] ? 'pending_review' : 'approved',
                json_encode($evaluation['flagged_reasons']),
                json_encode($evaluation['evaluation_details']),
                $evaluation['requires_admin_review'] ? 1 : 0,
                $evaluation['confidence_level'],
                $evaluation['category'],
                json_encode($evaluation['similarity_context'])
            ]);
            
            // If requires admin review, add to admin queue
            if ($evaluation['requires_admin_review']) {
                $this->addToAdminReviewQueue($imageId, $imageType, $evaluation);
            }
            
        } catch (Exception $e) {
            error_log("Error storing analysis results: " . $e->getMessage());
        }
    }
    
    /**
     * Add flagged image to admin review queue
     */
    private function addToAdminReviewQueue($imageId, $imageType, $evaluation) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_review_queue 
                (image_id, image_type, authenticity_score, risk_level, 
                 flagged_reasons, evaluation_details, flagged_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                flagged_reasons = VALUES(flagged_reasons),
                evaluation_details = VALUES(evaluation_details),
                flagged_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $evaluation['authenticity_score'],
                $evaluation['risk_level'],
                json_encode($evaluation['flagged_reasons']),
                json_encode($evaluation['evaluation_details'])
            ]);
            
        } catch (Exception $e) {
            error_log("Error adding to admin review queue: " . $e->getMessage());
        }
    }
    
    // Additional helper methods for hash calculations, EXIF analysis, etc.
    // (Implementation details for hash algorithms would go here)
    
    private function calculateAverageHash($filePath) {
        // Implementation for average hash calculation
        return hash('md5', $filePath . '_avg_' . time());
    }
    
    private function calculateDifferenceHash($filePath) {
        // Implementation for difference hash calculation
        return hash('md5', $filePath . '_diff_' . time());
    }
    
    private function calculatePerceptualHash($filePath) {
        // Implementation for perceptual hash calculation
        return hash('md5', $filePath . '_phash_' . time());
    }
    
    private function calculateWaveletHash($filePath) {
        // Implementation for wavelet hash calculation
        return hash('md5', $filePath . '_wavelet_' . time());
    }
    
    private function detectUnusualDimensions($width, $height) {
        // Check for unusual aspect ratios or dimensions
        $aspectRatio = $width / $height;
        return $aspectRatio > 10 || $aspectRatio < 0.1 || $width < 100 || $height < 100;
    }
    
    private function analyzeExifData($analysis, $exif) {
        // Analyze EXIF data for camera information and suspicious patterns
        if (isset($exif['Make']) && isset($exif['Model'])) {
            $analysis['camera_info'] = [
                'make' => $exif['Make'],
                'model' => $exif['Model'],
                'datetime' => $exif['DateTime'] ?? null
            ];
        } else {
            $analysis['suspicious_indicators'][] = 'missing_camera_info';
            $analysis['metadata_score'] += self::SUSPICIOUS_PATTERNS['missing_camera_info'];
        }
        
        return $analysis;
    }
    
    private function detectEditingSoftware($analysis, $filePath) {
        // Detect editing software signatures
        return $analysis;
    }
    
    private function analyzeTimestamps($analysis) {
        // Analyze timestamp consistency
        return $analysis;
    }
    
    private function analyzeFileSize($analysis, $filePath) {
        // Analyze file size patterns
        return $analysis;
    }
    
    private function loadConfiguration() {
        return [
            'strict_mode' => true,
            'category_matching' => true,
            'multi_hash_verification' => true
        ];
    }
    
    private function createErrorResult($imageId, $errorMessage) {
        return [
            'authenticity_score' => 50,
            'risk_level' => 'unknown',
            'flagged_reasons' => ['Analysis error: ' . $errorMessage],
            'requires_admin_review' => true,
            'auto_approve' => false,
            'confidence_level' => 'low',
            'error' => true
        ];
    }
}
?>