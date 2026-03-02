#!/usr/bin/env python3
"""
AI-Generated Image Detection Module
Multi-layer risk scoring system for detecting AI-generated images

Detection Layers:
1. Metadata Analysis - Inspect for AI generator keywords
2. EXIF Analysis - Check for camera metadata presence
3. Texture Smoothness - Detect synthetic-looking images via Laplacian variance
4. Watermark Detection - Optional detection of known AI platform watermarks

Risk Scoring: Weighted cumulative score determines final decision
- High Risk (>= 70): Auto-reject as AI-generated
- Medium Risk (40-69): Flag for admin review (AI_Flagged)
- Low Risk (< 40): Pass AI detection

Academic Research Ready: Deterministic, explainable, probabilistic risk-based detection
"""

import os
import cv2
import numpy as np
from PIL import Image
from PIL.ExifTags import TAGS
import json
from typing import Dict, List, Tuple, Optional


class AIImageDetector:
    """
    Multi-layer AI-generated image detection with weighted risk scoring
    """
    
    # Known AI generator keywords in metadata
    AI_GENERATOR_KEYWORDS = [
        'stable diffusion', 'stablediffusion', 'sd', 'sdxl',
        'midjourney', 'mj',
        'dall-e', 'dalle', 'dall·e',
        'firefly', 'adobe firefly',
        'ai generated', 'ai-generated', 'artificial intelligence',
        'generated', 'synthetic',
        'leonardo.ai', 'leonardo',
        'craiyon',
        'nightcafe',
        'artbreeder',
        'runway',
        'imagen',
        'deepdream'
    ]
    
    # Risk score weights (total should allow reaching 100)
    WEIGHT_METADATA_AI_KEYWORD = 50  # Strong signal
    WEIGHT_NO_EXIF_CAMERA = 20       # Moderate signal
    WEIGHT_SMOOTH_TEXTURE = 25       # Moderate signal
    WEIGHT_WATERMARK_DETECTED = 15   # Weak signal
    
    # Thresholds
    THRESHOLD_HIGH_RISK = 70    # Auto-reject
    THRESHOLD_MEDIUM_RISK = 40  # Flag for review
    
    # Texture smoothness threshold (Laplacian variance)
    LAPLACIAN_THRESHOLD = 100.0  # Below this = too smooth/synthetic
    
    def __init__(self):
        """Initialize AI image detector"""
        self.detection_stats = {
            'total_analyzed': 0,
            'high_risk_detected': 0,
            'medium_risk_detected': 0,
            'low_risk_detected': 0
        }
    
    def analyze_image(self, image_path: str) -> Dict:
        """
        Perform comprehensive AI detection analysis on image
        
        Args:
            image_path: Path to image file
            
        Returns:
            Dictionary with detection results and risk score
        """
        result = {
            'success': False,
            'ai_risk_score': 0,
            'risk_level': 'unknown',
            'is_likely_ai_generated': False,
            'detection_evidence': {
                'metadata_analysis': {},
                'exif_analysis': {},
                'texture_analysis': {},
                'watermark_analysis': {}
            },
            'decision': 'pass',  # pass, flag, reject
            'explanation': '',
            'error_message': None
        }
        
        try:
            # Validate image exists
            if not os.path.exists(image_path):
                result['error_message'] = 'Image file not found'
                return result
            
            # Layer 1: Metadata Analysis
            metadata_result = self._analyze_metadata(image_path)
            result['detection_evidence']['metadata_analysis'] = metadata_result
            
            # Layer 2: EXIF Analysis
            exif_result = self._analyze_exif(image_path)
            result['detection_evidence']['exif_analysis'] = exif_result
            
            # Layer 3: Texture Smoothness Analysis
            texture_result = self._analyze_texture_smoothness(image_path)
            result['detection_evidence']['texture_analysis'] = texture_result
            
            # Layer 4: Watermark Detection (Optional)
            watermark_result = self._detect_watermark_region(image_path)
            result['detection_evidence']['watermark_analysis'] = watermark_result
            
            # Calculate cumulative AI risk score
            ai_risk_score = self._calculate_risk_score(
                metadata_result,
                exif_result,
                texture_result,
                watermark_result
            )
            
            result['ai_risk_score'] = ai_risk_score
            
            # Determine risk level and decision
            if ai_risk_score >= self.THRESHOLD_HIGH_RISK:
                result['risk_level'] = 'high'
                result['is_likely_ai_generated'] = True
                result['decision'] = 'reject'
                result['explanation'] = f'High AI risk score ({ai_risk_score}/100) - likely AI-generated'
            elif ai_risk_score >= self.THRESHOLD_MEDIUM_RISK:
                result['risk_level'] = 'medium'
                result['is_likely_ai_generated'] = False  # Uncertain
                result['decision'] = 'flag'
                result['explanation'] = f'Medium AI risk score ({ai_risk_score}/100) - requires admin review'
            else:
                result['risk_level'] = 'low'
                result['is_likely_ai_generated'] = False
                result['decision'] = 'pass'
                result['explanation'] = f'Low AI risk score ({ai_risk_score}/100) - passed AI detection'
            
            result['success'] = True
            self.detection_stats['total_analyzed'] += 1
            
            if result['risk_level'] == 'high':
                self.detection_stats['high_risk_detected'] += 1
            elif result['risk_level'] == 'medium':
                self.detection_stats['medium_risk_detected'] += 1
            else:
                self.detection_stats['low_risk_detected'] += 1
            
        except Exception as e:
            result['error_message'] = f'AI detection exception: {str(e)}'
            result['explanation'] = 'Error during AI detection - defaulting to manual review'
            result['decision'] = 'flag'
        
        return result
    
    def _analyze_metadata(self, image_path: str) -> Dict:
        """
        Layer 1: Analyze image metadata for AI generator keywords
        
        Returns:
            Dictionary with metadata analysis results
        """
        analysis = {
            'ai_keywords_found': [],
            'metadata_fields_checked': [],
            'has_ai_signature': False,
            'risk_contribution': 0,
            'details': ''
        }
        
        try:
            with Image.open(image_path) as img:
                # Check various metadata fields
                metadata_to_check = {}
                
                # PIL info dictionary
                if hasattr(img, 'info') and img.info:
                    metadata_to_check.update(img.info)
                
                # EXIF data
                if hasattr(img, '_getexif') and img._getexif():
                    exif_data = img._getexif()
                    if exif_data:
                        for tag_id, value in exif_data.items():
                            tag_name = TAGS.get(tag_id, tag_id)
                            metadata_to_check[tag_name] = value
                
                # Search for AI keywords in metadata
                for field, value in metadata_to_check.items():
                    analysis['metadata_fields_checked'].append(str(field))
                    
                    if value and isinstance(value, (str, bytes)):
                        value_str = str(value).lower()
                        
                        for keyword in self.AI_GENERATOR_KEYWORDS:
                            if keyword in value_str:
                                analysis['ai_keywords_found'].append({
                                    'keyword': keyword,
                                    'field': str(field),
                                    'value_snippet': value_str[:100]
                                })
                                analysis['has_ai_signature'] = True
                
                # Calculate risk contribution
                if analysis['has_ai_signature']:
                    analysis['risk_contribution'] = self.WEIGHT_METADATA_AI_KEYWORD
                    analysis['details'] = f"Found AI generator keywords: {', '.join([k['keyword'] for k in analysis['ai_keywords_found']])}"
                else:
                    analysis['details'] = 'No AI generator keywords found in metadata'
        
        except Exception as e:
            analysis['details'] = f'Metadata analysis error: {str(e)}'
        
        return analysis
    
    def _analyze_exif(self, image_path: str) -> Dict:
        """
        Layer 2: Analyze EXIF data for camera metadata presence
        
        Real photos typically have camera metadata (model, ISO, exposure, lens)
        AI-generated images typically lack this metadata
        
        Returns:
            Dictionary with EXIF analysis results
        """
        analysis = {
            'has_camera_metadata': False,
            'camera_fields_found': [],
            'missing_camera_fields': [],
            'risk_contribution': 0,
            'details': ''
        }
        
        # Camera-related EXIF fields to check
        camera_fields = [
            'Make', 'Model',  # Camera manufacturer and model
            'ISOSpeedRatings', 'ISO',  # ISO setting
            'ExposureTime', 'ShutterSpeedValue',  # Exposure
            'FNumber', 'ApertureValue',  # Aperture
            'LensModel', 'LensMake',  # Lens information
            'FocalLength',  # Focal length
            'Flash',  # Flash usage
            'WhiteBalance'  # White balance
        ]
        
        try:
            with Image.open(image_path) as img:
                exif_data = None
                
                if hasattr(img, '_getexif'):
                    exif_data = img._getexif()
                
                if exif_data:
                    # Check for camera-related fields
                    for tag_id, value in exif_data.items():
                        tag_name = TAGS.get(tag_id, tag_id)
                        
                        if tag_name in camera_fields and value:
                            analysis['camera_fields_found'].append({
                                'field': tag_name,
                                'value': str(value)[:50]  # Truncate long values
                            })
                            analysis['has_camera_metadata'] = True
                    
                    # Identify missing camera fields
                    found_field_names = [f['field'] for f in analysis['camera_fields_found']]
                    analysis['missing_camera_fields'] = [
                        f for f in camera_fields if f not in found_field_names
                    ]
                else:
                    analysis['missing_camera_fields'] = camera_fields
                
                # Calculate risk contribution
                if not analysis['has_camera_metadata']:
                    analysis['risk_contribution'] = self.WEIGHT_NO_EXIF_CAMERA
                    analysis['details'] = 'No camera metadata found - typical of AI-generated images'
                else:
                    analysis['details'] = f"Found {len(analysis['camera_fields_found'])} camera metadata fields"
        
        except Exception as e:
            analysis['details'] = f'EXIF analysis error: {str(e)}'
            # If we can't read EXIF, treat as suspicious
            analysis['risk_contribution'] = self.WEIGHT_NO_EXIF_CAMERA // 2
        
        return analysis
    
    def _analyze_texture_smoothness(self, image_path: str) -> Dict:
        """
        Layer 3: Analyze texture smoothness using Laplacian variance
        
        AI-generated images often have unnaturally smooth textures
        Laplacian variance measures edge sharpness - low variance = smooth/synthetic
        
        Returns:
            Dictionary with texture analysis results
        """
        analysis = {
            'laplacian_variance': 0.0,
            'is_overly_smooth': False,
            'risk_contribution': 0,
            'details': ''
        }
        
        try:
            # Read image with OpenCV
            img = cv2.imread(image_path)
            
            if img is None:
                analysis['details'] = 'Could not read image for texture analysis'
                return analysis
            
            # Convert to grayscale
            gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
            
            # Calculate Laplacian variance
            laplacian = cv2.Laplacian(gray, cv2.CV_64F)
            variance = laplacian.var()
            
            analysis['laplacian_variance'] = float(variance)
            
            # Check if overly smooth
            if variance < self.LAPLACIAN_THRESHOLD:
                analysis['is_overly_smooth'] = True
                analysis['risk_contribution'] = self.WEIGHT_SMOOTH_TEXTURE
                analysis['details'] = f'Low texture variance ({variance:.2f}) - image appears overly smooth/synthetic'
            else:
                analysis['details'] = f'Normal texture variance ({variance:.2f}) - natural texture detected'
        
        except Exception as e:
            analysis['details'] = f'Texture analysis error: {str(e)}'
        
        return analysis
    
    def _detect_watermark_region(self, image_path: str) -> Dict:
        """
        Layer 4: Optional watermark detection in bottom-right corner
        
        Checks for known AI platform watermark patterns (sparkle-style overlays)
        This is a weak signal and not definitive evidence
        
        Returns:
            Dictionary with watermark detection results
        """
        analysis = {
            'watermark_detected': False,
            'watermark_location': None,
            'watermark_confidence': 0.0,
            'risk_contribution': 0,
            'details': ''
        }
        
        try:
            # Read image
            img = cv2.imread(image_path)
            
            if img is None:
                analysis['details'] = 'Could not read image for watermark detection'
                return analysis
            
            height, width = img.shape[:2]
            
            # Define bottom-right region (last 15% of width and height)
            region_height = int(height * 0.15)
            region_width = int(width * 0.15)
            
            # Extract bottom-right corner
            bottom_right = img[height - region_height:height, width - region_width:width]
            
            # Convert to grayscale
            gray_region = cv2.cvtColor(bottom_right, cv2.COLOR_BGR2GRAY)
            
            # Check for high-contrast patterns (typical of watermarks)
            # Calculate standard deviation - watermarks often have high contrast
            std_dev = np.std(gray_region)
            mean_intensity = np.mean(gray_region)
            
            # Simple heuristic: high std dev + bright region = possible watermark
            if std_dev > 40 and mean_intensity > 180:
                analysis['watermark_detected'] = True
                analysis['watermark_location'] = 'bottom_right'
                analysis['watermark_confidence'] = min(std_dev / 100.0, 1.0)
                analysis['risk_contribution'] = self.WEIGHT_WATERMARK_DETECTED
                analysis['details'] = f'Possible watermark pattern detected in bottom-right (confidence: {analysis["watermark_confidence"]:.2f})'
            else:
                analysis['details'] = 'No obvious watermark pattern detected'
        
        except Exception as e:
            analysis['details'] = f'Watermark detection error: {str(e)}'
        
        return analysis
    
    def _calculate_risk_score(
        self,
        metadata_result: Dict,
        exif_result: Dict,
        texture_result: Dict,
        watermark_result: Dict
    ) -> int:
        """
        Calculate cumulative AI risk score from all detection layers
        
        Returns:
            Risk score (0-100)
        """
        total_score = 0
        
        total_score += metadata_result.get('risk_contribution', 0)
        total_score += exif_result.get('risk_contribution', 0)
        total_score += texture_result.get('risk_contribution', 0)
        total_score += watermark_result.get('risk_contribution', 0)
        
        # Ensure score is within bounds
        return min(max(int(total_score), 0), 100)
    
    def get_detection_stats(self) -> Dict:
        """Get detection statistics"""
        return self.detection_stats.copy()


# Standalone testing function
if __name__ == '__main__':
    import sys
    
    if len(sys.argv) < 2:
        print("Usage: python ai_image_detector.py <image_path>")
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    detector = AIImageDetector()
    result = detector.analyze_image(image_path)
    
    print("\n=== AI Image Detection Results ===")
    print(json.dumps(result, indent=2))
    print("\n=== Detection Statistics ===")
    print(json.dumps(detector.get_detection_stats(), indent=2))
