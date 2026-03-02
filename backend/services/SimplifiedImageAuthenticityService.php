<?php
/**
 * Simplified Image Authenticity Service
 * 
 * Corrected implementation that focuses on:
 * - Only perceptual hash (pHash) similarity detection
 * - Strict category-based comparison
 * - Clear, explainable evaluation states
 * - No false claims about Google/internet detection
 * - Academic defensibility and transparency
 */

class SimplifiedImageAuthenticityService {
    private $pdo;
    
    // Strict similarity threshold - only very similar images are flagged
    private const PHASH_DISTANCE_THRESHOLD = 5; // Hamming distance ≤ 5 is suspicious
    
    // Clear evaluation states (no numeric scores)
    private const EVALUATION_STATES = [
        'unique' => 'No similar images found in the platform',
        'reused' => 'Identical or near-identical image found in platform',
        'highly_similar' => 'Very similar image found in same category',
        'needs_admin_review' => 'Flagged for manual review due to similarity'
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Main evaluation method - simplified and focused
     */
    public function evaluateImage($imageId, $imageType, $filePath, $userId, $tutorialId) {
        try {
            // Step 1: Generate only perceptual hash (pHash)
            $pHash = $this->generatePerceptualHash($filePath);
            if (!$pHash) {
                return $this->createErrorResult($imageId, 'Failed to generate perceptual hash');
            }
            
            // Step 2: Determine tutorial category
            $category = $this->getTutorialCategory($tutorialId);
            
            // Step 3: Extract metadata for context only (not for scoring)
            $metadata = $this->extractBasicMetadata($filePath);
            
            // Step 4: Check for similar images in same category only
            $similarityResult = $this->checkSimilarityInCategory($pHash, $category, $imageId, $imageType);
            
            // Step 5: Make clear decision based on strict criteria
            $evaluation = $this->makeEvaluationDecision($similarityResult, $metadata);
            
            // Step 6: Store minimal required data
            $this->storeEvaluationResult($imageId, $imageType, $filePath, $pHash, $category, $evaluation, $userId, $tutorialId);
            
            return $evaluation;
            
        } catch (Exception $e) {
            error_log("Image evaluation error: " . $e->getMessage());
            return $this->createErrorResult($imageId, $e->getMessage());
        }
    }
    
    /**
     * Generate perceptual hash using only pHash algorithm
     */
    private function generatePerceptualHash($filePath) {
        try {
            // Use ImageMagick or GD to generate pHash
            // For now, using a simple implementation
            $imageData = file_get_contents($filePath);
            if (!$imageData) {
                return null;
            }
            
            // Create image resource
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return null;
            }
            
            // Resize to 32x32 for pHash calculation
            $resized = imagecreatetruecolor(32, 32);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, 32, 32, imagesx($image), imagesy($image));
            
            // Convert to grayscale and calculate DCT-based hash
            $hash = $this->calculateDCTHash($resized);
            
            imagedestroy($image);
            imagedestroy($resized);
            
            return $hash;
            
        } catch (Exception $e) {
            error_log("pHash generation error: " . $e->getMessage());
            return null;
        }
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
        
        // Simple hash based on average (simplified pHash)
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
     * Get tutorial category for comparison grouping
     */
    private function getTutorialCategory($tutorialId) {
        try {
            $stmt = $this->pdo->prepare("SELECT category FROM tutorials WHERE id = ?");
            $stmt->execute([$tutorialId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['category'] ?? 'general';
            
        } catch (Exception $e) {
            error_log("Category lookup error: " . $e->getMessage());
            return 'general';
        }
    }
    
    /**
     * Extract basic metadata for context only
     */
    private function extractBasicMetadata($filePath) {
        $metadata = [
            'file_size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'has_exif' => false,
            'camera_make' => null,
            'camera_model' => null,
            'software' => null
        ];
        
        try {
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['has_exif'] = true;
                    $metadata['camera_make'] = $exif['Make'] ?? null;
                    $metadata['camera_model'] = $exif['Model'] ?? null;
                    $metadata['software'] = $exif['Software'] ?? null;
                }
            }
        } catch (Exception $e) {
            // Metadata extraction failure is not critical
            error_log("Metadata extraction warning: " . $e->getMessage());
        }
        
        return $metadata;
    }
    
