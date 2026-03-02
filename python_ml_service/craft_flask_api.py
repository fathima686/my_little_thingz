#!/usr/bin/env python3
"""
Flask API for Craft Image Classification Service - Production Version
Provides REST endpoints for craft-specific image validation using ONLY the trained .keras model
"""

import os
import sys
import json
import tempfile
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from craft_classifier import CraftImageClassifier

# Try to import AI detector (optional dependency)
try:
    from ai_image_detector import AIImageDetector
    AI_DETECTOR_IMPORT_SUCCESS = True
except ImportError as e:
    print(f"⚠ WARNING: AI detector import failed: {e}", file=sys.stderr)
    print("⚠ Service will continue without AI detection capability", file=sys.stderr)
    print("⚠ To enable AI detection, install: pip install opencv-python", file=sys.stderr)
    AIImageDetector = None
    AI_DETECTOR_IMPORT_SUCCESS = False

# Initialize Flask app
app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 10 * 1024 * 1024  # 10MB max file size

# Initialize classifier - STRICT MODE: Service fails if model can't load
print("=== CRAFT CLASSIFICATION API - PRODUCTION MODE ===", file=sys.stderr)
try:
    classifier = CraftImageClassifier()
    print("✓ Craft classifier initialized successfully", file=sys.stderr)
    classifier_available = True
except Exception as e:
    print(f"✗ CRITICAL ERROR: Failed to initialize craft classifier: {e}", file=sys.stderr)
    print("✗ Service terminating - trained model is required", file=sys.stderr)
    sys.exit(1)  # Terminate service immediately

# Initialize AI image detector
try:
    if AI_DETECTOR_IMPORT_SUCCESS and AIImageDetector is not None:
        ai_detector = AIImageDetector()
        print("✓ AI image detector initialized successfully", file=sys.stderr)
        ai_detector_available = True
    else:
        ai_detector = None
        ai_detector_available = False
        print("⚠ AI image detector not available (import failed)", file=sys.stderr)
except Exception as e:
    print(f"⚠ WARNING: AI image detector initialization failed: {e}", file=sys.stderr)
    print("⚠ Service will continue without AI detection", file=sys.stderr)
    ai_detector = None
    ai_detector_available = False

# Allowed file extensions
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp'}

def allowed_file(filename):
    """Check if file extension is allowed"""
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in ALLOWED_EXTENSIONS

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'craft_image_classification_production',
        'model': 'Trained craft_image_classifier.keras',
        'version': '2.0.0',
        'classifier_available': True,  # Always true in production mode
        'model_type': 'trained_keras_model',
        'fallback_disabled': True,
        'ai_detector_available': ai_detector_available,
        'supported_categories': [
            'candle_making',
            'clay_modeling', 
            'gift_making',
            'hand_embroidery',
            'jewelry_making',
            'mehandi_art',
            'resin_art'
        ]
    })

@app.route('/classify-craft', methods=['POST'])
def classify_craft():
    """
    Classify image into craft categories using ONLY the trained model
    
    Accepts:
    - JSON with image_path
    - File upload via multipart/form-data
    
    Returns:
    - Craft category predictions from trained model
    - Confidence scores
    - Deterministic results
    """
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
                    'error_message': 'image_path is required in JSON request'
                }), 400
            
            image_path = data['image_path']
            
            # Validate file exists
            if not os.path.exists(image_path):
                return jsonify({
                    'success': False,
                    'error_code': 'FILE_NOT_FOUND',
                    'error_message': f'Image file not found: {image_path}'
                }), 400
        
        # Handle file upload
        elif 'image' in request.files:
            file = request.files['image']
            
            if file.filename == '':
                return jsonify({
                    'success': False,
                    'error_code': 'NO_FILE_SELECTED',
                    'error_message': 'No file selected'
                }), 400
            
            if not allowed_file(file.filename):
                return jsonify({
                    'success': False,
                    'error_code': 'INVALID_FILE_TYPE',
                    'error_message': 'File type not allowed. Supported: ' + ', '.join(ALLOWED_EXTENSIONS)
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
                'error_message': 'No image provided. Send JSON with image_path or upload file'
            }), 400
        
        # Classify image using trained model
        result = classifier.classify_craft(image_path)
        
        # Clean up temporary file
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        return jsonify(result)
    
    except Exception as e:
        # Clean up temporary file on error
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        return jsonify({
            'success': False,
            'error_code': 'CLASSIFICATION_EXCEPTION',
            'error_message': str(e)
        }), 500

