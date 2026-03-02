#!/usr/bin/env python3
"""
Enhanced Flask API for AI-Assisted Craft Validation
Provides comprehensive REST endpoints for academic research and demonstration
"""

import os
import sys
import json
import time
import tempfile
import hashlib
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from enhanced_craft_classifier import EnhancedCraftClassifier
import logging

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

# Initialize Flask app
app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB max file size

# Initialize classifier
try:
    classifier = EnhancedCraftClassifier()
    classifier_available = True
    logger.info("Enhanced Craft Classifier initialized successfully")
except Exception as e:
    logger.error(f"Failed to initialize classifier: {e}")
    classifier = None
    classifier_available = False

# Allowed file extensions
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'}

def allowed_file(filename):
    """Check if file extension is allowed"""
    return '.' in filename and filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

def generate_request_id():
    """Generate unique request ID for tracking"""
    return hashlib.md5(f"{time.time()}_{os.getpid()}".encode()).hexdigest()[:12]

@app.route('/health', methods=['GET'])
def health_check():
    """Comprehensive health check endpoint"""
    stats = classifier.get_performance_stats() if classifier_available else {}
    
    return jsonify({
        'status': 'healthy' if classifier_available else 'degraded',
        'service': 'enhanced_craft_validation_api',
        'version': '2.0.0',
        'classifier_available': classifier_available,
        'has_fine_tuned_model': stats.get('has_fine_tuned_model', False),
        'model_path': stats.get('model_path'),
        'performance_stats': stats,
        'supported_categories': list(EnhancedCraftClassifier.CATEGORY_NAMES.keys()) if classifier_available else [],
        'api_endpoints': [
            'GET /health - Health check and statistics',
            'POST /classify-craft - Single image classification',
            'POST /classify-batch - Batch image classification',
            'POST /validate-practice - Complete practice validation',
            'GET /categories - Get supported categories',
            'GET /stats - Get detailed statistics'
        ],
        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
    })

@app.route('/classify-craft', methods=['POST'])
def classify_craft():
    """
    Enhanced craft classification endpoint
    
    Accepts:
    - JSON with image_path
    - File upload via multipart/form-data
    
    Returns comprehensive classification results with explanations
    """
    request_id = generate_request_id()
    logger.info(f"[{request_id}] Craft classification request received")
    
    if not classifier_available:
        return jsonify({
            'success': False,
            'error_code': 'CLASSIFIER_NOT_AVAILABLE',
            'error_message': 'Enhanced craft classifier failed to initialize',
            'request_id': request_id
        }), 503
    
    image_path = None
    temp_file = None
    
    try:
        # Handle JSON request with image path
        if request.is_json:
            data = request.get_json()
            if not data or 'image_path' not in data:
                return jsonify({
                    'success': False,
                    'error_code': 'MISSING_IMAGE_PATH',
                    'error_message': 'image_path is required in JSON request',
                    'request_id': request_id
                }), 400
            
            image_path = data['image_path']
            explain_decision = data.get('explain_decision', True)
            
            if not os.path.exists(image_path):
                return jsonify({
                    'success': False,
                    'error_code': 'FILE_NOT_FOUND',
                    'error_message': f'Image file not found: {image_path}',
                    'request_id': request_id
                }), 400
        
        # Handle file upload
        elif 'image' in request.files:
            file = request.files['image']
            explain_decision = request.form.get('explain_decision', 'true').lower() == 'true'
            
            if file.filename == '':
                return jsonify({
                    'success': False,
                    'error_code': 'NO_FILE_SELECTED',
                    'error_message': 'No file selected',
                    'request_id': request_id
                }), 400
            
            if not allowed_file(file.filename):
                return jsonify({
                    'success': False,
                    'error_code': 'INVALID_FILE_TYPE',
                    'error_message': f'File type not allowed. Supported: {", ".join(ALLOWED_EXTENSIONS)}',
                    'request_id': request_id
                }), 400
            
            # Save uploaded file to temporary location
            filename = secure_filename(file.filename)
            temp_file = tempfile.NamedTemporaryFile(delete=False, suffix=os.path.splitext(filename)[1])
            file.save(temp_file.name)
            image_path = temp_file.name
        
        else:
            return jsonify({
                'success': False,
                'error_code': 'NO_IMAGE_PROVIDED',
                'error_message': 'No image provided. Send JSON with image_path or upload file',
                'request_id': request_id
            }), 400
        
        # Perform classification
        logger.info(f"[{request_id}] Classifying image: {os.path.basename(image_path)}")
        result = classifier.classify_craft(image_path, explain_decision=explain_decision)
        
        # Add request tracking
        result['request_id'] = request_id
        result['api_version'] = '2.0.0'
        
        # Clean up temporary file
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        logger.info(f"[{request_id}] Classification completed successfully")
        return jsonify(result)
    
    except Exception as e:
        logger.error(f"[{request_id}] Classification exception: {e}")
        
        # Clean up temporary file on error
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        return jsonify({
            'success': False,
            'error_code': 'CLASSIFICATION_EXCEPTION',
            'error_message': str(e),
            'request_id': request_id
        }), 500

