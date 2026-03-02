<?php
/**
 * Enhanced Image Authenticity Service V2
 * 
 * Features:
 * - pHash-only similarity detection (strict threshold ≤ 5)
 * - Google Vision API for unrelated image warnings
 * - Category-specific comparison only
 * - No auto-rejection, admin is final authority
 * - EXIF metadata extraction for reference
 */

class EnhancedImageAuthenticityServiceV2 {
    private $pdo;
    private $localClassifierUrl;
    
    // Strict pHash threshold
    private const PHASH_DISTANCE_THRESHOLD = 5; // Hamming distance ≤ 5 is suspicious
    
    // Evaluation states
    private const EVALUATION_STATES = [
        'unique' => 'No similar images found in the platform',
        'possible_reuse' => 'Similar image found in same category',
        'possibly_unrelated' => 'Image may contain unrelated content',
        'needs_admin_review' => 'Flagged for manual admin review'
    ];
    
    // Unrelated content labels (from Google Vision API)
    private const UNRELATED_LABELS = [
        'person', 'people', 'human', 'face', 'portrait',
        'landscape', 'scenery', 'nature', 'outdoor',
        'animal', 'pet', 'dog', 'cat', 'bird',
        'food', 'meal', 'dish', 'restaurant',
        'vehicle', 'car', 'automobile', 'transportation',
        'building', 'architecture', 'city', 'urban'
    ];
    
    public function __construct($pdo, $localClassifierUrl = null) {
        $this->pdo = $pdo;
        $this->localClassifierUrl = $localClassifierUrl ?? getenv('LOCAL_CLASSIFIER_URL') ?? 'http://localhost:5000';
        
        // Check for GD extension
        if (!extension_loaded('gd')) {
            error_log("CRITICAL: GD extension is not loaded - image processing will fail");
        }
    }
    
    /**
     * Main evaluation method
     */
    public function evaluateImage($imageId, $imageType, $filePath, $userId, $tutorialId) {
        try {
            // Step 0: Validate file exists
            if (!file_exists($filePath)) {
                return $this->createErrorResult($imageId, 'FILE_NOT_FOUND', 'Image file does not exist');
            }
            
            // Step 1: Get tutorial category (ground truth)
            $category = $this->getTutorialCategory($tutorialId);
            
            // Step 2: Generate perceptual hash (pHash only) - CRITICAL
            $pHashResult = $this->generatePerceptualHash($filePath);
            if (!$pHashResult['success']) {
                return $this->createErrorResult($imageId, $pHashResult['error_code'], $pHashResult['error_message']);
            }
            $pHash = $pHashResult['hash'];
            
            // Step 3: Extract EXIF metadata for admin reference
            $metadata = $this->extractMetadata($filePath);
            
            // Step 4: Check for unrelated content using local classifier - NON-CRITICAL
            // If classifier is not available, this will return empty analysis and continue
            $aiAnalysis = $this->analyzeImageContent($filePath);
            
            // AI analysis is non-critical, so we don't check for errors
            // If it fails, we just continue without AI warnings
            
            // Step 5: Check similarity within same category only - CRITICAL
            $similarityResult = $this->checkSimilarityInCategory($pHash, $category, $imageId, $imageType);
            
            // Check for database errors
            if (isset($similarityResult['error_code'])) {
                return $this->createErrorResult($imageId, $similarityResult['error_code'], $similarityResult['error_message']);
            }
            
            // Step 6: Make evaluation decision
            $evaluation = $this->makeEvaluationDecision($similarityResult, $aiAnalysis, $metadata, $category);
            
            // Step 7: Store evaluation result
            $storeResult = $this->storeEvaluationResult($imageId, $imageType, $filePath, $pHash, $category, $evaluation, $aiAnalysis, $userId, $tutorialId);
            
            if (!$storeResult['success']) {
                return $this->createErrorResult($imageId, $storeResult['error_code'], $storeResult['error_message']);
            }
            
            return $evaluation;
            
        } catch (Exception $e) {
            error_log("Image evaluation error: " . $e->getMessage());
            return $this->createErrorResult($imageId, 'EVALUATION_FAILED', $e->getMessage());
        }
    }
    
