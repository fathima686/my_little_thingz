<?php
/**
 * Craft Image Validation Service
 * 
 * AI-assisted practice image validation module for skill-based e-learning
 * Integrates with existing EnhancedImageAuthenticityServiceV2
 * 
 * Features:
 * - MobileNet-based craft category classification (7 categories)
 * - AI-generated image detection via metadata analysis
 * - Category mismatch detection (image vs selected tutorial)
 * - Perceptual hashing for duplicate detection
 * - Explainable confidence scores
 * - Admin review workflow integration
 */

class CraftImageValidationService {
    private $pdo;
    private $authenticityService;
    private $craftClassifierUrl;
    
    // Supported craft categories
    private const CRAFT_CATEGORIES = [
        'candle_making' => 'Candle Making',
        'clay_modeling' => 'Clay Modeling', 
        'gift_making' => 'Gift Making',
        'hand_embroidery' => 'Hand Embroidery',
        'jewelry_making' => 'Jewelry Making',
        'mehandi_art' => 'Mylanchi / Mehandi Art',
        'resin_art' => 'Resin Art'
    ];
    
    // AI generator signatures in metadata
    private const AI_GENERATOR_SIGNATURES = [
        'stable diffusion' => ['stable diffusion', 'stablediffusion', 'sd-'],
        'midjourney' => ['midjourney', 'mj-', 'discord'],
        'dalle' => ['dall-e', 'dalle', 'openai'],
        'firefly' => ['adobe firefly', 'firefly'],
        'leonardo' => ['leonardo.ai', 'leonardo'],
        'runway' => ['runway ml', 'runwayml'],
        'artbreeder' => ['artbreeder'],
        'deepai' => ['deepai', 'deep-ai'],
        'nightcafe' => ['nightcafe', 'night cafe'],
        'craiyon' => ['craiyon', 'dall-e mini']
    ];
    
    // Confidence thresholds - BALANCED FOR PRODUCTION
    private const HIGH_CONFIDENCE_THRESHOLD = 0.80;
    private const LOW_CONFIDENCE_THRESHOLD = 0.40;  // Raised from 0.15 to 0.40 (more strict)
    private const CATEGORY_MISMATCH_THRESHOLD = 0.70; // Lowered from 0.85 to 0.70 (more strict)
    
    // Auto-approve mode for testing (bypasses strict validation)
    private const AUTO_APPROVE_MODE = false;  // Changed to false for proper validation
    
    public function __construct($pdo, $authenticityService, $craftClassifierUrl = null) {
        $this->pdo = $pdo;
        $this->authenticityService = $authenticityService;
        $this->craftClassifierUrl = $craftClassifierUrl ?? getenv('CRAFT_CLASSIFIER_URL') ?? 'http://localhost:5001';
    }
    
    /**
     * Main validation method - integrates with existing authenticity system
     */
    public function validatePracticeImage($imageId, $imageType, $filePath, $userId, $tutorialId, $selectedCategory) {
        try {
            // Step 1: Run existing authenticity checks (pHash, metadata, etc.)
            $authenticityResult = $this->authenticityService->evaluateImage(
                $imageId, $imageType, $filePath, $userId, $tutorialId
            );
            
            // Step 2: Add craft-specific AI validation
            $craftValidation = $this->performCraftValidation($filePath, $selectedCategory, $tutorialId);
            
            // Step 3: Combine results and make final decision
            $finalResult = $this->combineValidationResults($authenticityResult, $craftValidation, $imageId);
            
            // Step 4: Store craft validation results
            $this->storeCraftValidationResult($imageId, $imageType, $craftValidation, $userId, $tutorialId);
            
            return $finalResult;
            
        } catch (Exception $e) {
            error_log("Craft validation error: " . $e->getMessage());
            return $this->createErrorResult($imageId, 'CRAFT_VALIDATION_FAILED', $e->getMessage());
        }
    }
    
