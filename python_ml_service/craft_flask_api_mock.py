#!/usr/bin/env python3
"""
Mock Flask API for Craft Image Classification Service
For testing without TensorFlow - uses mock classifier
"""

import os
import sys
import json
import tempfile
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename

# Try to import real classifier first, fall back to mock
try:
    from craft_classifier import CraftImageClassifier
    print("Using real TensorFlow classifier", file=sys.stderr)
    USING_MOCK = False
except ImportError:
    from craft_classifier_mock import CraftImageClassifier
    print("Using mock classifier (TensorFlow not available)", file=sys.stderr)
    USING_MOCK = True

# Initialize Flask app
app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 10 * 1024 * 1024  # 10MB max file size

# Initialize classifier
print("=== CRAFT CLASSIFICATION API - TESTING MODE ===" if USING_MOCK else "=== CRAFT CLASSIFICATION API - PRODUCTION MODE ===", file=sys.stderr)
try:
    classifier = CraftImageClassifier()
    print("✓ Craft classifier initialized successfully", file=sys.stderr)
    classifier_available = True
except Exception as e:
    print(f"✗ CRITICAL ERROR: Failed to initialize craft classifier: {e}", file=sys.stderr)
    if not USING_MOCK:
        print("✗ Service terminating - trained model is required", file=sys.stderr)
        sys.exit(1)
    else:
        print("✗ Even mock classifier failed - check dependencies", file=sys.stderr)
        sys.exit(1)

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
        'service': 'craft_image_classification_testing' if USING_MOCK else 'craft_image_classification_production',
        'model': 'Mock Classifier (Testing)' if USING_MOCK else 'Trained craft_image_classifier.keras',
        'version': '2.0.0-mock' if USING_MOCK else '2.0.0',
        'classifier_available': True,
        'model_type': 'mock_classifier' if USING_MOCK else 'trained_keras_model',
        'fallback_disabled': True,
        'mock_mode': USING_MOCK,
        'warning': 'Install TensorFlow for production use' if USING_MOCK else None,
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
    Classify image into craft categories
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
        
        # Classify image
        result = classifier.classify_craft(image_path)
        
        # Add mock warning if using mock classifier
        if USING_MOCK and result.get('success'):
            result['mock_mode'] = True
            result['warning'] = 'This is a mock response - install TensorFlow for real AI classification'
        
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
    Classify multiple images into craft categories
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
        
        # Classify all images
        results = classifier.classify_batch(image_paths)
        
        return jsonify({
            'success': True,
            'results': results,
            'batch_size': len(results),
            'model_used': 'mock_classifier' if USING_MOCK else 'trained_keras_model',
            'mock_mode': USING_MOCK
        })
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'BATCH_CLASSIFICATION_EXCEPTION',
            'error_message': str(e)
        }), 500

