#!/usr/bin/env python3
"""
Image Authenticity and Practice Validation Service
Provides comprehensive image verification including metadata extraction,
perceptual hashing, and similarity detection for learning platform uploads.
"""

import os
import sys
import json
import hashlib
import logging
from datetime import datetime
from typing import Dict, List, Optional, Tuple, Any
import mysql.connector
from mysql.connector import Error
import requests
from PIL import Image, ExifTags
from PIL.ExifTags import TAGS
import imagehash
import numpy as np
from pathlib import Path
import mimetypes
import subprocess
import tempfile

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('image_authenticity.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

class ImageAuthenticityService:
    """Main service class for image authenticity verification"""
    
    def __init__(self, config_path: str = 'config.json'):
        """Initialize the service with configuration"""
        self.config = self._load_config(config_path)
        self.db_connection = None
        self._connect_database()
        
        # Authenticity thresholds (loaded from database settings)
        self.thresholds = self._load_authenticity_settings()
        
        # Known editing software signatures
        self.editing_software_signatures = {
            'Adobe Photoshop': ['Adobe Photoshop', 'Photoshop'],
            'GIMP': ['GIMP', 'GNU Image Manipulation Program'],
            'Canva': ['Canva'],
            'PicsArt': ['PicsArt'],
            'Snapseed': ['Snapseed'],
            'VSCO': ['VSCO'],
            'Instagram': ['Instagram'],
            'Facetune': ['Facetune'],
            'Adobe Lightroom': ['Adobe Lightroom', 'Lightroom'],
            'Paint.NET': ['Paint.NET'],
            'Pixlr': ['Pixlr'],
            'FotoJet': ['FotoJet'],
            'Fotor': ['Fotor']
        }
        
        # Suspicious metadata patterns
        self.suspicious_patterns = {
            'missing_camera_info': 'No camera information found',
            'generic_software': 'Generic image editing software detected',
            'multiple_edits': 'Multiple editing software signatures found',
            'timestamp_mismatch': 'Creation and modification timestamps mismatch significantly',
            'unusual_dimensions': 'Unusual image dimensions detected',
            'low_quality_upscale': 'Image appears to be artificially upscaled'
        }
    
    def _load_config(self, config_path: str) -> Dict:
        """Load configuration from JSON file"""
        try:
            with open(config_path, 'r') as f:
                return json.load(f)
        except FileNotFoundError:
            # Default configuration
            return {
                'database': {
                    'host': 'localhost',
                    'user': 'root',
                    'password': '',
                    'database': 'my_little_thingz'
                },
                'upload_base_path': '../backend/uploads/',
                'temp_dir': '/tmp/image_processing'
            }
    
    def _connect_database(self):
        """Establish database connection"""
        try:
            self.db_connection = mysql.connector.connect(
                host=self.config['database']['host'],
                user=self.config['database']['user'],
                password=self.config['database']['password'],
                database=self.config['database']['database'],
                autocommit=True
            )
            logger.info("Database connection established")
        except Error as e:
            logger.error(f"Database connection failed: {e}")
            raise
    
    def _load_authenticity_settings(self) -> Dict:
        """Load authenticity settings from database"""
        try:
            cursor = self.db_connection.cursor(dictionary=True)
            cursor.execute("SELECT setting_key, setting_value, setting_type FROM authenticity_settings")
            settings = cursor.fetchall()
            
            thresholds = {}
            for setting in settings:
                key = setting['setting_key']
                value = setting['setting_value']
                setting_type = setting['setting_type']
                
                if setting_type == 'number':
                    thresholds[key] = float(value)
                elif setting_type == 'boolean':
                    thresholds[key] = value.lower() == 'true'
                else:
                    thresholds[key] = value
            
            cursor.close()
            return thresholds
            
        except Error as e:
            logger.error(f"Failed to load authenticity settings: {e}")
            # Return default thresholds
            return {
                'suspicious_score_threshold': 60.0,
                'highly_suspicious_score_threshold': 80.0,
                'similarity_threshold': 0.85,
                'auto_approve_clean_threshold': 30.0
            }
    
    def verify_image(self, image_id: str, image_type: str, file_path: str, user_id: int, tutorial_id: Optional[int] = None) -> Dict:
        """
        Main verification method for a single image
        Returns comprehensive authenticity analysis
        """
        logger.info(f"Starting verification for image {image_id} (type: {image_type})")
        
        try:
            # Initialize result structure
            result = {
                'image_id': image_id,
                'image_type': image_type,
                'verification_status': 'verified',
                'authenticity_score': 0.0,
                'risk_level': 'clean',
                'flagged_reasons': [],
                'metadata_extracted': {},
                'camera_info': {},
                'editing_software': {},
                'similarity_matches': [],
                'processing_errors': []
            }
            
            # Check if file exists
            full_path = os.path.join(self.config['upload_base_path'], file_path)
            if not os.path.exists(full_path):
                raise FileNotFoundError(f"Image file not found: {full_path}")
            
            # Generate file hash
            file_hash = self._generate_file_hash(full_path)
            
            # Extract metadata
            metadata = self._extract_image_metadata(full_path)
            result['metadata_extracted'] = metadata
            
            # Extract camera information
            camera_info = self._extract_camera_info(metadata)
            result['camera_info'] = camera_info
            
            # Detect editing software
            editing_software = self._detect_editing_software(metadata)
            result['editing_software'] = editing_software
            
            # Generate perceptual hash
            perceptual_hash = self._generate_perceptual_hash(full_path)
            
            # Check for similar images
            similarity_matches = self._check_similarity(perceptual_hash, image_id, image_type)
            result['similarity_matches'] = similarity_matches
            
            # Calculate authenticity score
            authenticity_score, flagged_reasons = self._calculate_authenticity_score(
                metadata, camera_info, editing_software, similarity_matches
            )
            result['authenticity_score'] = authenticity_score
            result['flagged_reasons'] = flagged_reasons
            
            # Determine risk level
            result['risk_level'] = self._determine_risk_level(authenticity_score)
            
            # Determine verification status
            if authenticity_score >= self.thresholds['highly_suspicious_score_threshold']:
                result['verification_status'] = 'flagged'
            elif authenticity_score >= self.thresholds['suspicious_score_threshold']:
                result['verification_status'] = 'flagged'
            elif authenticity_score <= self.thresholds['auto_approve_clean_threshold']:
                result['verification_status'] = 'verified'
            else:
                result['verification_status'] = 'flagged'  # Manual review needed
            
            # Store results in database
            self._store_authenticity_metadata(
                image_id, image_type, full_path, file_hash, perceptual_hash, result
            )
            
            # Add to admin review queue if flagged
            if result['verification_status'] == 'flagged':
                self._add_to_admin_review_queue(image_id, image_type, user_id, tutorial_id, result)
            
            # Log audit trail
            self._log_audit_action(
                image_id, image_type, 'verification_completed', 
                None, result['verification_status'], user_id, 'system'
            )
            
            logger.info(f"Verification completed for {image_id}: {result['verification_status']} (score: {authenticity_score})")
            return result
            
        except Exception as e:
            logger.error(f"Verification failed for {image_id}: {str(e)}")
            result['processing_errors'].append(str(e))
            result['verification_status'] = 'failed'
            return result
    
    def _generate_file_hash(self, file_path: str) -> str:
        """Generate SHA-256 hash of the file"""
        hash_sha256 = hashlib.sha256()
        with open(file_path, "rb") as f:
            for chunk in iter(lambda: f.read(4096), b""):
                hash_sha256.update(chunk)
        return hash_sha256.hexdigest()
    
    def _extract_image_metadata(self, file_path: str) -> Dict:
        """Extract comprehensive metadata from image"""
        metadata = {}
        
        try:
            # Basic file information
            stat = os.stat(file_path)
            metadata['file_size'] = stat.st_size
            metadata['created_time'] = datetime.fromtimestamp(stat.st_ctime).isoformat()
            metadata['modified_time'] = datetime.fromtimestamp(stat.st_mtime).isoformat()
            
            # MIME type
            mime_type, _ = mimetypes.guess_type(file_path)
            metadata['mime_type'] = mime_type
            
            # Image dimensions and format
            with Image.open(file_path) as img:
                metadata['width'] = img.width
                metadata['height'] = img.height
                metadata['format'] = img.format
                metadata['mode'] = img.mode
                
                # EXIF data
                exif_data = {}
                if hasattr(img, '_getexif') and img._getexif() is not None:
                    exif = img._getexif()
                    for tag_id, value in exif.items():
                        tag = TAGS.get(tag_id, tag_id)
                        exif_data[tag] = str(value)
                
                metadata['exif'] = exif_data
                
        except Exception as e:
            logger.warning(f"Failed to extract metadata from {file_path}: {e}")
            metadata['extraction_error'] = str(e)
        
        return metadata
    
    def _extract_camera_info(self, metadata: Dict) -> Dict:
        """Extract camera-specific information from metadata"""
        camera_info = {}
        exif = metadata.get('exif', {})
        
        # Camera make and model
        camera_info['make'] = exif.get('Make', '')
        camera_info['model'] = exif.get('Model', '')
        
        # Camera settings
        camera_info['iso'] = exif.get('ISOSpeedRatings', '')
        camera_info['aperture'] = exif.get('FNumber', '')
        camera_info['shutter_speed'] = exif.get('ExposureTime', '')
        camera_info['focal_length'] = exif.get('FocalLength', '')
        
        # Date and time
        camera_info['datetime_original'] = exif.get('DateTimeOriginal', '')
        camera_info['datetime_digitized'] = exif.get('DateTimeDigitized', '')
        
        # GPS information (if available)
        gps_info = exif.get('GPSInfo', {})
        if gps_info:
            camera_info['has_gps'] = True
            camera_info['gps_data'] = str(gps_info)
        else:
            camera_info['has_gps'] = False
        
        # Software used
        camera_info['software'] = exif.get('Software', '')
        
        return camera_info
    
    def _detect_editing_software(self, metadata: Dict) -> Dict:
        """Detect editing software from metadata"""
        editing_info = {
            'detected_software': [],
            'confidence_level': 'low',
            'editing_indicators': []
        }
        
        exif = metadata.get('exif', {})
        software = exif.get('Software', '').lower()
        
        # Check for known editing software
        for software_name, signatures in self.editing_software_signatures.items():
            for signature in signatures:
                if signature.lower() in software:
                    editing_info['detected_software'].append({
                        'name': software_name,
                        'signature': signature,
                        'confidence': 'high'
                    })
        
        # Check for editing indicators
        if exif.get('ColorSpace') == '65535':  # Uncalibrated color space
            editing_info['editing_indicators'].append('uncalibrated_color_space')
        
        if exif.get('WhiteBalance') and exif.get('WhiteBalance') != '0':
            editing_info['editing_indicators'].append('manual_white_balance')
        
        # Check for multiple software signatures
        if len(editing_info['detected_software']) > 1:
            editing_info['editing_indicators'].append('multiple_software_signatures')
            editing_info['confidence_level'] = 'high'
        elif len(editing_info['detected_software']) == 1:
            editing_info['confidence_level'] = 'medium'
        
        return editing_info
    
    def _generate_perceptual_hash(self, file_path: str) -> str:
        """Generate perceptual hash for similarity detection"""
        try:
            with Image.open(file_path) as img:
                # Convert to RGB if necessary
                if img.mode != 'RGB':
                    img = img.convert('RGB')
                
                # Generate average hash (good for detecting similar images)
                hash_value = imagehash.average_hash(img, hash_size=16)
                return str(hash_value)
                
        except Exception as e:
            logger.warning(f"Failed to generate perceptual hash for {file_path}: {e}")
            return ""
    
    def _check_similarity(self, perceptual_hash: str, current_image_id: str, current_image_type: str) -> List[Dict]:
        """Check for similar images using perceptual hash"""
        if not perceptual_hash:
            return []
        
        try:
            cursor = self.db_connection.cursor(dictionary=True)
            
            # Get all existing perceptual hashes
            cursor.execute("""
                SELECT image_id, image_type, perceptual_hash, file_path, created_at
                FROM image_authenticity_metadata 
                WHERE perceptual_hash IS NOT NULL 
                AND NOT (image_id = %s AND image_type = %s)
                ORDER BY created_at DESC
            """, (current_image_id, current_image_type))
            
            existing_hashes = cursor.fetchall()
            cursor.close()
            
            similar_images = []
            current_hash = imagehash.hex_to_hash(perceptual_hash)
            
            for record in existing_hashes:
                try:
                    existing_hash = imagehash.hex_to_hash(record['perceptual_hash'])
                    similarity = 1 - (current_hash - existing_hash) / len(current_hash.hash) ** 2
                    
                    if similarity >= self.thresholds['similarity_threshold']:
                        similar_images.append({
                            'image_id': record['image_id'],
                            'image_type': record['image_type'],
                            'similarity_score': round(similarity, 4),
                            'file_path': record['file_path'],
                            'created_at': record['created_at'].isoformat() if record['created_at'] else None
                        })
                        
                except Exception as e:
                    logger.warning(f"Error comparing hash with {record['image_id']}: {e}")
                    continue
            
            return similar_images
            
        except Exception as e:
            logger.error(f"Similarity check failed: {e}")
            return []
    
    def _calculate_authenticity_score(self, metadata: Dict, camera_info: Dict, 
                                    editing_software: Dict, similarity_matches: List[Dict]) -> Tuple[float, List[str]]:
        """Calculate overall authenticity score and flagged reasons"""
        score = 0.0
        reasons = []
        
        # Check for missing camera information (20 points)
        if not camera_info.get('make') and not camera_info.get('model'):
            score += 20
            reasons.append(self.suspicious_patterns['missing_camera_info'])
        
        # Check for editing software (15-30 points based on type)
        detected_software = editing_software.get('detected_software', [])
        if detected_software:
            for software in detected_software:
                if software['name'] in ['Adobe Photoshop', 'GIMP', 'Canva']:
                    score += 15
                    reasons.append(f"Professional editing software detected: {software['name']}")
                elif software['name'] in ['PicsArt', 'Facetune', 'VSCO']:
                    score += 25
                    reasons.append(f"Mobile editing app detected: {software['name']}")
        
        # Check for multiple editing software (additional 15 points)
        if len(detected_software) > 1:
            score += 15
            reasons.append(self.suspicious_patterns['multiple_edits'])
        
        # Check for similarity matches (30-50 points based on similarity)
        if similarity_matches:
            max_similarity = max(match['similarity_score'] for match in similarity_matches)
            if max_similarity >= 0.95:
                score += 50
                reasons.append(f"Near-identical image found (similarity: {max_similarity:.2%})")
            elif max_similarity >= 0.85:
                score += 30
                reasons.append(f"Very similar image found (similarity: {max_similarity:.2%})")
        
        # Check timestamp consistency (10 points)
        created_time = metadata.get('created_time')
        modified_time = metadata.get('modified_time')
        datetime_original = camera_info.get('datetime_original')
        
        if created_time and modified_time and datetime_original:
            try:
                created_dt = datetime.fromisoformat(created_time.replace('Z', '+00:00'))
                modified_dt = datetime.fromisoformat(modified_time.replace('Z', '+00:00'))
                
                # If modification time is significantly different from creation time
                time_diff = abs((modified_dt - created_dt).total_seconds())
                if time_diff > 3600:  # More than 1 hour difference
                    score += 10
                    reasons.append(self.suspicious_patterns['timestamp_mismatch'])
                    
            except Exception:
                pass
        
        # Check image dimensions for unusual patterns (5-10 points)
        width = metadata.get('width', 0)
        height = metadata.get('height', 0)
        
        if width and height:
            aspect_ratio = width / height
            # Very unusual aspect ratios might indicate cropping/editing
            if aspect_ratio > 3 or aspect_ratio < 0.3:
                score += 5
                reasons.append("Unusual aspect ratio detected")
            
            # Check for common screenshot dimensions
            if (width, height) in [(1920, 1080), (1366, 768), (1280, 720)]:
                score += 10
                reasons.append("Screenshot dimensions detected")
        
        # Check file size vs dimensions (potential compression/quality issues)
        file_size = metadata.get('file_size', 0)
        if width and height and file_size:
            pixels = width * height
            bytes_per_pixel = file_size / pixels if pixels > 0 else 0
            
            # Very low bytes per pixel might indicate heavy compression or upscaling
            if bytes_per_pixel < 0.5:
                score += 8
                reasons.append("Unusually low file size for image dimensions")
        
        return min(score, 100.0), reasons  # Cap at 100
    
    def _determine_risk_level(self, authenticity_score: float) -> str:
        """Determine risk level based on authenticity score"""
        if authenticity_score >= self.thresholds['highly_suspicious_score_threshold']:
            return 'highly_suspicious'
        elif authenticity_score >= self.thresholds['suspicious_score_threshold']:
            return 'suspicious'
        else:
            return 'clean'
    
    def _store_authenticity_metadata(self, image_id: str, image_type: str, file_path: str, 
                                   file_hash: str, perceptual_hash: str, result: Dict):
        """Store authenticity metadata in database"""
        try:
            cursor = self.db_connection.cursor()
            
            # Get file info
            stat = os.stat(file_path)
            original_filename = os.path.basename(file_path)
            mime_type = result['metadata_extracted'].get('mime_type', '')
            
            cursor.execute("""
                INSERT INTO image_authenticity_metadata 
                (image_id, image_type, file_path, original_filename, file_size, mime_type,
                 image_hash, perceptual_hash, metadata_extracted, camera_info, editing_software,
                 authenticity_score, risk_level, verification_status, verification_method, similarity_matches)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                image_hash = VALUES(image_hash),
                perceptual_hash = VALUES(perceptual_hash),
                metadata_extracted = VALUES(metadata_extracted),
                camera_info = VALUES(camera_info),
                editing_software = VALUES(editing_software),
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                verification_status = VALUES(verification_status),
                similarity_matches = VALUES(similarity_matches),
                updated_at = CURRENT_TIMESTAMP
            """, (
                image_id, image_type, file_path, original_filename, stat.st_size, mime_type,
                file_hash, perceptual_hash, 
                json.dumps(result['metadata_extracted']),
                json.dumps(result['camera_info']),
                json.dumps(result['editing_software']),
                result['authenticity_score'], result['risk_level'], result['verification_status'],
                'automated', json.dumps(result['similarity_matches'])
            ))
            
            cursor.close()
            
        except Exception as e:
            logger.error(f"Failed to store authenticity metadata: {e}")
    
    def _add_to_admin_review_queue(self, image_id: str, image_type: str, user_id: int, 
                                 tutorial_id: Optional[int], result: Dict):
        """Add flagged image to admin review queue"""
        try:
            cursor = self.db_connection.cursor()
            
            cursor.execute("""
                INSERT INTO admin_review_queue 
                (image_id, image_type, user_id, tutorial_id, authenticity_score, 
                 risk_level, flagged_reasons, admin_decision)
                VALUES (%s, %s, %s, %s, %s, %s, %s, 'pending')
                ON DUPLICATE KEY UPDATE
                authenticity_score = VALUES(authenticity_score),
                risk_level = VALUES(risk_level),
                flagged_reasons = VALUES(flagged_reasons),
                flagged_at = CURRENT_TIMESTAMP
            """, (
                image_id, image_type, user_id, tutorial_id,
                result['authenticity_score'], result['risk_level'],
                json.dumps(result['flagged_reasons'])
            ))
            
            cursor.close()
            
        except Exception as e:
            logger.error(f"Failed to add to admin review queue: {e}")
    
    def _log_audit_action(self, image_id: str, image_type: str, action: str, 
                         old_status: Optional[str], new_status: Optional[str], 
                         performed_by: Optional[int], performed_by_type: str):
        """Log audit action"""
        try:
            cursor = self.db_connection.cursor()
            
            cursor.execute("""
                INSERT INTO authenticity_audit_log 
                (image_id, image_type, action, old_status, new_status, 
                 performed_by, performed_by_type, details)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
            """, (
                image_id, image_type, action, old_status, new_status,
                performed_by, performed_by_type, json.dumps({
                    'timestamp': datetime.now().isoformat(),
                    'action_details': f"{action} performed by {performed_by_type}"
                })
            ))
            
            cursor.close()
            
        except Exception as e:
            logger.error(f"Failed to log audit action: {e}")
    
    def process_queue(self, limit: int = 10) -> Dict:
        """Process images from verification queue"""
        try:
            cursor = self.db_connection.cursor(dictionary=True)
            
            # Get queued images
            cursor.execute("""
                SELECT * FROM image_verification_queue 
                WHERE status = 'queued' 
                ORDER BY priority DESC, queued_at ASC 
                LIMIT %s
            """, (limit,))
            
            queued_images = cursor.fetchall()
            cursor.close()
            
            results = {
                'processed': 0,
                'successful': 0,
                'failed': 0,
                'details': []
            }
            
            for queue_item in queued_images:
                try:
                    # Update status to processing
                    self._update_queue_status(queue_item['id'], 'processing')
                    
                    # Process the image
                    result = self.verify_image(
                        queue_item['image_id'],
                        queue_item['image_type'],
                        queue_item['file_path'],
                        queue_item['user_id'],
                        queue_item['tutorial_id']
                    )
                    
                    if result['verification_status'] != 'failed':
                        self._update_queue_status(queue_item['id'], 'completed')
                        results['successful'] += 1
                    else:
                        self._update_queue_status(queue_item['id'], 'failed', 
                                                str(result.get('processing_errors', [])))
                        results['failed'] += 1
                    
                    results['details'].append({
                        'image_id': queue_item['image_id'],
                        'status': result['verification_status'],
                        'score': result['authenticity_score']
                    })
                    
                except Exception as e:
                    logger.error(f"Failed to process queue item {queue_item['id']}: {e}")
                    self._update_queue_status(queue_item['id'], 'failed', str(e))
                    results['failed'] += 1
                
                results['processed'] += 1
            
            return results
            
        except Exception as e:
            logger.error(f"Queue processing failed: {e}")
            return {'error': str(e)}
    
    def _update_queue_status(self, queue_id: int, status: str, error_message: Optional[str] = None):
        """Update queue item status"""
        try:
            cursor = self.db_connection.cursor()
            
            if status == 'completed' or status == 'failed':
                cursor.execute("""
                    UPDATE image_verification_queue 
                    SET status = %s, processed_at = CURRENT_TIMESTAMP, error_message = %s
                    WHERE id = %s
                """, (status, error_message, queue_id))
            else:
                cursor.execute("""
                    UPDATE image_verification_queue 
                    SET status = %s, error_message = %s
                    WHERE id = %s
                """, (status, error_message, queue_id))
            
            cursor.close()
            
        except Exception as e:
            logger.error(f"Failed to update queue status: {e}")

def main():
    """Main entry point for command line usage"""
    if len(sys.argv) < 2:
        print("Usage: python image_authenticity_service.py <command> [args...]")
        print("Commands:")
        print("  verify <image_id> <image_type> <file_path> <user_id> [tutorial_id]")
        print("  process_queue [limit]")
        return
    
    service = ImageAuthenticityService()
    command = sys.argv[1]
    
    if command == 'verify':
        if len(sys.argv) < 6:
            print("Usage: verify <image_id> <image_type> <file_path> <user_id> [tutorial_id]")
            return
        
        image_id = sys.argv[2]
        image_type = sys.argv[3]
        file_path = sys.argv[4]
        user_id = int(sys.argv[5])
        tutorial_id = int(sys.argv[6]) if len(sys.argv) > 6 else None
        
        result = service.verify_image(image_id, image_type, file_path, user_id, tutorial_id)
        print(json.dumps(result, indent=2))
        
    elif command == 'process_queue':
        limit = int(sys.argv[2]) if len(sys.argv) > 2 else 10
        result = service.process_queue(limit)
        print(json.dumps(result, indent=2))
        
    else:
        print(f"Unknown command: {command}")

if __name__ == '__main__':
    main()