@app.route('/classify-craft/batch', methods=['POST'])
def classify_craft_batch():
    """
    Classify multiple images into craft categories using trained model
    
    Accepts JSON with array of image_paths
    
    Returns array of classification results
    """
    try:
        if not request.is_json:
            return jsonify({
                'success': False,
                'error_code': 'JSON_REQUIRED',
                'error_message': 'JSON request required for batch classification'
            }), 400
        
        data = request.get_json()
        if not data or 'image_paths' not in data:
            return jsonify({
                'success': False,
                'error_code': 'MISSING_IMAGE_PATHS',
                'error_message': 'image_paths array is required'
            }), 400
        
        image_paths = data['image_paths']
        if not isinstance(image_paths, list):
            return jsonify({
                'success': False,
                'error_code': 'INVALID_IMAGE_PATHS',
                'error_message': 'image_paths must be an array'
            }), 400
        
        # Limit batch size
        if len(image_paths) > 10:
            return jsonify({
                'success': False,
                'error_code': 'BATCH_TOO_LARGE',
                'error_message': 'Maximum 10 images per batch'
            }), 400
        
        # Classify all images using trained model
        results = classifier.classify_batch(image_paths)
        
        return jsonify({
            'success': True,
            'results': results,
            'batch_size': len(results),
            'model_used': 'trained_keras_model'
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'BATCH_CLASSIFICATION_EXCEPTION',
            'error_message': str(e)
        }), 500

@app.route('/categories', methods=['GET'])
def get_categories():
    """Get supported craft categories"""
    return jsonify({
        'success': True,
        'categories': {
            'candle_making': 'Candle Making',
            'clay_modeling': 'Clay Modeling',
            'gift_making': 'Gift Making',
            'hand_embroidery': 'Hand Embroidery',
            'jewelry_making': 'Jewelry Making',
            'mehandi_art': 'Mylanchi / Mehandi Art',
            'resin_art': 'Resin Art'
        },
        'total_categories': 7
    })

@app.route('/detect-ai-image', methods=['POST'])
def detect_ai_image():
    """
    Detect if image is AI-generated using multi-layer analysis
    
    Accepts:
    - JSON with image_path
    - File upload via multipart/form-data
    
    Returns:
    - AI risk score (0-100)
    - Risk level (low/medium/high)
    - Detection evidence from all layers
    - Decision (pass/flag/reject)
    """
    if not ai_detector_available:
        return jsonify({
            'success': False,
            'error_code': 'AI_DETECTOR_UNAVAILABLE',
            'error_message': 'AI detection service not available'
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
                    'error_message': 'image_path is required in JSON request'
                }), 400
            
            image_path = data['image_path']
            
            # Validate file exists
            if not os.path.exists(image_path):
                return jsonify({
                    'success': False,
                    'error_code': 'FILE_NOT_FOUND',
                    'error_message': f'Image file not found: {image_path}'
                }), 400
        
        # Handle file upload
        elif 'image' in request.files:
            file = request.files['image']
            
            if file.filename == '':
                return jsonify({
                    'success': False,
                    'error_code': 'NO_FILE_SELECTED',
                    'error_message': 'No file selected'
                }), 400
            
            if not allowed_file(file.filename):
                return jsonify({
                    'success': False,
                    'error_code': 'INVALID_FILE_TYPE',
                    'error_message': 'File type not allowed. Supported: ' + ', '.join(ALLOWED_EXTENSIONS)
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
                'error_message': 'No image provided. Send JSON with image_path or upload file'
            }), 400
        
        # Perform AI detection
        result = ai_detector.analyze_image(image_path)
        
        # Clean up temporary file
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        return jsonify(result)
    
    except Exception as e:
        # Clean up temporary file on error
        if temp_file:
            try:
                os.unlink(temp_file.name)
            except:
                pass
        
        return jsonify({
            'success': False,
            'error_code': 'AI_DETECTION_EXCEPTION',
            'error_message': str(e)
        }), 500