@app.route('/classify-batch', methods=['POST'])
def classify_batch():
    """
    Batch classification endpoint for multiple images
    """
    request_id = generate_request_id()
    logger.info(f"[{request_id}] Batch classification request received")
    
    if not classifier_available:
        return jsonify({
            'success': False,
            'error_code': 'CLASSIFIER_NOT_AVAILABLE',
            'error_message': 'Enhanced craft classifier failed to initialize',
            'request_id': request_id
        }), 503
    
    try:
        if not request.is_json:
            return jsonify({
                'success': False,
                'error_code': 'JSON_REQUIRED',
                'error_message': 'JSON request required for batch classification',
                'request_id': request_id
            }), 400
        
        data = request.get_json()
        if not data or 'image_paths' not in data:
            return jsonify({
                'success': False,
                'error_code': 'MISSING_IMAGE_PATHS',
                'error_message': 'image_paths array is required',
                'request_id': request_id
            }), 400
        
        image_paths = data['image_paths']
        if not isinstance(image_paths, list):
            return jsonify({
                'success': False,
                'error_code': 'INVALID_IMAGE_PATHS',
                'error_message': 'image_paths must be an array',
                'request_id': request_id
            }), 400
        
        # Limit batch size for performance
        if len(image_paths) > 20:
            return jsonify({
                'success': False,
                'error_code': 'BATCH_TOO_LARGE',
                'error_message': 'Maximum 20 images per batch',
                'request_id': request_id
            }), 400
        
        explain_decision = data.get('explain_decision', True)
        
        # Process all images
        results = []
        start_time = time.time()
        
        for i, image_path in enumerate(image_paths):
            try:
                if not os.path.exists(image_path):
                    results.append({
                        'success': False,
                        'error_message': f'File not found: {image_path}',
                        'image_path': image_path,
                        'batch_index': i
                    })
                    continue
                
                result = classifier.classify_craft(image_path, explain_decision=explain_decision)
                result['batch_index'] = i
                results.append(result)
                
            except Exception as e:
                logger.error(f"[{request_id}] Batch item {i} error: {e}")
                results.append({
                    'success': False,
                    'error_message': str(e),
                    'image_path': image_path,
                    'batch_index': i
                })
        
        total_time = time.time() - start_time
        
        return jsonify({
            'success': True,
            'request_id': request_id,
            'batch_size': len(image_paths),
            'results': results,
            'processing_time': total_time,
            'successful_classifications': sum(1 for r in results if r.get('success', False)),
            'failed_classifications': sum(1 for r in results if not r.get('success', False))
        })
    
    except Exception as e:
        logger.error(f"[{request_id}] Batch classification exception: {e}")
        return jsonify({
            'success': False,
            'error_code': 'BATCH_CLASSIFICATION_EXCEPTION',
            'error_message': str(e),
            'request_id': request_id
        }), 500