@app.route('/validate-practice', methods=['POST'])
def validate_practice_image():
    """
    Comprehensive practice image validation
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
        
        # Classify image
        classification_result = classifier.classify_craft(image_path)
        
        if not classification_result['success']:
            return jsonify(classification_result), 400
        
        # Perform validation logic
        validation_result = perform_validation_logic(
            classification_result, 
            selected_category, 
            tutorial_id
        )
        
        # Combine results
        result = {
            'success': True,
            'classification': classification_result,
            'validation': validation_result,
            'tutorial_id': tutorial_id,
            'selected_category': selected_category,
            'model_used': 'mock_classifier' if USING_MOCK else 'trained_keras_model',
            'mock_mode': USING_MOCK,
            'fallback_disabled': True
        }
        
        return jsonify(result)
    
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'VALIDATION_EXCEPTION',
            'error_message': str(e)
        }), 500

def perform_validation_logic(classification_result, selected_category, tutorial_id):
    """
    Perform validation logic based on classification results
    """
    validation = {
        'status': 'approved',
        'category_match': False,
        'confidence_level': 'unknown',
        'recommendation': 'approve',
        'reasons': [],
        'warnings': [],
        'decision_type': 'auto-approve'
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
        
        # Apply validation rules (same as production)
        if confidence < 0.1 or not is_craft_related:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['decision_type'] = 'auto-reject'
            validation['reasons'].append(f'Image appears unrelated to crafts (confidence: {confidence:.1%})')
        elif not validation['category_match'] and confidence >= 0.7:
            validation['status'] = 'rejected'
            validation['recommendation'] = 'reject'
            validation['decision_type'] = 'auto-reject'
            validation['reasons'].append(f'Category mismatch: predicted {predicted_category} with {confidence:.1%} confidence, selected {selected_category}')
        elif validation['category_match'] and confidence >= 0.6:
            validation['status'] = 'approved'
            validation['recommendation'] = 'approve'
            validation['decision_type'] = 'auto-approve'
            validation['reasons'].append(f'Category match with {confidence:.1%} confidence: {predicted_category}')
        elif validation['category_match'] and confidence >= 0.3:
            validation['status'] = 'approved'
            validation['recommendation'] = 'approve'
            validation['decision_type'] = 'auto-approve'
            validation['reasons'].append(f'Category match with moderate confidence: {confidence:.1%}')
        elif not validation['category_match'] and confidence >= 0.4:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['decision_type'] = 'flag-for-review'
            validation['reasons'].append(f'Possible category mismatch: predicted {predicted_category} ({confidence:.1%}), selected {selected_category}')
        elif confidence >= 0.1 and confidence < 0.4:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['decision_type'] = 'flag-for-review'
            validation['reasons'].append(f'Low confidence classification: {confidence:.1%}')
        else:
            validation['status'] = 'flagged'
            validation['recommendation'] = 'review'
            validation['decision_type'] = 'flag-for-review'
            validation['reasons'].append('Ambiguous classification result')
        
        # Add mock warning
        if USING_MOCK:
            validation['warnings'].append('Using mock classifier - install TensorFlow for real AI validation')
        
    except Exception as e:
        validation['status'] = 'error'
        validation['recommendation'] = 'review'
        validation['decision_type'] = 'flag-for-review'
        validation['reasons'].append(f'Validation error: {str(e)}')
    
    return validation

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
        'total_categories': 7,
        'mock_mode': USING_MOCK
    })

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
    
    mode_text = "TESTING MODE (MOCK)" if USING_MOCK else "PRODUCTION MODE"
    print(f"=== STARTING CRAFT CLASSIFICATION API - {mode_text} ===", file=sys.stderr)
    print(f"Server: {host}:{port}", file=sys.stderr)
    print("Available endpoints:", file=sys.stderr)
    print("  GET  /health - Health check", file=sys.stderr)
    print("  POST /classify-craft - Classify single image", file=sys.stderr)
    print("  POST /classify-craft/batch - Classify multiple images", file=sys.stderr)
    print("  POST /validate-practice - Comprehensive practice validation", file=sys.stderr)
    print("  GET  /categories - Get supported categories", file=sys.stderr)
    print("", file=sys.stderr)
    
    if USING_MOCK:
        print("TESTING FEATURES:", file=sys.stderr)
        print("✓ Mock classifier for testing without TensorFlow", file=sys.stderr)
        print("✓ Simulated AI responses based on filename patterns", file=sys.stderr)
        print("✓ Same API structure as production system", file=sys.stderr)
        print("⚠ Install TensorFlow for real AI classification", file=sys.stderr)
    else:
        print("PRODUCTION FEATURES:", file=sys.stderr)
        print("✓ Trained craft_image_classifier.keras model ONLY", file=sys.stderr)
        print("✓ No fallback logic - deterministic results", file=sys.stderr)
        print("✓ Strict auto-approve/auto-reject/flag decisions", file=sys.stderr)
        print("✓ Service fails fast if model unavailable", file=sys.stderr)
    
    print("=== READY FOR USE ===", file=sys.stderr)
    
    app.run(host=host, port=port, debug=False)