@app.route('/validate-practice', methods=['POST'])
def validate_practice_image():
    """
    Comprehensive practice image validation using ONLY the trained model
    
    Combines craft classification with strict validation rules AND AI detection:
    - Category matching with selected tutorial
    - Confidence-based decisions
    - Deterministic auto-approve/auto-reject/flag logic
    - Multi-layer AI-generated image detection
    
    Expected JSON:
    {
        "image_path": "/path/to/image.jpg",
        "selected_category": "hand_embroidery",
        "tutorial_id": 123,
        "enable_ai_detection": true  // Optional, defaults to true
    }
    """
    try:
        if not request.is_json:
            return jsonify({
                'success': False,
                'error_code': 'JSON_REQUIRED',
                'error_message': 'JSON request required'
            }), 400
        
        data = request.get_json()
        required_fields = ['image_path', 'selected_category']
        
        for field in required_fields:
            if field not in data:
                return jsonify({
                    'success': False,
                    'error_code': 'MISSING_FIELD',
                    'error_message': f'Required field missing: {field}'
                }), 400
        
        image_path = data['image_path']
        selected_category = data['selected_category']
        tutorial_id = data.get('tutorial_id')
        enable_ai_detection = data.get('enable_ai_detection', True)
        
        # Classify image using trained model
        classification_result = classifier.classify_craft(image_path)
        
        if not classification_result['success']:
            return jsonify(classification_result), 400
        
        # Perform AI detection if enabled
        ai_detection_result = None
        if enable_ai_detection and ai_detector_available:
            ai_detection_result = ai_detector.analyze_image(image_path)
        
        # Perform strict validation logic
        validation_result = perform_strict_validation_logic(
            classification_result, 
            selected_category, 
            tutorial_id,
            ai_detection_result
        )
        
        # Combine results
        result = {
            'success': True,
            'classification': classification_result,
            'validation': validation_result,
            'ai_detection': ai_detection_result,
            'tutorial_id': tutorial_id,
            'selected_category': selected_category,
            'model_used': 'trained_keras_model',
            'fallback_disabled': True,
            'ai_detection_enabled': enable_ai_detection and ai_detector_available
        }
        
        return jsonify(result)
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'VALIDATION_EXCEPTION',
            'error_message': str(e)
        }), 500

def perform_strict_validation_logic(classification_result, selected_category, tutorial_id, ai_detection_result=None):
    """
    Perform strict validation logic based on trained model results
    Enforces deterministic auto-approve/auto-reject/flag decisions
    Integrates AI detection as additional validation layer
    """
    validation = {
        'status': 'approved',
        'category_match': False,
        'confidence_level': 'unknown',
        'recommendation': 'approve',
        'reasons': [],
        'warnings': [],
        'decision_type': 'auto-approve',  # auto-approve, auto-reject, or flag-for-review
        'ai_detection_applied': False
    }
    
    try:
        predicted_category = classification_result.get('predicted_category', '')
        confidence = classification_result.get('confidence', 0.0)
        is_craft_related = classification_result.get('is_craft_related', True)
        
        # Normalize category names for comparison
        selected_normalized = selected_category.lower().replace(' ', '_').replace('/', '_')
        predicted_normalized = predicted_category.lower().replace(' ', '_').replace('/', '_')
        
        # Check category match
        validation['category_match'] = (selected_normalized == predicted_normalized)
        
        # Determine confidence level
        if confidence >= 0.8:
            validation['confidence_level'] = 'high'
        elif confidence >= 0.5:
            validation['confidence_level'] = 'medium'
        elif confidence >= 0.2:
            validation['confidence_level'] = 'low'
        else:
            validation['confidence_level'] = 'very_low'
        
        # AI DETECTION LAYER - Check before craft validation
        if ai_detection_result and ai_detection_result.get('success'):
            validation['ai_detection_applied'] = True
            ai_decision = ai_detection_result.get('decision', 'pass')
            ai_risk_score = ai_detection_result.get('ai_risk_score', 0)
            
            # High risk AI detection - auto-reject
            if ai_decision == 'reject':
                validation['status'] = 'rejected'
                validation['recommendation'] = 'reject'
                validation['decision_type'] = 'auto-reject'
                validation['reasons'].append(f'AI-generated image detected (risk score: {ai_risk_score}/100)')
                validation['reasons'].append(ai_detection_result.get('explanation', ''))
                return validation
            
            # Medium risk AI detection - flag for review
            elif ai_decision == 'flag':
                validation['status'] = 'flagged'
                validation['recommendation'] = 'review'
                validation['decision_type'] = 'flag-for-review'
                validation['reasons'].append(f'Possible AI-generated image (risk score: {ai_risk_score}/100)')
                validation['warnings'].append('AI detection flagged this image for manual review')
                return validation
            
            # Low risk - continue with craft validation
            else:
                validation['warnings'].append(f'AI detection passed (risk score: {ai_risk_score}/100)')
        
        # CRAFT CATEGORY VALIDATION - VERY PERMISSIVE FOR CRAFT PRODUCTS
        
        # Rule 1: AUTO-REJECT - Very low confidence (likely not craft-related at all)
        if confidence < 0.2 or not is_craft_related:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['decision_type'] = 'auto-reject'
            validation['reasons'].append(f'Image appears unrelated to crafts (confidence: {confidence:.1%})')
            return validation
        
        # Rule 2: AUTO-REJECT - High confidence category mismatch
        if not validation['category_match'] and confidence >= 0.6:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['decision_type'] = 'auto-reject'
            validation['reasons'].append(f'Category mismatch: predicted {predicted_category} with {confidence:.1%} confidence, selected {selected_category}')
            return validation
        
        # Rule 3: AUTO-APPROVE - Good confidence category match
        if validation['category_match'] and confidence >= 0.4:
            validation['status'] = 'approved'
            validation['recommendation'] = 'approve'
            validation['decision_type'] = 'auto-approve'
            validation['reasons'].append(f'Good confidence category match: {predicted_category} ({confidence:.1%})')
            return validation
        
        # Rule 4: AUTO-APPROVE - Medium confidence category match (ACCEPTS CRAFT PRODUCTS)
        if validation['category_match'] and confidence >= 0.3:
            validation['status'] = 'approved'
            validation['recommendation'] = 'approve'
            validation['decision_type'] = 'auto-approve'
            validation['reasons'].append(f'Medium confidence category match - accepting craft products: {predicted_category} ({confidence:.1%})')
            return validation
        
        # Rule 5: FLAG FOR REVIEW - Low confidence but category match
        if validation['category_match'] and confidence >= 0.2:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['decision_type'] = 'flag-for-review'
            validation['reasons'].append(f'Low confidence category match needs review: {predicted_category} ({confidence:.1%})')
            return validation
        
        # Rule 6: AUTO-REJECT - Everything else
        validation['status'] = 'rejected'
        validation['recommendation'] = 'reject'
        validation['decision_type'] = 'auto-reject'
        validation['reasons'].append(f'Insufficient confidence or ambiguous classification: {confidence:.1%}')
        return validation
        
    except Exception as e:
        validation['status'] = 'error'
        validation['recommendation'] = 'review'
        validation['decision_type'] = 'flag-for-review'
        validation['reasons'].append(f'Validation error: {str(e)}')
    
    return validation