@app.route('/validate-practice', methods=['POST'])
def validate_practice():
    """
    Comprehensive practice image validation endpoint
    
    Combines craft classification with validation rules for academic demonstration
    """
    request_id = generate_request_id()
    logger.info(f"[{request_id}] Practice validation request received")
    
    if not classifier_available:
        return jsonify({
            'success': False,
            'error_code': 'CLASSIFIER_NOT_AVAILABLE',
            'error_message': 'Enhanced craft classifier failed to initialize',
            'request_id': request_id
        }), 503
    
    try:
        if not request.is_json:
            return jsonify({
                'success': False,
                'error_code': 'JSON_REQUIRED',
                'error_message': 'JSON request required',
                'request_id': request_id
            }), 400
        
        data = request.get_json()
        required_fields = ['image_path', 'selected_category', 'tutorial_id']
        
        for field in required_fields:
            if field not in data:
                return jsonify({
                    'success': False,
                    'error_code': 'MISSING_FIELD',
                    'error_message': f'Required field missing: {field}',
                    'request_id': request_id
                }), 400
        
        image_path = data['image_path']
        selected_category = data['selected_category']
        tutorial_id = data['tutorial_id']
        user_id = data.get('user_id')
        
        # Perform classification
        classification_result = classifier.classify_craft(image_path, explain_decision=True)
        
        if not classification_result['success']:
            return jsonify({
                'success': False,
                'error_code': 'CLASSIFICATION_FAILED',
                'error_message': classification_result.get('error_message', 'Classification failed'),
                'request_id': request_id
            }), 400
        
        # Perform validation analysis
        validation_result = perform_comprehensive_validation(
            classification_result, selected_category, tutorial_id, user_id, request_id
        )
        
        # Combine results
        result = {
            'success': True,
            'request_id': request_id,
            'classification': classification_result,
            'validation': validation_result,
            'tutorial_id': tutorial_id,
            'selected_category': selected_category,
            'user_id': user_id,
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        
        logger.info(f"[{request_id}] Practice validation completed: {validation_result['recommendation']}")
        return jsonify(result)
    
    except Exception as e:
        logger.error(f"[{request_id}] Practice validation exception: {e}")
        return jsonify({
            'success': False,
            'error_code': 'VALIDATION_EXCEPTION',
            'error_message': str(e),
            'request_id': request_id
        }), 500

def perform_comprehensive_validation(classification_result, selected_category, tutorial_id, user_id, request_id):
    """
    Comprehensive validation logic for academic demonstration
    """
    logger.info(f"[{request_id}] Performing comprehensive validation analysis")
    
    validation = {
        'status': 'approved',
        'recommendation': 'approve',
        'confidence_level': 'unknown',
        'category_match': False,
        'reasons': [],
        'warnings': [],
        'decision_factors': {},
        'academic_insights': {}
    }
    
    try:
        predicted_category = classification_result.get('predicted_category', '')
        confidence = classification_result.get('confidence', 0.0)
        is_craft_related = classification_result.get('is_craft_related', True)
        non_craft_confidence = classification_result.get('non_craft_confidence', 0.0)
        
        # Normalize category names for comparison
        selected_normalized = selected_category.lower().replace(' ', '_').replace('/', '_')
        predicted_normalized = predicted_category.lower().replace(' ', '_').replace('/', '_')
        
        # Category matching analysis
        validation['category_match'] = (selected_normalized == predicted_normalized)
        
        # Confidence level assessment
        if confidence >= 0.8:
            validation['confidence_level'] = 'high'
        elif confidence >= 0.5:
            validation['confidence_level'] = 'medium'
        elif confidence >= 0.3:
            validation['confidence_level'] = 'low'
        else:
            validation['confidence_level'] = 'very_low'
        
        # Decision factors for academic analysis
        validation['decision_factors'] = {
            'craft_classification_confidence': confidence,
            'category_match_score': 1.0 if validation['category_match'] else 0.0,
            'craft_relatedness_score': 1.0 if is_craft_related else 0.0,
            'non_craft_confidence': non_craft_confidence
        }
        
        # Apply validation rules
        
        # Rule 1: Non-craft content with high confidence
        if not is_craft_related and non_craft_confidence >= 0.7:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['reasons'].append(f'Image contains non-craft content (confidence: {non_craft_confidence:.1%})')
        
        # Rule 2: Category mismatch with high confidence
        elif not validation['category_match'] and confidence >= 0.75:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['reasons'].append(f'High-confidence category mismatch: predicted {predicted_category}, selected {selected_category}')
        
        # Rule 3: Category mismatch with medium confidence
        elif not validation['category_match'] and confidence >= 0.5:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['reasons'].append(f'Possible category mismatch (confidence: {confidence:.1%})')
        
        # Rule 4: Very low confidence classification
        elif confidence < 0.2:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['reasons'].append('Very low confidence in classification')
        
        # Rule 5: Non-craft content with medium confidence
        elif not is_craft_related and non_craft_confidence >= 0.4:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['warnings'].append(f'Possible non-craft content detected (confidence: {non_craft_confidence:.1%})')
        
        # Rule 6: Approved cases
        else:
            validation['status'] = 'approved'
            validation['recommendation'] = 'approve'
            validation['reasons'].append('Image meets validation criteria')
            
            if validation['category_match']:
                validation['reasons'].append('Category matches selected tutorial')
        
        # Academic insights for research purposes
        validation['academic_insights'] = {
            'model_performance': {
                'model_used': classification_result.get('model_used', 'unknown'),
                'has_fine_tuned_model': classification_result.get('has_fine_tuned_model', False),
                'processing_time': classification_result.get('processing_time', 0.0)
            },
            'classification_details': {
                'top_predictions': classification_result.get('all_predictions', [])[:3],
                'imagenet_predictions': classification_result.get('imagenet_predictions', [])[:3],
                'non_craft_evidence': classification_result.get('non_craft_evidence', [])[:3]
            },
            'decision_explanation': classification_result.get('explanation', ''),
            'validation_pipeline': {
                'rules_evaluated': 6,
                'final_rule_triggered': validation['reasons'][-1] if validation['reasons'] else 'default_approval'
            }
        }
    
    except Exception as e:
        logger.error(f"[{request_id}] Validation logic error: {e}")
        validation['status'] = 'error'
        validation['recommendation'] = 'review'
        validation['reasons'].append(f'Validation error: {str(e)}')
    
    return validation

@app.route('/categories', methods=['GET'])
def get_categories():
    """Get supported craft categories"""
    if not classifier_available:
        return jsonify({
            'success': False,
            'error_message': 'Classifier not available'
        }), 503
    
    return jsonify({
        'success': True,
        'categories': EnhancedCraftClassifier.CATEGORY_NAMES,
        'category_keys': list(EnhancedCraftClassifier.CATEGORY_NAMES.keys()),
        'total_categories': len(EnhancedCraftClassifier.CATEGORY_NAMES),
        'craft_keywords': EnhancedCraftClassifier.CRAFT_KEYWORDS
    })

@app.route('/stats', methods=['GET'])
def get_detailed_stats():
    """Get detailed classifier statistics for academic analysis"""
    if not classifier_available:
        return jsonify({
            'success': False,
            'error_message': 'Classifier not available'
        }), 503
    
    stats = classifier.get_performance_stats()
    
    return jsonify({
        'success': True,
        'performance_stats': stats,
        'system_info': {
            'api_version': '2.0.0',
            'service_uptime': time.time(),
            'supported_formats': list(ALLOWED_EXTENSIONS),
            'max_file_size': '16MB',
            'max_batch_size': 20
        },
        'model_info': {
            'has_fine_tuned_model': stats.get('has_fine_tuned_model', False),
            'model_path': stats.get('model_path'),
            'supported_categories': len(EnhancedCraftClassifier.CATEGORY_NAMES),
            'non_craft_indicators': len(EnhancedCraftClassifier.NON_CRAFT_INDICATORS)
        },
        'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
    })

@app.errorhandler(413)
def too_large(e):
    """Handle file too large error"""
    return jsonify({
        'success': False,
        'error_code': 'FILE_TOO_LARGE',
        'error_message': 'File too large. Maximum size is 16MB'
    }), 413

@app.errorhandler(404)
def not_found(e):
    """Handle 404 errors"""
    return jsonify({
        'success': False,
        'error_code': 'ENDPOINT_NOT_FOUND',
        'error_message': 'API endpoint not found'
    }), 404

@app.errorhandler(500)
def internal_error(e):
    """Handle internal server errors"""
    return jsonify({
        'success': False,
        'error_code': 'INTERNAL_SERVER_ERROR',
        'error_message': 'Internal server error occurred'
    }), 500

if __name__ == '__main__':
    # Get configuration from environment
    port = int(os.environ.get('PORT', 5001))
    host = os.environ.get('HOST', '127.0.0.1')
    debug = os.environ.get('DEBUG', 'false').lower() == 'true'
    
    logger.info(f"Starting Enhanced Craft Validation API on {host}:{port}")
    logger.info("Available endpoints:")
    logger.info("  GET  /health - Health check and statistics")
    logger.info("  POST /classify-craft - Enhanced craft classification")
    logger.info("  POST /classify-batch - Batch classification")
    logger.info("  POST /validate-practice - Complete practice validation")
    logger.info("  GET  /categories - Get supported categories")
    logger.info("  GET  /stats - Get detailed statistics")
    
    app.run(host=host, port=port, debug=debug)