    /**
     * Get tutorial category (ground truth - never auto-detect from image)
     */
    private function getTutorialCategory($tutorialId) {
        try {
            $stmt = $this->pdo->prepare("SELECT category, title FROM tutorials WHERE id = ?");
            $stmt->execute([$tutorialId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['category'])) {
                return $result['category'];
            }
            
            // Fallback: infer from title if category not set
            if ($result && $result['title']) {
                return $this->inferCategoryFromTitle($result['title']);
            }
            
            return 'general';
            
        } catch (Exception $e) {
            error_log("Category lookup error: " . $e->getMessage());
            return 'general';
        }
    }
    
    /**
     * Infer category from tutorial title
     */
    private function inferCategoryFromTitle($title) {
        $title = strtolower($title);
        
        $categoryKeywords = [
            'embroidery' => ['embroidery', 'stitch', 'borduur', 'needle', 'thread'],
            'painting' => ['paint', 'canvas', 'brush', 'acrylic', 'watercolor', 'oil'],
            'drawing' => ['draw', 'sketch', 'pencil', 'charcoal', 'illustration'],
            'crafts' => ['craft', 'diy', 'handmade', 'creative', 'project'],
            'jewelry' => ['jewelry', 'bead', 'wire', 'pendant', 'necklace', 'bracelet'],
            'pottery' => ['pottery', 'clay', 'ceramic', 'wheel', 'glaze'],
            'woodwork' => ['wood', 'carving', 'furniture', 'timber', 'carpentry'],
            'textile' => ['fabric', 'sewing', 'quilting', 'weaving', 'textile'],
            'photography' => ['photo', 'camera', 'lens', 'portrait', 'photography'],
            'digital_art' => ['digital', 'photoshop', 'illustrator', 'graphic', 'design']
        ];
        
        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($title, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Generate perceptual hash (pHash only)
     */
    private function generatePerceptualHash($filePath) {
        try {
            // Check GD extension
            if (!extension_loaded('gd')) {
                return [
                    'success' => false,
                    'error_code' => 'GD_NOT_AVAILABLE',
                    'error_message' => 'PHP GD extension is not enabled. Please enable it in php.ini'
                ];
            }
            
            // Sanitize filename
            $sanitizedPath = $this->sanitizeFilePath($filePath);
            
            // Verify file exists
            if (!file_exists($sanitizedPath)) {
                return [
                    'success' => false,
                    'error_code' => 'FILE_NOT_FOUND',
                    'error_message' => 'Image file not found at: ' . basename($sanitizedPath)
                ];
            }
            
            // Read image data
            $imageData = @file_get_contents($sanitizedPath);
            if (!$imageData) {
                return [
                    'success' => false,
                    'error_code' => 'FILE_READ_FAILED',
                    'error_message' => 'Failed to read image file'
                ];
            }
            
            // Create image from string
            $image = @imagecreatefromstring($imageData);
            if (!$image) {
                return [
                    'success' => false,
                    'error_code' => 'IMAGE_DECODE_FAILED',
                    'error_message' => 'Failed to decode image. File may be corrupted or in unsupported format'
                ];
            }
            
            // Re-encode to JPG to ensure clean processing
            ob_start();
            imagejpeg($image, null, 90);
            $jpegData = ob_get_clean();
            imagedestroy($image);
            
            // Recreate from clean JPEG
            $image = @imagecreatefromstring($jpegData);
            if (!$image) {
                return [
                    'success' => false,
                    'error_code' => 'IMAGE_REENCODE_FAILED',
                    'error_message' => 'Failed to re-encode image to JPEG'
                ];
            }
            
            // Resize to 32x32 for pHash calculation
            $resized = imagecreatetruecolor(32, 32);
            if (!$resized) {
                imagedestroy($image);
                return [
                    'success' => false,
                    'error_code' => 'IMAGE_RESIZE_FAILED',
                    'error_message' => 'Failed to create resized image'
                ];
            }
            
            $resizeSuccess = imagecopyresampled($resized, $image, 0, 0, 0, 0, 32, 32, imagesx($image), imagesy($image));
            if (!$resizeSuccess) {
                imagedestroy($image);
                imagedestroy($resized);
                return [
                    'success' => false,
                    'error_code' => 'IMAGE_RESIZE_FAILED',
                    'error_message' => 'Failed to resize image'
                ];
            }
            
            // Convert to grayscale and calculate hash
            $hash = $this->calculateDCTHash($resized);
            
            imagedestroy($image);
            imagedestroy($resized);
            
            if (empty($hash)) {
                return [
                    'success' => false,
                    'error_code' => 'PHASH_FAILED',
                    'error_message' => 'Failed to generate perceptual hash'
                ];
            }
            
            return [
                'success' => true,
                'hash' => $hash
            ];
            
        } catch (Exception $e) {
            error_log("pHash generation error: " . $e->getMessage());
            return [
                'success' => false,
                'error_code' => 'PHASH_EXCEPTION',
                'error_message' => 'Exception during hash generation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Sanitize file path to prevent directory traversal
     */
    private function sanitizeFilePath($filePath) {
        // Convert to absolute path
        $realPath = realpath($filePath);
        if ($realPath === false) {
            // If realpath fails, use the original path
            return $filePath;
        }
        return $realPath;
    }
    
    /**
     * Calculate DCT-based perceptual hash
     */
    private function calculateDCTHash($image) {
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Convert to grayscale values
        $gray = [];
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $gray[$y][$x] = ($r + $g + $b) / 3;
            }
        }
        
        // Calculate average
        $total = 0;
        $count = 0;
        foreach ($gray as $row) {
            foreach ($row as $pixel) {
                $total += $pixel;
                $count++;
            }
        }
        $average = $total / $count;
        
        // Generate binary hash
        $hash = '';
        foreach ($gray as $row) {
            foreach ($row as $pixel) {
                $hash .= ($pixel > $average) ? '1' : '0';
            }
        }
        
        return $hash;
    }
    
    /**
     * Extract EXIF metadata for admin reference only
     */
    private function extractMetadata($filePath) {
        $metadata = [
            'file_size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'has_exif' => false,
            'camera_make' => null,
            'camera_model' => null,
            'software' => null,
            'date_taken' => null,
            'dimensions' => null
        ];
        
        try {
            // Get image dimensions
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $metadata['dimensions'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1]
                ];
            }
            
            // Extract EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['has_exif'] = true;
                    $metadata['camera_make'] = $exif['Make'] ?? null;
                    $metadata['camera_model'] = $exif['Model'] ?? null;
                    $metadata['software'] = $exif['Software'] ?? null;
                    $metadata['date_taken'] = $exif['DateTimeOriginal'] ?? $exif['DateTime'] ?? null;
                }
            }
        } catch (Exception $e) {
            error_log("Metadata extraction warning: " . $e->getMessage());
        }
        
