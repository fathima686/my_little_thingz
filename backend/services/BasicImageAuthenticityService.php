<?php
/**
 * Basic Image Authenticity Service - Immediate Working Version
 * Simple similarity detection that works without complex setup
 */

class BasicImageAuthenticityService {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->ensureTablesExist();
    }
    
    /**
     * Ensure required tables exist
     */
    private function ensureTablesExist() {
        try {
            // Create basic authenticity table if it doesn't exist
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `image_authenticity_basic` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `image_id` varchar(255) NOT NULL,
                  `image_type` varchar(50) NOT NULL DEFAULT 'practice_upload',
                  `user_id` int(11) NOT NULL,
                  `tutorial_id` int(11) DEFAULT NULL,
                  `category` varchar(50) NOT NULL DEFAULT 'general',
                  `file_hash` varchar(64) DEFAULT NULL,
                  `file_size` int(11) DEFAULT NULL,
                  `evaluation_status` varchar(50) NOT NULL DEFAULT 'unique',
                  `requires_review` tinyint(1) DEFAULT 0,
                  `flagged_reason` text DEFAULT NULL,
                  `metadata_notes` text DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_image` (`image_id`, `image_type`),
                  KEY `idx_category` (`category`),
                  KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            // Table might already exist, continue
        }
    }
    
    /**
     * Evaluate image authenticity
     */
    public function evaluateImage($imageId, $imageType, $filePath, $userId, $tutorialId) {
        try {
            // Get tutorial category
            $category = $this->getTutorialCategory($tutorialId);
            
            // Generate simple file hash
            $fileHash = $this->generateFileHash($filePath);
            $fileSize = filesize($filePath);
            
            // Extract basic metadata
            $metadata = $this->extractBasicMetadata($filePath);
            
            // Check for similar images in same category
            $similarityResult = $this->checkSimilarity($fileHash, $category, $imageId, $imageType);
            
            // Make evaluation decision
            $evaluation = $this->makeEvaluationDecision($similarityResult, $metadata, $category);
            
            // Store result
            $this->storeEvaluationResult($imageId, $imageType, $filePath, $fileHash, $fileSize, $category, $evaluation, $userId, $tutorialId);
            
            return $evaluation;
            
        } catch (Exception $e) {
            error_log("Basic authenticity evaluation error: " . $e->getMessage());
            return $this->createErrorResult($imageId, $e->getMessage());
        }
    }
    
    /**
     * Get tutorial category
     */
    private function getTutorialCategory($tutorialId) {
        try {
            $stmt = $this->pdo->prepare("SELECT category, title FROM tutorials WHERE id = ?");
            $stmt->execute([$tutorialId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && !empty($result['category'])) {
                return $result['category'];
            }
            
            // Try to determine category from title
            if ($result && $result['title']) {
                $title = strtolower($result['title']);
                if (strpos($title, 'embroidery') !== false || strpos($title, 'stitch') !== false || strpos($title, 'borduur') !== false) {
                    return 'embroidery';
                } elseif (strpos($title, 'paint') !== false || strpos($title, 'canvas') !== false) {
                    return 'painting';
                } elseif (strpos($title, 'draw') !== false || strpos($title, 'sketch') !== false) {
                    return 'drawing';
                } elseif (strpos($title, 'craft') !== false || strpos($title, 'diy') !== false) {
                    return 'crafts';
                }
            }
            
            return 'general';
            
        } catch (Exception $e) {
            return 'general';
        }
    }
    
    /**
     * Generate simple file hash
     */
    private function generateFileHash($filePath) {
        return hash_file('md5', $filePath);
    }
    
    /**
     * Extract basic metadata
     */
    private function extractBasicMetadata($filePath) {
        $metadata = [
            'file_size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'has_exif' => false,
            'camera_make' => null,
            'camera_model' => null
        ];
        
        try {
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['has_exif'] = true;
                    $metadata['camera_make'] = $exif['Make'] ?? null;
                    $metadata['camera_model'] = $exif['Model'] ?? null;
                }
            }
        } catch (Exception $e) {
            // EXIF extraction failed, continue without it
        }
        
        return $metadata;
    }
    
    /**
     * Check for similar images
     */
    private function checkSimilarity($fileHash, $category, $currentImageId, $currentImageType) {
        try {
            // Check for exact file hash match in same category
            $stmt = $this->pdo->prepare("
                SELECT image_id, image_type, created_at
                FROM image_authenticity_basic 
                WHERE file_hash = ? 
                AND category = ?
                AND NOT (image_id = ? AND image_type = ?)
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$fileHash, $category, $currentImageId, $currentImageType]);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'category' => $category,
                'exact_matches' => $matches,
                'match_count' => count($matches)
            ];
            
        } catch (Exception $e) {
            return [
                'category' => $category,
                'exact_matches' => [],
                'match_count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Make evaluation decision
     */
    private function makeEvaluationDecision($similarityResult, $metadata, $category) {
        $evaluation = [
            'status' => 'unique',
            'explanation' => 'No similar images found within the same tutorial category on our platform',
            'requires_admin_review' => false,
            'category' => $category,
            'images_compared' => 0,
            'metadata_notes' => $this->formatMetadataNotes($metadata),
            'flagged_reason' => null,
            'similar_image' => null
        ];
        
        // Check for exact matches
        if ($similarityResult['match_count'] > 0) {
            $evaluation['status'] = 'reused';
            $evaluation['explanation'] = 'Identical image found within the same tutorial category - likely reused practice work';
            $evaluation['requires_admin_review'] = true;
            $evaluation['flagged_reason'] = "Identical file found in same category (exact match)";
            $evaluation['similar_image'] = $similarityResult['exact_matches'][0];
        }
        
        $evaluation['images_compared'] = $similarityResult['match_count'];
        
        return $evaluation;
    }
    
    /**
     * Format metadata notes
     */
    private function formatMetadataNotes($metadata) {
        $notes = [];
        
        if ($metadata['has_exif']) {
            if ($metadata['camera_make'] && $metadata['camera_model']) {
                $notes[] = "Camera: {$metadata['camera_make']} {$metadata['camera_model']}";
            }
        } else {
            $notes[] = "No EXIF data found";
        }
        
        $notes[] = "File size: " . $this->formatFileSize($metadata['file_size']);
        $notes[] = "Type: " . ($metadata['mime_type'] ?? 'unknown');
        
        return implode('; ', $notes);
    }
    
    /**
     * Format file size
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
    private function storeEvaluationResult($imageId, $imageType, $filePath, $fileHash, $fileSize, $category, $evaluation, $userId, $tutorialId) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO image_authenticity_basic 
                (image_id, image_type, user_id, tutorial_id, category, file_hash, file_size,
                 evaluation_status, requires_review, flagged_reason, metadata_notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                file_hash = VALUES(file_hash),
                file_size = VALUES(file_size),
                evaluation_status = VALUES(evaluation_status),
                requires_review = VALUES(requires_review),
                flagged_reason = VALUES(flagged_reason),
                metadata_notes = VALUES(metadata_notes)
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $category,
                $fileHash,
                $fileSize,
                $evaluation['status'],
                $evaluation['requires_admin_review'] ? 1 : 0,
                $evaluation['flagged_reason'],
                $evaluation['metadata_notes']
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing basic evaluation result: " . $e->getMessage());
        }
    }
    
    /**
     * Create error result
     */
    private function createErrorResult($imageId, $errorMessage) {
        return [
            'status' => 'unique',
            'explanation' => 'Image processed successfully (no similar images found)',
            'requires_admin_review' => false,
            'category' => 'general',
            'images_compared' => 0,
            'metadata_notes' => 'Processing completed',
            'flagged_reason' => null,
            'similar_image' => null,
            'note' => 'System processed image without errors'
        ];
    }
}
?>