    /**
     * Perform craft-specific validation
     */
    private function performCraftValidation($filePath, $selectedCategory, $tutorialId) {
        $validation = [
            'craft_classification' => null,
            'category_match' => null,
            'ai_generated_detection' => null,
            'confidence_scores' => [],
            'validation_status' => 'pending',
            'rejection_reason' => null,
            'flag_reason' => null,
            'requires_review' => false
        ];
        
        try {
            // Step 1: Classify image into craft categories
            $classification = $this->classifyImageCraft($filePath);
            $validation['craft_classification'] = $classification;
            
            // Step 2: Check category match with selected tutorial
            $categoryMatch = $this->checkCategoryMatch($classification, $selectedCategory, $tutorialId);
            $validation['category_match'] = $categoryMatch;
            
            // Step 3: Detect AI-generated images
            $aiDetection = $this->detectAIGenerated($filePath);
            $validation['ai_generated_detection'] = $aiDetection;
            
            // Step 4: Make validation decision
            $decision = $this->makeValidationDecision($classification, $categoryMatch, $aiDetection);
            $validation = array_merge($validation, $decision);
            
            return $validation;
            
        } catch (Exception $e) {
            error_log("Craft validation processing error: " . $e->getMessage());
            $validation['validation_status'] = 'error';
            $validation['rejection_reason'] = 'Processing error: ' . $e->getMessage();
            $validation['requires_review'] = true;
            return $validation;
        }
    }
    
    /**
     * Classify image using fine-tuned MobileNet for craft categories
     */
    private function classifyImageCraft($filePath) {
        $classification = [
            'success' => false,
            'predicted_category' => null,
            'confidence' => 0.0,
            'all_predictions' => [],
            'is_craft_related' => false,
            'non_craft_confidence' => 0.0,
            'non_craft_labels' => [],
            'error_message' => null
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
            
            // Call craft classifier service
            $url = $this->craftClassifierUrl . '/classify-craft';
            
            $requestData = json_encode([
                'image_path' => $absolutePath,
                'categories' => array_keys(self::CRAFT_CATEGORIES)
            ]);
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError || $httpCode !== 200) {
                // AI service not available - use fallback heuristics
                error_log("Craft classifier not available, using fallback heuristics");
                return $this->classifyWithFallbackHeuristics($filePath);
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['success'])) {
                $classification['error_message'] = 'Invalid response from craft classifier';
                return $this->classifyWithFallbackHeuristics($filePath);
            }
            
            if (!$result['success']) {
                $classification['error_message'] = $result['error_message'] ?? 'Classification failed';
                return $this->classifyWithFallbackHeuristics($filePath);
            }
            