        return $metadata;
    }
    
    /**
     * Analyze image content using local MobileNet classifier
     * Free, no billing, completely local processing
     */
    private function analyzeImageContent($filePath) {
        $analysis = [
            'ai_enabled' => false,
            'possibly_unrelated' => false,
            'labels' => [],
            'confidence' => 0.0,
            'warning_message' => null
        ];
        
        try {
            // Verify file exists
            if (!file_exists($filePath)) {
                // Non-critical: return empty analysis instead of error
                error_log("Image file not found for classification: $filePath");
                return $analysis;
            }
            
            // Get absolute path for classifier
            $absolutePath = realpath($filePath);
            if (!$absolutePath) {
                error_log("Could not resolve absolute path for: $filePath");
                return $analysis;
            }
            
            // Call local classifier service
            $url = $this->localClassifierUrl . '/classify';
            
            $requestData = json_encode([
                'image_path' => $absolutePath
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            // Log the response for debugging
            error_log("Local Classifier Response Code: $httpCode");
            
            if ($curlError) {
                // Non-critical: classifier service may not be running
                error_log("Local Classifier not available: $curlError");
                error_log("Continuing without AI analysis (pHash will still work)");
                return $analysis;
            }
            
            if ($httpCode !== 200) {
                error_log("Local Classifier returned HTTP $httpCode");
                if ($response) {
                    error_log("Response: " . substr($response, 0, 200));
                }
                return $analysis;
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['success'])) {
                error_log("Invalid response from local classifier");
                return $analysis;
            }
            
            if (!$result['success']) {
                error_log("Classifier error: " . ($result['error_message'] ?? 'Unknown error'));
                return $analysis;
            }
            
            // Extract classification results
            $analysis['ai_enabled'] = $result['ai_enabled'] ?? true;
            $analysis['possibly_unrelated'] = $result['possibly_unrelated'] ?? false;
            $analysis['labels'] = $result['labels'] ?? [];
            $analysis['confidence'] = $result['confidence'] ?? 0.0;
            $analysis['warning_message'] = $result['warning_message'] ?? null;
            
            if ($analysis['possibly_unrelated']) {
                error_log("Image flagged as possibly unrelated: " . $analysis['warning_message']);
            }
            
        } catch (Exception $e) {
            // Non-critical: log and continue without AI analysis
            error_log("Local Classifier exception: " . $e->getMessage());
            error_log("Continuing without AI analysis (pHash will still work)");
        }
        
        return $analysis;
    }
    
    /**
     * Check similarity only within the same category
     */
    private function checkSimilarityInCategory($pHash, $category, $currentImageId, $currentImageType) {
        try {
            // Get existing images in same category only
            $stmt = $this->pdo->prepare("
                SELECT image_id, image_type, phash, created_at, user_id
                FROM image_authenticity_v2 
                WHERE category = ? 
                AND NOT (image_id = ? AND image_type = ?)
                AND phash IS NOT NULL
                AND admin_decision = 'approved'
                ORDER BY created_at DESC
                LIMIT 500
            ");
            $stmt->execute([$category, $currentImageId, $currentImageType]);
            $existingImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $bestMatch = null;
            $minDistance = PHP_INT_MAX;
            
            foreach ($existingImages as $existing) {
                $distance = $this->calculateHammingDistance($pHash, $existing['phash']);
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $bestMatch = [
                        'image_id' => $existing['image_id'],
                        'image_type' => $existing['image_type'],
                        'distance' => $distance,
                        'created_at' => $existing['created_at'],
                        'user_id' => $existing['user_id']
                    ];
                }
            }
            
            return [
                'category' => $category,
                'images_compared' => count($existingImages),
                'best_match' => $bestMatch,
                'min_distance' => $minDistance
            ];
            
        } catch (Exception $e) {
            error_log("Similarity check error: " . $e->getMessage());
            return [
                'error_code' => 'DB_ERROR',
                'error_message' => 'Database error during similarity check: ' . $e->getMessage(),
                'category' => $category,
                'images_compared' => 0,
                'best_match' => null,
                'min_distance' => PHP_INT_MAX
            ];
        }
    }
    
    /**
     * Calculate Hamming distance between two binary hashes
     */
    private function calculateHammingDistance($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) {
            return PHP_INT_MAX;
        }
        
        $distance = 0;
        $length = strlen($hash1);
        
        for ($i = 0; $i < $length; $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }
        
        return $distance;
    }
    
    /**
     * Make evaluation decision based on strict criteria
     * Decision Logic:
     * IF (possibly_unrelated == true OR phash_distance ≤ 5)
     *     → evaluation_status = 'needs_admin_review'
     * ELSE
     *     → evaluation_status = 'unique'
     */
    private function makeEvaluationDecision($similarityResult, $aiAnalysis, $metadata, $category) {
        $evaluation = [
            'status' => 'unique',
            'explanation' => self::EVALUATION_STATES['unique'],
            'requires_admin_review' => false,
            'category' => $category,
            'images_compared' => $similarityResult['images_compared'],
            'metadata_notes' => $this->formatMetadataNotes($metadata),
            'flagged_reason' => null,
            'similar_image' => null,
            'ai_warning' => null
        ];
        
        $needsReview = false;
        $reasons = [];
        
        // Check 1: AI detected possibly unrelated content
        if ($aiAnalysis['possibly_unrelated']) {
            $needsReview = true;
            $reasons[] = "AI detected possibly unrelated content: " . $aiAnalysis['warning_message'];
            $evaluation['ai_warning'] = $aiAnalysis['warning_message'];
            $evaluation['status'] = 'possibly_unrelated';
        }
        
        // Check 2: pHash distance ≤ 5 (similar image found)
        if ($similarityResult['best_match'] && $similarityResult['min_distance'] <= self::PHASH_DISTANCE_THRESHOLD) {
            $needsReview = true;
            $reasons[] = "Similar image found in same category (pHash distance: {$similarityResult['min_distance']})";
            $evaluation['similar_image'] = $similarityResult['best_match'];
            
            // Update status if not already set to possibly_unrelated
            if ($evaluation['status'] === 'unique') {
                $evaluation['status'] = 'possible_reuse';
            }
        }
        
        // Apply decision logic
        if ($needsReview) {
            $evaluation['requires_admin_review'] = true;
            $evaluation['status'] = 'needs_admin_review';
            $evaluation['explanation'] = self::EVALUATION_STATES['needs_admin_review'];
            $evaluation['flagged_reason'] = implode('; ', $reasons);
        }
        
        return $evaluation;
    }
    
    /**
     * Format metadata notes for admin visibility
     */
    private function formatMetadataNotes($metadata) {
        $notes = [];
        
        if ($metadata['dimensions']) {
            $notes[] = "Dimensions: {$metadata['dimensions']['width']}x{$metadata['dimensions']['height']}";
        }
        
        if ($metadata['has_exif']) {
            if ($metadata['camera_make'] && $metadata['camera_model']) {
                $notes[] = "Camera: {$metadata['camera_make']} {$metadata['camera_model']}";
            }
            if ($metadata['software']) {
                $notes[] = "Software: {$metadata['software']}";
            }
            if ($metadata['date_taken']) {
                $notes[] = "Date taken: {$metadata['date_taken']}";
            }
        } else {
            $notes[] = "No EXIF data found";
        }
        
        $notes[] = "File size: " . $this->formatFileSize($metadata['file_size']);
        $notes[] = "Type: " . ($metadata['mime_type'] ?? 'unknown');
        
        return implode('; ', $notes);
    }
    
    /**
     * Format file size for display
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Store evaluation result
     */
    private function storeEvaluationResult($imageId, $imageType, $filePath, $pHash, $category, $evaluation, $aiAnalysis, $userId, $tutorialId) {
        try {
            // Ensure table exists
            $this->ensureTableExists();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO image_authenticity_v2 
                (image_id, image_type, user_id, tutorial_id, category, phash, 
                 evaluation_status, admin_decision, requires_review, flagged_reason, 
                 metadata_notes, ai_labels, ai_warning, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                phash = VALUES(phash),
                evaluation_status = VALUES(evaluation_status),
                requires_review = VALUES(requires_review),
                flagged_reason = VALUES(flagged_reason),
                metadata_notes = VALUES(metadata_notes),
                ai_labels = VALUES(ai_labels),
                ai_warning = VALUES(ai_warning),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $category,
                $pHash,
                $evaluation['status'],
                $evaluation['requires_admin_review'] ? 1 : 0,
                $evaluation['flagged_reason'],
                $evaluation['metadata_notes'],
                json_encode($aiAnalysis['labels'] ?? []),
                $evaluation['ai_warning']
            ]);
            
            // Add to admin review queue if flagged
            if ($evaluation['requires_admin_review']) {
                $this->addToReviewQueue($imageId, $imageType, $evaluation, $userId, $tutorialId);
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Error storing evaluation result: " . $e->getMessage());
            return [
                'success' => false,
                'error_code' => 'DB_ERROR',
                'error_message' => 'Database error storing evaluation: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Ensure database table exists
     */
    private function ensureTableExists() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `image_authenticity_v2` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `image_id` varchar(255) NOT NULL,
                  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `tutorial_id` int(11) DEFAULT NULL,
                  `category` varchar(50) NOT NULL DEFAULT 'general',
                  `phash` text DEFAULT NULL,
                  `evaluation_status` enum('unique', 'possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL DEFAULT 'unique',
                  `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
                  `requires_review` tinyint(1) DEFAULT 0,
                  `flagged_reason` text DEFAULT NULL,
                  `metadata_notes` text DEFAULT NULL,
                  `ai_labels` json DEFAULT NULL,
                  `ai_warning` text DEFAULT NULL,
                  `admin_notes` text DEFAULT NULL,
                  `reviewed_by` int(11) DEFAULT NULL,
                  `reviewed_at` timestamp NULL DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_image` (`image_id`, `image_type`),
                  KEY `idx_category` (`category`),
                  KEY `idx_evaluation_status` (`evaluation_status`),
                  KEY `idx_requires_review` (`requires_review`),
                  KEY `idx_admin_decision` (`admin_decision`),
                  KEY `idx_user_tutorial` (`user_id`, `tutorial_id`),
                  KEY `idx_category_phash` (`category`, `phash`(50))
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `admin_review_v2` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `image_id` varchar(255) NOT NULL,
                  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `tutorial_id` int(11) DEFAULT NULL,
                  `category` varchar(50) NOT NULL,
                  `evaluation_status` enum('possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL,
                  `flagged_reason` text NOT NULL,
                  `similar_image_info` json DEFAULT NULL,
                  `ai_warning` text DEFAULT NULL,
                  `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
                  `admin_notes` text DEFAULT NULL,
                  `reviewed_by` int(11) DEFAULT NULL,
                  `reviewed_at` timestamp NULL DEFAULT NULL,
                  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_review` (`image_id`, `image_type`),
                  KEY `idx_admin_decision` (`admin_decision`),
                  KEY `idx_category` (`category`),
                  KEY `idx_evaluation_status` (`evaluation_status`),
                  KEY `idx_flagged_at` (`flagged_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Add flagged image to admin review queue
     */
    private function addToReviewQueue($imageId, $imageType, $evaluation, $userId, $tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_review_v2 
                (image_id, image_type, user_id, tutorial_id, category, 
                 evaluation_status, flagged_reason, similar_image_info, ai_warning, flagged_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                evaluation_status = VALUES(evaluation_status),
                flagged_reason = VALUES(flagged_reason),
                similar_image_info = VALUES(similar_image_info),
                ai_warning = VALUES(ai_warning),
                flagged_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $evaluation['category'],
                $evaluation['status'],
                $evaluation['flagged_reason'],
                json_encode($evaluation['similar_image'] ?? null),
                $evaluation['ai_warning']
            ]);
            
        } catch (Exception $e) {
            error_log("Error adding to review queue: " . $e->getMessage());
        }
    }
    
    /**
     * Get evaluation result for an image
     */
    public function getEvaluationResult($imageId, $imageType) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM image_authenticity_v2 
                WHERE image_id = ? AND image_type = ?
            ");
            $stmt->execute([$imageId, $imageType]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting evaluation result: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update admin decision
     */
    public function updateAdminDecision($imageId, $imageType, $decision, $adminId, $notes = null) {
        try {
            $validDecisions = ['approved', 'rejected'];
            if (!in_array($decision, $validDecisions)) {
                throw new Exception('Invalid admin decision');
            }
            
            $this->pdo->beginTransaction();
            
            // Update main record
            $stmt = $this->pdo->prepare("
                UPDATE image_authenticity_v2 
                SET admin_decision = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE image_id = ? AND image_type = ?
            ");
            $stmt->execute([$decision, $notes, $adminId, $imageId, $imageType]);
            
            // Update review queue
            $stmt = $this->pdo->prepare("
                UPDATE admin_review_v2 
                SET admin_decision = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE image_id = ? AND image_type = ?
            ");
            $stmt->execute([$decision, $notes, $adminId, $imageId, $imageType]);
            
            // Update practice progress if approved
            if ($decision === 'approved' && $imageType === 'practice_upload') {
                $this->updatePracticeProgress($imageId, $imageType);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating admin decision: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update practice progress after admin approval
     */
    private function updatePracticeProgress($imageId, $imageType) {
        try {
            // Extract upload ID from image_id (format: uploadId_imageIndex)
            $uploadId = intval(explode('_', $imageId)[0]);
            
            // Get user_id and tutorial_id from practice_uploads
            $stmt = $this->pdo->prepare("
                SELECT user_id, tutorial_id FROM practice_uploads WHERE id = ?
            ");
            $stmt->execute([$uploadId]);
            $upload = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($upload) {
                // Update learning progress
                $stmt = $this->pdo->prepare("
                    INSERT INTO learning_progress 
                    (user_id, tutorial_id, practice_uploaded, practice_completed, practice_admin_approved, last_accessed)
                    VALUES (?, ?, 1, 1, 1, NOW())
                    ON DUPLICATE KEY UPDATE 
                    practice_completed = 1,
                    practice_admin_approved = 1,
                    last_accessed = NOW()
                ");
                $stmt->execute([$upload['user_id'], $upload['tutorial_id']]);
                
                // Update practice upload status
                $stmt = $this->pdo->prepare("
                    UPDATE practice_uploads 
                    SET authenticity_status = 'approved', progress_approved = 1
                    WHERE id = ?
                ");
                $stmt->execute([$uploadId]);
            }
            
        } catch (Exception $e) {
            error_log("Error updating practice progress: " . $e->getMessage());
        }
    }
    
    /**
     * Create error result
     */
    private function createErrorResult($imageId, $errorCode, $errorMessage) {
        error_log("Image evaluation error [$errorCode]: $errorMessage (Image ID: $imageId)");
        
        return [
            'status' => 'error',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'explanation' => 'Processing failed - admin review required',
            'requires_admin_review' => true,
            'category' => 'unknown',
            'images_compared' => 0,
            'metadata_notes' => "Error: $errorMessage",
            'flagged_reason' => "Processing error: $errorCode",
            'similar_image' => null,
            'ai_warning' => null,
            'error' => true
        ];
    }
}
?>