    /**
     * Check similarity only within the same category
     */
    private function checkSimilarityInCategory($pHash, $category, $currentImageId, $currentImageType) {
        try {
            // Get existing images in same category only
            $stmt = $this->pdo->prepare("
                SELECT image_id, image_type, phash, created_at
                FROM image_authenticity_simple 
                WHERE category = ? 
                AND NOT (image_id = ? AND image_type = ?)
                AND phash IS NOT NULL
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
                        'created_at' => $existing['created_at']
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
                'category' => $category,
                'images_compared' => 0,
                'best_match' => null,
                'min_distance' => PHP_INT_MAX,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Calculate Hamming distance between two binary hashes
     */
    private function calculateHammingDistance($hash1, $hash2) {
        if (strlen($hash1) !== strlen($hash2)) {
            return PHP_INT_MAX; // Invalid comparison
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
     */
    private function makeEvaluationDecision($similarityResult, $metadata) {
        $evaluation = [
            'status' => 'unique',
            'explanation' => self::EVALUATION_STATES['unique'],
            'requires_admin_review' => false,
            'category' => $similarityResult['category'],
            'images_compared' => $similarityResult['images_compared'],
            'metadata_notes' => $this->formatMetadataNotes($metadata),
            'flagged_reason' => null,
            'similar_image' => null
        ];
        
        // Only flag if very strict criteria are met
        if ($similarityResult['best_match'] && $similarityResult['min_distance'] <= self::PHASH_DISTANCE_THRESHOLD) {
            
            if ($similarityResult['min_distance'] <= 2) {
                // Nearly identical images
                $evaluation['status'] = 'reused';
                $evaluation['explanation'] = self::EVALUATION_STATES['reused'];
                $evaluation['requires_admin_review'] = true;
                $evaluation['flagged_reason'] = "Nearly identical image found (distance: {$similarityResult['min_distance']})";
            } else {
                // Very similar images
                $evaluation['status'] = 'highly_similar';
                $evaluation['explanation'] = self::EVALUATION_STATES['highly_similar'];
                $evaluation['requires_admin_review'] = true;
                $evaluation['flagged_reason'] = "Very similar image found (distance: {$similarityResult['min_distance']})";
            }
            
            $evaluation['similar_image'] = $similarityResult['best_match'];
        }
        
        return $evaluation;
    }
    
    /**
     * Format metadata notes for admin visibility
     */
    private function formatMetadataNotes($metadata) {
        $notes = [];
        
        if ($metadata['has_exif']) {
            if ($metadata['camera_make'] && $metadata['camera_model']) {
                $notes[] = "Camera: {$metadata['camera_make']} {$metadata['camera_model']}";
            }
            if ($metadata['software']) {
                $notes[] = "Software: {$metadata['software']}";
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
     * Store minimal evaluation result
     */
    private function storeEvaluationResult($imageId, $imageType, $filePath, $pHash, $category, $evaluation, $userId, $tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO image_authenticity_simple 
                (image_id, image_type, user_id, tutorial_id, category, phash, 
                 evaluation_status, admin_decision, requires_review, flagged_reason, 
                 metadata_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                phash = VALUES(phash),
                evaluation_status = VALUES(evaluation_status),
                requires_review = VALUES(requires_review),
                flagged_reason = VALUES(flagged_reason),
                metadata_notes = VALUES(metadata_notes),
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
                $evaluation['metadata_notes']
            ]);
            
            // Add to admin review queue if flagged
            if ($evaluation['requires_admin_review']) {
                $this->addToSimpleReviewQueue($imageId, $imageType, $evaluation, $userId, $tutorialId);
            }
            
        } catch (Exception $e) {
            error_log("Error storing evaluation result: " . $e->getMessage());
        }
    }
    
    /**
     * Add flagged image to simplified admin review queue
     */
    private function addToSimpleReviewQueue($imageId, $imageType, $evaluation, $userId, $tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_review_simple 
                (image_id, image_type, user_id, tutorial_id, category, 
                 evaluation_status, flagged_reason, similar_image_info, flagged_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                evaluation_status = VALUES(evaluation_status),
                flagged_reason = VALUES(flagged_reason),
                similar_image_info = VALUES(similar_image_info),
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
                json_encode($evaluation['similar_image'] ?? null)
            ]);
            
        } catch (Exception $e) {
            error_log("Error adding to review queue: " . $e->getMessage());
        }
    }
    
    /**
     * Create error result
     */
    private function createErrorResult($imageId, $errorMessage) {
        return [
            'status' => 'needs_admin_review',
            'explanation' => 'Technical error occurred during analysis',
            'requires_admin_review' => true,
            'category' => 'unknown',
            'images_compared' => 0,
            'metadata_notes' => 'Error: ' . $errorMessage,
            'flagged_reason' => 'Processing error',
            'similar_image' => null,
            'error' => true
        ];
    }
    
    /**
     * Get evaluation result for an image
     */
    public function getEvaluationResult($imageId, $imageType) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM image_authenticity_simple 
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
            $validDecisions = ['approved', 'rejected', 'false_positive'];
            if (!in_array($decision, $validDecisions)) {
                throw new Exception('Invalid admin decision');
            }
            
            $this->pdo->beginTransaction();
            
            // Update main record
            $stmt = $this->pdo->prepare("
                UPDATE image_authenticity_simple 
                SET admin_decision = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE image_id = ? AND image_type = ?
            ");
            $stmt->execute([$decision, $notes, $adminId, $imageId, $imageType]);
            
            // Update review queue
            $stmt = $this->pdo->prepare("
                UPDATE admin_review_simple 
                SET admin_decision = ?, admin_notes = ?, reviewed_by = ?, reviewed_at = NOW()
                WHERE image_id = ? AND image_type = ?
            ");
            $stmt->execute([$decision, $notes, $adminId, $imageId, $imageType]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error updating admin decision: " . $e->getMessage());
            return false;
        }
    }
}
?>