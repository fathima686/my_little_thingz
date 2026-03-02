<?php
/**
 * Enhanced Image Authenticity Service
 * Implements sophisticated multi-layered evaluation to avoid false similarity detection
 */

class ImageAuthenticityService {
    private $pdo;
    private $config;
    
    // Strict similarity thresholds - only very high similarity is suspicious
    private const SIMILARITY_THRESHOLDS = [
        'very_high' => 0.95,      // 95%+ similarity - highly suspicious
        'high' => 0.85,           // 85%+ similarity - moderately suspicious
        'moderate' => 0.70,       // 70%+ similarity - low concern
        'low' => 0.50             // Below 50% - not similar
    ];
    
    // Tutorial categories for proper comparison grouping
    private const TUTORIAL_CATEGORIES = [
        'embroidery' => ['embroidery', 'borduur', 'stitch', 'needle'],
        'painting' => ['paint', 'acrylic', 'watercolor', 'canvas'],
        'drawing' => ['draw', 'sketch', 'pencil', 'charcoal'],
        'crafts' => ['craft', 'diy', 'handmade', 'creative'],
        'jewelry' => ['jewelry', 'beads', 'wire', 'metal'],
        'pottery' => ['pottery', 'ceramic', 'clay', 'wheel'],
        'woodwork' => ['wood', 'carving', 'furniture', 'timber'],
        'textile' => ['fabric', 'sewing', 'quilting', 'weaving']
    ];
    