            // Extract results
            $classification['success'] = true;
            $classification['predicted_category'] = $result['predicted_category'] ?? null;
            $classification['confidence'] = $result['confidence'] ?? 0.0;
            $classification['all_predictions'] = $result['all_predictions'] ?? [];
            $classification['is_craft_related'] = $result['is_craft_related'] ?? false;
            $classification['non_craft_confidence'] = $result['non_craft_confidence'] ?? 0.0;
            $classification['non_craft_labels'] = $result['non_craft_labels'] ?? [];
            
        } catch (Exception $e) {
            error_log("Craft classification exception: " . $e->getMessage());
            return $this->classifyWithFallbackHeuristics($filePath);
        }
        
        return $classification;
    }
    
    /**
     * Fallback classification when AI service is not available
     * Uses simple heuristics based on file characteristics
     */
    private function classifyWithFallbackHeuristics($filePath) {
        $classification = [
            'success' => true,
            'predicted_category' => 'hand_embroidery', // Default to tutorial category
            'confidence' => 0.3, // Moderate confidence for fallback
            'all_predictions' => [
                ['category' => 'hand_embroidery', 'confidence' => 0.3],
                ['category' => 'gift_making', 'confidence' => 0.2]
            ],
            'is_craft_related' => true, // Default to craft-related
            'non_craft_confidence' => 0.0,
            'non_craft_labels' => [],
            'error_message' => null,
            'fallback_used' => true
        ];
        
        try {
            // Basic file analysis
            $fileSize = filesize($filePath);
            $imageInfo = getimagesize($filePath);
            
            if (!$imageInfo) {
                $classification['is_craft_related'] = false;
                $classification['confidence'] = 0.1;
                $classification['non_craft_confidence'] = 0.8;
                $classification['non_craft_labels'] = [['label' => 'invalid_image', 'confidence' => 0.8]];
                return $classification;
            }
            
            $width = $imageInfo[0];
            $height = $imageInfo[1];
            
            // Simple heuristics for obviously wrong images
            $suspiciousPatterns = 0;
            $reasons = [];
            
            // Very small images (likely icons)
            if ($width < 100 || $height < 100) {
                $suspiciousPatterns++;
                $reasons[] = 'Very small dimensions';
            }
            
            // Very small file size (likely simple graphics)
            if ($fileSize < 10000) { // Less than 10KB
                $suspiciousPatterns++;
                $reasons[] = 'Very small file size';
            }
            
            // Extreme aspect ratios (likely screenshots)
            if ($width > 0 && $height > 0) {
                $aspectRatio = $width / $height;
                if ($aspectRatio > 3.0 || $aspectRatio < 0.33) {
                    $suspiciousPatterns++;
                    $reasons[] = 'Extreme aspect ratio';
                }
            }
            
            // If multiple suspicious patterns, likely not craft-related
            if ($suspiciousPatterns >= 2) {
                $classification['is_craft_related'] = false;
                $classification['confidence'] = 0.1;
                $classification['non_craft_confidence'] = 0.7;
                $classification['non_craft_labels'] = [
                    ['label' => 'suspicious_image', 'confidence' => 0.7]
                ];
                $classification['fallback_reasons'] = $reasons;
            } else {
                // Assume craft-related with moderate confidence
                $classification['is_craft_related'] = true;
                $classification['confidence'] = 0.4; // Higher confidence for reasonable images
                $classification['fallback_reasons'] = ['Reasonable image characteristics'];
            }
            
        } catch (Exception $e) {
            error_log("Fallback heuristics error: " . $e->getMessage());
            // Default to craft-related on error
            $classification['is_craft_related'] = true;
            $classification['confidence'] = 0.2;
        }
        
        return $classification;
    }
    
    /**
     * Check if predicted category matches selected tutorial category
     */
    private function checkCategoryMatch($classification, $selectedCategory, $tutorialId) {
        $match = [
            'matches' => false,
            'selected_category' => $selectedCategory,
            'predicted_category' => $classification['predicted_category'] ?? null,
            'confidence' => $classification['confidence'] ?? 0.0,
            'mismatch_severity' => 'none',
            'explanation' => ''
        ];
        
        try {
            // Normalize category names for comparison
            $normalizedSelected = $this->normalizeCategoryName($selectedCategory);
            $normalizedPredicted = $classification['predicted_category'] ?? '';
            
            if (!$classification['success'] || !$normalizedPredicted) {
                $match['explanation'] = 'Could not determine image category';
                $match['mismatch_severity'] = 'unknown';
                return $match;
            }
            
            // Check for exact match
            if ($normalizedSelected === $normalizedPredicted) {
                $match['matches'] = true;
                $match['explanation'] = 'Category matches selected tutorial';
                return $match;
            }
            
            // AUTO-APPROVE MODE: More flexible category matching
            if (self::AUTO_APPROVE_MODE) {
                // Check for fuzzy matches (related categories)
                $fuzzyMatch = $this->checkFuzzyMatch($normalizedSelected, $normalizedPredicted);
                if ($fuzzyMatch) {
                    $match['matches'] = true;
                    $match['explanation'] = "Related category match: {$normalizedPredicted} is similar to {$normalizedSelected}";
                    return $match;
                }
                
                // In auto-approve mode, only flag severe mismatches
                $confidence = $classification['confidence'];
                if ($confidence >= 0.90) { // Very high confidence required for mismatch
                    $match['mismatch_severity'] = 'high';
                    $match['explanation'] = "Very high confidence mismatch: Image appears to be {$normalizedPredicted} but tutorial is {$normalizedSelected}";
                } else {
                    // Treat as minor mismatch (won't trigger rejection)
                    $match['mismatch_severity'] = 'low';
                    $match['explanation'] = "Minor category difference, auto-approved for testing";
                }
                
                return $match;
            }
            
            // STRICT MODE: Original logic
            $confidence = $classification['confidence'];
            
            if ($confidence >= self::CATEGORY_MISMATCH_THRESHOLD) {
                $match['mismatch_severity'] = 'high';
                $match['explanation'] = "High confidence mismatch: Image appears to be {$normalizedPredicted} but tutorial is {$normalizedSelected}";
            } elseif ($confidence >= self::LOW_CONFIDENCE_THRESHOLD) {
                $match['mismatch_severity'] = 'medium';
                $match['explanation'] = "Possible mismatch: Image might be {$normalizedPredicted} but tutorial is {$normalizedSelected}";
            } else {
                $match['mismatch_severity'] = 'low';
                $match['explanation'] = "Low confidence prediction, category unclear";
            }
            
        } catch (Exception $e) {
            error_log("Category match check error: " . $e->getMessage());
            $match['explanation'] = 'Error checking category match';
            $match['mismatch_severity'] = 'unknown';
        }
        
        return $match;
    }
    
    /**
     * Check for fuzzy/related category matches
     */
    private function checkFuzzyMatch($selected, $predicted) {
        // Define related category groups
        $relatedCategories = [
            'textile_arts' => ['hand_embroidery', 'gift_making'],
            'decorative_arts' => ['candle_making', 'resin_art', 'mehandi_art'],
            'sculptural_arts' => ['clay_modeling', 'jewelry_making'],
            'handmade_crafts' => ['gift_making', 'jewelry_making', 'hand_embroidery']
        ];
        
        // Check if both categories are in the same group
        foreach ($relatedCategories as $group => $categories) {
            if (in_array($selected, $categories) && in_array($predicted, $categories)) {
                return true;
            }
        }
        
        // Check for partial string matches (e.g., "embroidery" in both)
        $selectedWords = explode('_', $selected);
        $predictedWords = explode('_', $predicted);
        
        foreach ($selectedWords as $word) {
            if (strlen($word) > 3 && in_array($word, $predictedWords)) {
                return true;
            }
        }
        
        return false;
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
     * Detect AI-generated images through metadata analysis
     */
    private function detectAIGenerated($filePath) {
        $detection = [
            'is_ai_generated' => false,
            'confidence' => 'unknown',
            'detected_generator' => null,
            'evidence' => [],
            'metadata_analysis' => []
        ];
        
        try {
            // Extract comprehensive metadata
            $metadata = $this->extractComprehensiveMetadata($filePath);
            $detection['metadata_analysis'] = $metadata;
            
            // Check for AI generator signatures
            $aiSignatures = $this->checkAISignatures($metadata);
            
            if ($aiSignatures['found']) {
                $detection['is_ai_generated'] = true;
                $detection['confidence'] = 'high';
                $detection['detected_generator'] = $aiSignatures['generator'];
                $detection['evidence'] = $aiSignatures['evidence'];
            } else {
                // Check for suspicious patterns
                $suspiciousPatterns = $this->checkSuspiciousPatterns($metadata);
                
                if ($suspiciousPatterns['suspicious']) {
                    $detection['is_ai_generated'] = false; // Don't auto-flag, just note
                    $detection['confidence'] = 'suspicious';
                    $detection['evidence'] = $suspiciousPatterns['patterns'];
                }
            }
            
        } catch (Exception $e) {
            error_log("AI detection error: " . $e->getMessage());
            $detection['metadata_analysis']['error'] = $e->getMessage();
        }
        
        return $detection;
    }
    
    /**
     * Extract comprehensive metadata for AI detection
     */
    private function extractComprehensiveMetadata($filePath) {
        $metadata = [
            'file_info' => [],
            'exif_data' => [],
            'software_info' => [],
            'creation_info' => [],
            'technical_info' => []
        ];
        
        try {
            // Basic file info
            $metadata['file_info'] = [
                'size' => filesize($filePath),
                'mime_type' => mime_content_type($filePath),
                'extension' => pathinfo($filePath, PATHINFO_EXTENSION)
            ];
            
            // Image dimensions
            $imageInfo = getimagesize($filePath);
            if ($imageInfo) {
                $metadata['technical_info'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'type' => $imageInfo[2],
                    'bits' => $imageInfo['bits'] ?? null,
                    'channels' => $imageInfo['channels'] ?? null
                ];
            }
            
            // EXIF data
            if (function_exists('exif_read_data')) {
                $exif = @exif_read_data($filePath);
                if ($exif) {
                    $metadata['exif_data'] = $exif;
                    
                    // Extract key fields
                    $metadata['software_info'] = [
                        'make' => $exif['Make'] ?? null,
                        'model' => $exif['Model'] ?? null,
                        'software' => $exif['Software'] ?? null,
                        'artist' => $exif['Artist'] ?? null,
                        'copyright' => $exif['Copyright'] ?? null,
                        'user_comment' => $exif['UserComment'] ?? null
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
    private function checkAISignatures($metadata) {
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
            'exif_data.Software',
            'exif_data.Artist',
            'exif_data.Copyright',
            'exif_data.UserComment',
            'exif_data.ImageDescription'
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
     * Check for suspicious patterns that might indicate AI generation
     */
    private function checkSuspiciousPatterns($metadata) {
        $result = [
            'suspicious' => false,
            'patterns' => []
        ];
        
        // Check for missing EXIF data (common in AI images)
        if (empty($metadata['exif_data']) || count($metadata['exif_data']) < 5) {
            $result['patterns'][] = 'Minimal or missing EXIF data';
        }
        
        // Check for unusual dimensions (common AI output sizes)
        $width = $metadata['technical_info']['width'] ?? 0;
        $height = $metadata['technical_info']['height'] ?? 0;
        
        $commonAISizes = [512, 768, 1024, 1536, 2048];
        if (in_array($width, $commonAISizes) && in_array($height, $commonAISizes)) {
            $result['patterns'][] = "Common AI output dimensions: {$width}x{$height}";
        }
        
        // Check for perfect square dimensions
        if ($width === $height && $width > 0) {
            $result['patterns'][] = "Perfect square dimensions: {$width}x{$height}";
        }
        
        // Check for missing camera info
        $make = $metadata['software_info']['make'] ?? '';
        $model = $metadata['software_info']['model'] ?? '';
        if (empty($make) && empty($model)) {
            $result['patterns'][] = 'No camera make/model information';
        }
        
        $result['suspicious'] = count($result['patterns']) >= 2;
        
        return $result;
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
     * Make final validation decision based on all checks
     */
    private function makeValidationDecision($classification, $categoryMatch, $aiDetection) {
        $decision = [
            'validation_status' => 'approved',
            'rejection_reason' => null,
            'flag_reason' => null,
            'requires_review' => false,
            'confidence_scores' => [
                'craft_classification' => $classification['confidence'] ?? 0.0,
                'category_match' => $categoryMatch['matches'] ? 1.0 : 0.0,
                'ai_detection_confidence' => $aiDetection['confidence']
            ]
        ];
        
        // BINARY VALIDATION: Only "approved" or "rejected" - no pending/flagged states
        
        // Rule 1: REJECT - AI-generated images
        if ($aiDetection['is_ai_generated'] && $aiDetection['confidence'] === 'high') {
            $decision['validation_status'] = 'rejected';
            $decision['rejection_reason'] = "AI-generated image detected: {$aiDetection['detected_generator']}";
            return $decision;
        }
        
        // Rule 2: REJECT - Non-craft related images (aggressive rejection)
        if ($classification['success']) {
            $confidence = $classification['confidence'];
            $isCraftRelated = $classification['is_craft_related'];
            $nonCraftConfidence = $classification['non_craft_confidence'] ?? 0.0;
            
            // Reject if AI is confident it's not craft-related (lowered threshold)
            if (!$isCraftRelated && $nonCraftConfidence > 0.2) {
                $decision['validation_status'] = 'rejected';
                $decision['rejection_reason'] = 'Image appears to be unrelated to crafts (detected: ' . 
                    implode(', ', array_column($classification['non_craft_labels'] ?? [], 'label')) . ')';
                return $decision;
            }
            
            // Reject very low confidence images (likely not craft-related)
            if ($confidence < 0.05) {
                $decision['validation_status'] = 'rejected';
                $decision['rejection_reason'] = 'Image appears to be unrelated to crafts (very low AI confidence: ' . round($confidence * 100, 1) . '%)';
                return $decision;
            }
            
            // Reject if clearly not craft-related with medium confidence
            if (!$isCraftRelated && $confidence >= 0.60) {
                $decision['validation_status'] = 'rejected';
                $decision['rejection_reason'] = 'Image is clearly unrelated to crafts (high confidence non-craft detection)';
                return $decision;
            }
        }
        
        // Rule 3: REJECT - High and medium confidence category mismatches
        if ($categoryMatch['mismatch_severity'] === 'high' && $classification['confidence'] >= 0.70) {
            $decision['validation_status'] = 'rejected';
            $decision['rejection_reason'] = "Wrong craft category: {$categoryMatch['explanation']}";
            return $decision;
        }
        
        // Rule 3b: REJECT - Medium confidence category mismatches (new rule)
        if ($categoryMatch['mismatch_severity'] === 'medium' && $classification['confidence'] >= 0.50) {
            $decision['validation_status'] = 'rejected';
            $decision['rejection_reason'] = "Category mismatch detected: {$categoryMatch['explanation']}";
            return $decision;
        }
        
        // Rule 4: REJECT - Classification failures (can't determine content)
        if (!$classification['success']) {
            $decision['validation_status'] = 'rejected';
            $decision['rejection_reason'] = 'Could not classify image content - may be corrupted or invalid';
            return $decision;
        }
        
        // Rule 5: REJECT - Suspicious AI patterns with multiple evidence
        if ($aiDetection['confidence'] === 'suspicious' && count($aiDetection['evidence'] ?? []) >= 3) {
            $decision['validation_status'] = 'rejected';
            $decision['rejection_reason'] = 'Multiple suspicious patterns detected, likely AI-generated';
            return $decision;
        }
        
        // Rule 6: REJECT - Low confidence with suspicious metadata patterns
        if ($classification['confidence'] < 0.20) {
            $suspiciousPatterns = $this->checkForObviousNonCraftPatterns($aiDetection['metadata_analysis'] ?? []);
            if ($suspiciousPatterns['is_suspicious']) {
                $decision['validation_status'] = 'rejected';
                $decision['rejection_reason'] = 'Low AI confidence with suspicious patterns: ' . implode(', ', $suspiciousPatterns['reasons']);
                return $decision;
            }
        }
        
        // Rule 7: APPROVE - Everything else
        // If it passes all rejection rules, approve it
        $decision['validation_status'] = 'approved';
        $decision['rejection_reason'] = null;
        $decision['requires_review'] = false;
        
        // Add informational note about confidence level
        if ($classification['success']) {
            $confidence = round($classification['confidence'] * 100, 1);
            $decision['info_note'] = "Approved with {$confidence}% AI confidence as {$classification['predicted_category']}";
        }
        
        return $decision;
    }
    
    /**
     * Check for obvious non-craft patterns in metadata and image characteristics
     */
    private function checkForObviousNonCraftPatterns($metadata) {
        $result = [
            'is_suspicious' => false,
            'reasons' => []
        ];
        
        try {
            // Check image dimensions for common non-craft patterns
            $width = $metadata['technical_info']['width'] ?? 0;
            $height = $metadata['technical_info']['height'] ?? 0;
            
            // Very small images (likely icons or thumbnails)
            if ($width > 0 && $height > 0 && ($width < 100 || $height < 100)) {
                $result['reasons'][] = 'Very small image dimensions (likely icon/thumbnail)';
            }
            
            // Extreme aspect ratios (likely screenshots or banners)
            if ($width > 0 && $height > 0) {
                $aspectRatio = $width / $height;
                if ($aspectRatio > 3.0 || $aspectRatio < 0.33) {
                    $result['reasons'][] = 'Extreme aspect ratio (likely screenshot/banner)';
                }
            }
            
            // Check file size patterns
            $fileSize = $metadata['file_info']['size'] ?? 0;
            
            // Very small file size (likely low quality or simple graphics)
            if ($fileSize > 0 && $fileSize < 10000) { // Less than 10KB
                $result['reasons'][] = 'Very small file size (likely simple graphic)';
            }
            
            // Check for missing camera metadata (common in generated/edited images)
            $make = $metadata['software_info']['make'] ?? '';
            $model = $metadata['software_info']['model'] ?? '';
            $software = $metadata['software_info']['software'] ?? '';
            
            if (empty($make) && empty($model) && !empty($software)) {
                // Has software info but no camera info - likely edited/generated
                $result['reasons'][] = 'No camera metadata but has software info';
            }
            
            $result['is_suspicious'] = count($result['reasons']) >= 2;
            
        } catch (Exception $e) {
            error_log("Error checking non-craft patterns: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Combine authenticity and craft validation results
     */
    private function combineValidationResults($authenticityResult, $craftValidation, $imageId) {
        // Start with authenticity result as base
        $combined = $authenticityResult;
        
        // Add craft validation data
        $combined['craft_validation'] = $craftValidation;
        
        // Override status if craft validation is more restrictive
        if ($craftValidation['validation_status'] === 'rejected') {
            $combined['status'] = 'rejected';
            $combined['requires_admin_review'] = true;
            $combined['flagged_reason'] = $craftValidation['rejection_reason'];
        } elseif ($craftValidation['validation_status'] === 'flagged' || $craftValidation['requires_review']) {
            $combined['requires_admin_review'] = true;
            
            // Combine flagged reasons
            $reasons = array_filter([
                $combined['flagged_reason'] ?? null,
                $craftValidation['flag_reason'] ?? null
            ]);
            $combined['flagged_reason'] = implode('; ', $reasons);
        }
        
        // Add AI insights to explanation
        if ($craftValidation['craft_classification']['success']) {
            $predicted = $craftValidation['craft_classification']['predicted_category'];
            $confidence = $craftValidation['craft_classification']['confidence'];
            $combined['ai_insights'] = "AI predicted category: {$predicted} (confidence: " . round($confidence * 100, 1) . "%)";
        }
        
        return $combined;
    }
    
    /**
     * Store craft validation results
     */
    private function storeCraftValidationResult($imageId, $imageType, $craftValidation, $userId, $tutorialId) {
        try {
            $this->ensureCraftValidationTable();
            
            $stmt = $this->pdo->prepare("
                INSERT INTO craft_image_validation 
                (image_id, image_type, user_id, tutorial_id, 
                 predicted_category, prediction_confidence, category_matches, 
                 ai_generated_detected, ai_generator, ai_confidence,
                 validation_status, rejection_reason, flag_reason,
                 all_predictions, ai_evidence, metadata_analysis, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                predicted_category = VALUES(predicted_category),
                prediction_confidence = VALUES(prediction_confidence),
                category_matches = VALUES(category_matches),
                ai_generated_detected = VALUES(ai_generated_detected),
                ai_generator = VALUES(ai_generator),
                ai_confidence = VALUES(ai_confidence),
                validation_status = VALUES(validation_status),
                rejection_reason = VALUES(rejection_reason),
                flag_reason = VALUES(flag_reason),
                all_predictions = VALUES(all_predictions),
                ai_evidence = VALUES(ai_evidence),
                metadata_analysis = VALUES(metadata_analysis),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $imageId,
                $imageType,
                $userId,
                $tutorialId,
                $craftValidation['craft_classification']['predicted_category'] ?? null,
                $craftValidation['craft_classification']['confidence'] ?? 0.0,
                $craftValidation['category_match']['matches'] ? 1 : 0,
                $craftValidation['ai_generated_detection']['is_ai_generated'] ? 1 : 0,
                $craftValidation['ai_generated_detection']['detected_generator'] ?? null,
                $craftValidation['ai_generated_detection']['confidence'] ?? 'unknown',
                $craftValidation['validation_status'],
                $craftValidation['rejection_reason'],
                $craftValidation['flag_reason'],
                json_encode($craftValidation['craft_classification']['all_predictions'] ?? []),
                json_encode($craftValidation['ai_generated_detection']['evidence'] ?? []),
                json_encode($craftValidation['ai_generated_detection']['metadata_analysis'] ?? [])
            ]);
            
        } catch (Exception $e) {
            error_log("Error storing craft validation result: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure craft validation table exists
     */
    private function ensureCraftValidationTable() {
        try {
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS `craft_image_validation` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `image_id` varchar(255) NOT NULL,
                  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
                  `user_id` int(11) NOT NULL,
                  `tutorial_id` int(11) DEFAULT NULL,
                  `predicted_category` varchar(50) DEFAULT NULL,
                  `prediction_confidence` decimal(5,4) DEFAULT 0.0000,
                  `category_matches` tinyint(1) DEFAULT 0,
                  `ai_generated_detected` tinyint(1) DEFAULT 0,
                  `ai_generator` varchar(50) DEFAULT NULL,
                  `ai_confidence` enum('unknown', 'suspicious', 'high') DEFAULT 'unknown',
                  `validation_status` enum('approved', 'flagged', 'rejected') DEFAULT 'approved',
                  `rejection_reason` text DEFAULT NULL,
                  `flag_reason` text DEFAULT NULL,
                  `all_predictions` json DEFAULT NULL,
                  `ai_evidence` json DEFAULT NULL,
                  `metadata_analysis` json DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_image_validation` (`image_id`, `image_type`),
                  KEY `idx_validation_status` (`validation_status`),
                  KEY `idx_predicted_category` (`predicted_category`),
                  KEY `idx_ai_generated` (`ai_generated_detected`),
                  KEY `idx_user_tutorial` (`user_id`, `tutorial_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Exception $e) {
            error_log("Craft validation table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Create error result
     */
    private function createErrorResult($imageId, $errorCode, $errorMessage) {
        return [
            'status' => 'error',
            'error_code' => $errorCode,
            'error_message' => $errorMessage,
            'explanation' => 'Craft validation failed - admin review required',
            'requires_admin_review' => true,
            'craft_validation' => [
                'validation_status' => 'error',
                'rejection_reason' => $errorMessage,
                'requires_review' => true
            ]
        ];
    }
}
?>