@app.errorhandler(413)
def too_large(e):
    """Handle file too large error"""
    return jsonify({
        'success': False,
        'error_code': 'FILE_TOO_LARGE',
        'error_message': 'File too large. Maximum size is 10MB'
    }), 413

@app.errorhandler(404)
def not_found(e):
    """Handle 404 errors"""
    return jsonify({
        'success': False,
        'error_code': 'ENDPOINT_NOT_FOUND',
        'error_message': 'Endpoint not found'
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
    # Get port from environment or default to 5001
    port = int(os.environ.get('PORT', 5001))
    
    # Get host from environment or default to localhost
    host = os.environ.get('HOST', '127.0.0.1')
    
    print(f"=== STARTING CRAFT CLASSIFICATION API - PRODUCTION MODE ===", file=sys.stderr)
    print(f"Server: {host}:{port}", file=sys.stderr)
    print("Available endpoints:", file=sys.stderr)
    print("  GET  /health - Health check", file=sys.stderr)
    print("  POST /classify-craft - Classify single image", file=sys.stderr)
    print("  POST /classify-craft/batch - Classify multiple images", file=sys.stderr)
    print("  POST /validate-practice - Comprehensive practice validation", file=sys.stderr)
    print("  POST /detect-ai-image - AI-generated image detection", file=sys.stderr)
    print("  GET  /categories - Get supported categories", file=sys.stderr)
    print("", file=sys.stderr)
    print("PRODUCTION FEATURES:", file=sys.stderr)
    print("✓ Trained craft_image_classifier.keras model ONLY", file=sys.stderr)
    print("✓ No fallback logic - deterministic results", file=sys.stderr)
    print("✓ Strict auto-approve/auto-reject/flag decisions", file=sys.stderr)
    print("✓ Service fails fast if model unavailable", file=sys.stderr)
    if ai_detector_available:
        print("✓ Multi-layer AI detection enabled (4 layers)", file=sys.stderr)
        print("  - Metadata analysis (AI keywords)", file=sys.stderr)
        print("  - EXIF camera metadata check", file=sys.stderr)
        print("  - Texture smoothness analysis", file=sys.stderr)
        print("  - Watermark detection", file=sys.stderr)
    else:
        print("⚠ AI detection unavailable - install opencv-python", file=sys.stderr)
    print("=== READY FOR ACADEMIC DEMONSTRATION ===", file=sys.stderr)
    
    app.run(host=host, port=port, debug=False)