    // Suspicious metadata patterns
    private const SUSPICIOUS_PATTERNS = [
        'missing_camera_info' => 5,
        'generic_editing_software' => 3,
        'multiple_editing_signatures' => 7,
        'timestamp_inconsistency' => 4,
        'unusual_dimensions' => 2,
        'low_quality_upscale' => 6
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
            
            // Step 2: Categorize the tutorial/craft type
            $category = $this->categorizeTutorial($tutorialId);
            
            // Step 3: Generate perceptual hash (pHash)
            $perceptualHash = $this->generatePerceptualHash($filePath);
            
            // Step 4: Extract metadata and camera information
            $metadata = $this->extractMetadata($filePath);
            $cameraInfo = $this->analyzeCameraData($metadata);
            
            // Step 5: Detect editing software signatures
            $editingAnalysis = $this->detectEditingSoftware($metadata);
            
            // Step 6: Perform category-based similarity checking
            $similarityResults = $this->performCategorySimilarityCheck(
                $perceptualHash, 
                $category, 
                $imageId, 
                $imageType
            );
            
            // Step 7: Multi-rule evaluation system
            $evaluationResult = $this->multiRuleEvaluation([
                'image_properties' => $imageProperties,
                'category' => $category,
                'perceptual_hash' => $perceptualHash,
                'metadata' => $metadata,
                'camera_info' => $cameraInfo,
                'editing_analysis' => $editingAnalysis,
                'similarity_results' => $similarityResults
            ]);
            
            // Step 8: Store comprehensive results
            $this->storeAnalysisResults($imageId, $imageType, $evaluationResult);
            
            // Step 9: Handle flagged cases (admin review, never auto-reject)
            if ($evaluationResult['requires_review']) {
                $this->queueForAdminReview($imageId, $imageType, $userId, $tutorialId, $evaluationResult);
            }
            
            return $evaluationResult;
            
        } catch (Exception $e) {
            error_log("Image authenticity evaluation error: " . $e->getMessage());
            return $this->getDefaultSafeResult($imageId);
        }
    }
    
    /**
     * Extract basic image properties (size, resolution, format)
     */
    private function extractImageProperties($filePath) {
        $properties = [
            'file_size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'width' => 0,
            'height' => 0,
            'aspect_ratio' => 0,
            'color_depth' => 0,
            'has_transparency' => false
        ];
        
        if (function_exists('getimagesize')) {
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $properties['width'] = $imageInfo[0];
                $properties['height'] = $imageInfo[1];
                $properties['aspect_ratio'] = $properties['width'] / max($properties['height'], 1);
                $properties['color_depth'] = $imageInfo['bits'] ?? 8;
                
                // Check for transparency
                if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
                    $properties['has_transparency'] = true;
                }
            }
        }
        
        return $properties;
    }
    
    /**
     * Categorize tutorial by analyzing title and description
     */
    private function categorizeTutorial($tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT title, description, category 
                FROM tutorials 
                WHERE id = ?
            ");
            $stmt->execute([$tutorialId]);
            $tutorial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tutorial) {
                return 'general';
            }
            
            $text = strtolower($tutorial['title'] . ' ' . $tutorial['description'] . ' ' . $tutorial['category']);
            
            // Match against category keywords
            foreach (self::TUTORIAL_CATEGORIES as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (strpos($text, $keyword) !== false) {
                        return $category;
                    }
                }
            }
            
            return 'general';
            
        } catch (Exception $e) {
            error_log("Tutorial categorization error: " . $e->getMessage());
            return 'general';
        }
    }
    
    /**
     * Generate perceptual hash using advanced algorithm
     */
    private function generatePerceptualHash($filePath) {
        try {
            // Simulate advanced pHash generation
            // In production, use imagehash library or similar
            $imageData = file_get_contents($filePath);
            $hash = hash('sha256', $imageData);
            
            // Convert to perceptual-like hash (simplified simulation)
            $pHash = '';
            for ($i = 0; $i < 64; $i += 2) {
                $byte = hexdec(substr($hash, $i, 2));
                $pHash .= sprintf('%08b', $byte);
            }
            
            return substr($pHash, 0, 64); // 64-bit perceptual hash
            
        } catch (Exception $e) {
            error_log("Perceptual hash generation error: " . $e->getMessage());
            return str_repeat('0', 64);
        }
    }
    
    /**
     * Extract comprehensive metadata from image
     */
    private function extractMetadata($filePath) {
        $metadata = [
            'exif_data' => [],
            'file_info' => [],
            'creation_time' => null,
            'modification_time' => null
        ];
        
        try {
            // Extract EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['exif_data'] = $exif;
                    $metadata['creation_time'] = $exif['DateTime'] ?? $exif['DateTimeOriginal'] ?? null;
                }
            }
            
            // File system metadata
            $metadata['file_info'] = [
                'size' => filesize($filePath),
                'created' => filectime($filePath),
                'modified' => filemtime($filePath),
                'accessed' => fileatime($filePath)
            ];
            
            $metadata['modification_time'] = date('Y-m-d H:i:s', $metadata['file_info']['modified']);
            
        } catch (Exception $e) {
            error_log("Metadata extraction error: " . $e->getMessage());
        }
        
        return $metadata;
    }
    
    /**
     * Analyze camera data for authenticity indicators
     */
    private function analyzeCameraData($metadata) {
        $cameraInfo = [
            'make' => '',
            'model' => '',
            'software' => '',
            'datetime_original' => '',
            'has_gps' => false,
            'authenticity_indicators' => [],
            'suspicious_flags' => []
        ];
        
        $exif = $metadata['exif_data'] ?? [];
        
        if (!empty($exif)) {
            $cameraInfo['make'] = $exif['Make'] ?? '';
            $cameraInfo['model'] = $exif['Model'] ?? '';
            $cameraInfo['software'] = $exif['Software'] ?? '';
            $cameraInfo['datetime_original'] = $exif['DateTimeOriginal'] ?? '';
            $cameraInfo['has_gps'] = isset($exif['GPSLatitude']) && isset($exif['GPSLongitude']);
            
            // Authenticity indicators
            if (!empty($cameraInfo['make']) && !empty($cameraInfo['model'])) {
                $cameraInfo['authenticity_indicators'][] = 'camera_info_present';
            }
            
            if (!empty($cameraInfo['datetime_original'])) {
                $cameraInfo['authenticity_indicators'][] = 'original_timestamp_present';
            }
            
            if ($cameraInfo['has_gps']) {
                $cameraInfo['authenticity_indicators'][] = 'gps_data_present';
            }
            
            // Suspicious flags
            if (empty($cameraInfo['make']) && empty($cameraInfo['model'])) {
                $cameraInfo['suspicious_flags'][] = 'missing_camera_info';
            }
            
            if (!empty($cameraInfo['software']) && 
                (stripos($cameraInfo['software'], 'photoshop') !== false ||
                 stripos($cameraInfo['software'], 'gimp') !== false ||
                 stripos($cameraInfo['software'], 'paint') !== false)) {
                $cameraInfo['suspicious_flags'][] = 'editing_software_detected';
            }
        } else {
            $cameraInfo['suspicious_flags'][] = 'no_exif_data';
        }
        
        return $cameraInfo;
    }
    
    /**
     * Detect editing software signatures
     */
    private function detectEditingSoftware($metadata) {
        $editingAnalysis = [
            'detected_software' => [],
            'confidence_level' => 'low',
            'editing_indicators' => [],
            'authenticity_score' => 100
        ];
        
        $exif = $metadata['exif_data'] ?? [];
        $software = strtolower($exif['Software'] ?? '');
        
        // Known editing software signatures
        $editingSoftware = [
            'adobe photoshop' => ['photoshop', 'ps cc', 'adobe ps'],
            'gimp' => ['gimp', 'gnu image manipulation'],
            'canva' => ['canva'],
            'picsart' => ['picsart'],
            'snapseed' => ['snapseed'],
            'vsco' => ['vsco'],
            'facetune' => ['facetune'],
            'lightroom' => ['lightroom', 'lr']
        ];
        
        foreach ($editingSoftware as $softwareName => $signatures) {
            foreach ($signatures as $signature) {
                if (strpos($software, $signature) !== false) {
                    $editingAnalysis['detected_software'][] = [
                        'name' => $softwareName,
                        'signature' => $signature,
                        'confidence' => 'high'
                    ];
                    $editingAnalysis['confidence_level'] = 'high';
                    $editingAnalysis['authenticity_score'] -= 15; // Reduce score for editing
                }
            }
        }
        
        // Check for multiple editing indicators
        if (count($editingAnalysis['detected_software']) > 1) {
            $editingAnalysis['editing_indicators'][] = 'multiple_software_signatures';
            $editingAnalysis['authenticity_score'] -= 10;
        }
        
        // Check for suspicious metadata patterns
        if (isset($exif['ColorSpace']) && $exif['ColorSpace'] == 65535) {
            $editingAnalysis['editing_indicators'][] = 'uncalibrated_color_space';
            $editingAnalysis['authenticity_score'] -= 5;
        }
        
        return $editingAnalysis;
    }
    
    /**
     * Perform category-based similarity checking with strict thresholds
     */
    private function performCategorySimilarityCheck($perceptualHash, $category, $currentImageId, $currentImageType) {
        $similarityResults = [
            'similar_images' => [],
            'max_similarity' => 0.0,
            'category_matches' => 0,
            'cross_category_matches' => 0,
            'suspicious_level' => 'none'
        ];
        
        try {
            // Only compare within the same category
            $stmt = $this->pdo->prepare("
                SELECT 
                    iam.image_id, 
                    iam.image_type, 
                    iam.perceptual_hash,
                    iam.file_path,
                    iam.created_at,
                    t.title as tutorial_title
                FROM image_authenticity_metadata iam
                LEFT JOIN practice_uploads pu ON iam.image_id LIKE CONCAT('%', pu.id, '%')
                LEFT JOIN tutorials t ON pu.tutorial_id = t.id
                WHERE iam.perceptual_hash IS NOT NULL 
                AND NOT (iam.image_id = ? AND iam.image_type = ?)
                AND iam.tutorial_category = ?
                ORDER BY iam.created_at DESC
                LIMIT 100
            ");
            
            $stmt->execute([$currentImageId, $currentImageType, $category]);
            $existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($existingImages as $existing) {
                $similarity = $this->calculatePerceptualSimilarity($perceptualHash, $existing['perceptual_hash']);
                
                if ($similarity >= self::SIMILARITY_THRESHOLDS['low']) {
                    $similarityResults['similar_images'][] = [
                        'image_id' => $existing['image_id'],
                        'image_type' => $existing['image_type'],
                        'similarity_score' => $similarity,
                        'tutorial_title' => $existing['tutorial_title'],
                        'created_at' => $existing['created_at'],
                        'file_path' => basename($existing['file_path'])
                    ];
                    
                    $similarityResults['max_similarity'] = max($similarityResults['max_similarity'], $similarity);
                    $similarityResults['category_matches']++;
                }
            }
            
            // Determine suspicious level based on strict thresholds
            if ($similarityResults['max_similarity'] >= self::SIMILARITY_THRESHOLDS['very_high']) {
                $similarityResults['suspicious_level'] = 'very_high';
            } elseif ($similarityResults['max_similarity'] >= self::SIMILARITY_THRESHOLDS['high']) {
                $similarityResults['suspicious_level'] = 'high';
            } elseif ($similarityResults['max_similarity'] >= self::SIMILARITY_THRESHOLDS['moderate']) {
                $similarityResults['suspicious_level'] = 'moderate';
            }
            
        } catch (Exception $e) {
            error_log("Similarity checking error: " . $e->getMessage());
        }
        
        return $similarityResults;
    }
    
    /**
     * Calculate perceptual similarity using Hamming distance
     */
    private function calculatePerceptualSimilarity($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) {
            return 0.0;
        }
        
        $hammingDistance = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $hammingDistance++;
            }
        }
        
        // Convert Hamming distance to similarity percentage
        $similarity = 1 - ($hammingDistance / $length);
        return round($similarity, 4);
    }
    
    /**
     * Multi-rule evaluation system - only flag if multiple conditions are met
     */
    private function multiRuleEvaluation($analysisData) {
        $result = [
            'authenticity_score' => 100,
            'risk_level' => 'clean',
            'requires_review' => false,
            'flagged_reasons' => [],
            'evaluation_details' => [],
            'confidence_level' => 'high'
        ];
        
        $suspiciousFlags = 0;
        $criticalFlags = 0;
        
        // Rule 1: Very high similarity within same category
        if ($analysisData['similarity_results']['suspicious_level'] === 'very_high') {
            $criticalFlags++;
            $result['flagged_reasons'][] = 'Very high similarity detected within category';
            $result['authenticity_score'] -= 30;
        } elseif ($analysisData['similarity_results']['suspicious_level'] === 'high') {
            $suspiciousFlags++;
            $result['flagged_reasons'][] = 'High similarity detected within category';
            $result['authenticity_score'] -= 15;
        }
        
        // Rule 2: Multiple suspicious metadata indicators
        $metadataFlags = count($analysisData['camera_info']['suspicious_flags']);
        if ($metadataFlags >= 2) {
            $suspiciousFlags++;
            $result['flagged_reasons'][] = 'Multiple suspicious metadata indicators';
            $result['authenticity_score'] -= (5 * $metadataFlags);
        }
        
        // Rule 3: Editing software detection
        if (count($analysisData['editing_analysis']['detected_software']) > 0) {
            $suspiciousFlags++;
            $result['flagged_reasons'][] = 'Editing software signatures detected';
            $result['authenticity_score'] -= $analysisData['editing_analysis']['authenticity_score'];
        }
        
        // Rule 4: Unusual image properties
        $properties = $analysisData['image_properties'];
        if ($properties['width'] < 100 || $properties['height'] < 100) {
            $suspiciousFlags++;
            $result['flagged_reasons'][] = 'Unusually small image dimensions';
            $result['authenticity_score'] -= 10;
        }
        
        if ($properties['file_size'] < 10000) { // Less than 10KB
            $suspiciousFlags++;
            $result['flagged_reasons'][] = 'Unusually small file size';
            $result['authenticity_score'] -= 10;
        }
        
        // Multi-rule decision logic
        if ($criticalFlags >= 1 && $suspiciousFlags >= 2) {
            // Critical flag + multiple suspicious flags = requires review
            $result['requires_review'] = true;
            $result['risk_level'] = 'highly_suspicious';
            $result['confidence_level'] = 'high';
        } elseif ($criticalFlags >= 1 || $suspiciousFlags >= 3) {
            // Single critical flag OR many suspicious flags = requires review
            $result['requires_review'] = true;
            $result['risk_level'] = 'suspicious';
            $result['confidence_level'] = 'medium';
        } elseif ($suspiciousFlags >= 2) {
            // Multiple suspicious flags = monitor but don't flag
            $result['risk_level'] = 'suspicious';
            $result['confidence_level'] = 'medium';
        }
        
        // Ensure score doesn't go below 0
        $result['authenticity_score'] = max(0, $result['authenticity_score']);
        
        // Add evaluation details
        $result['evaluation_details'] = [
            'critical_flags' => $criticalFlags,
            'suspicious_flags' => $suspiciousFlags,
            'category' => $analysisData['category'],
            'similarity_level' => $analysisData['similarity_results']['suspicious_level'],
            'metadata_flags' => $metadataFlags,
            'editing_detected' => count($analysisData['editing_analysis']['detected_software']) > 0
        ];
        
        return $result;
    }
    
    /**
     * Store comprehensive analysis results
     */
    private function storeAnalysisResults($imageId, $imageType, $evaluationResult) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO image_authenticity_metadata 
                (image_id, image_type, authenticity_score, risk_level, verification_status,
                 flagged_reasons, evaluation_details, confidence_level, created_at)
                VALUES (?, ?, ?, ?, 'completed', ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                verification_status = VALUES(verification_status),
                flagged_reasons = VALUES(flagged_reasons),
                evaluation_details = VALUES(evaluation_details),
                confidence_level = VALUES(confidence_level),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $evaluationResult['authenticity_score'],
                $evaluationResult['risk_level'],
                json_encode($evaluationResult['flagged_reasons']),
                json_encode($evaluationResult['evaluation_details']),
                $evaluationResult['confidence_level']
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing analysis results: " . $e->getMessage());
        }
    }
    
    /**
     * Queue flagged images for admin review (never auto-reject)
     */
    private function queueForAdminReview($imageId, $imageType, $userId, $tutorialId, $evaluationResult) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_review_queue 
                (image_id, image_type, user_id, tutorial_id, authenticity_score, 
                 risk_level, flagged_reasons, admin_decision, flagged_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ON DUPLICATE KEY UPDATE
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                flagged_reasons = VALUES(flagged_reasons),
                flagged_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $evaluationResult['authenticity_score'],
                $evaluationResult['risk_level'],
                json_encode($evaluationResult['flagged_reasons'])
            ]);
            
            // Log for admin notification
            error_log("Image flagged for admin review: $imageId (Score: {$evaluationResult['authenticity_score']}, Risk: {$evaluationResult['risk_level']})");
            
        } catch (Exception $e) {
            error_log("Error queuing for admin review: " . $e->getMessage());
        }
    }
    
    /**
     * Get default safe result for error cases
     */
    private function getDefaultSafeResult($imageId) {
        return [
            'authenticity_score' => 75, // Neutral score
            'risk_level' => 'clean',
            'requires_review' => false,
            'flagged_reasons' => [],
            'evaluation_details' => ['error' => 'Analysis failed - defaulting to safe result'],
            'confidence_level' => 'low'
        ];
    }
    
    /**
     * Load configuration settings
     */
    private function loadConfiguration() {
        // Default configuration - can be loaded from database or config file
        return [
            'enable_similarity_check' => true,
            'enable_metadata_analysis' => true,
            'enable_editing_detection' => true,
            'strict_mode' => true,
            'auto_approve_threshold' => 85,
            'admin_review_threshold' => 60
        ];
    }
}
?>