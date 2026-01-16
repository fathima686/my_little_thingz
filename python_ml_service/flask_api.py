#!/usr/bin/env python3
"""
Flask API for Image Classification Service
Provides REST API endpoint for image classification
"""

import os
import json
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from image_classifier import ImageClassifier

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 10 * 1024 * 1024  # 10MB max file size

# Initialize classifier (loads model once at startup)
print("Initializing image classifier...")
classifier = ImageClassifier()
print("Classifier ready!")

@app.route('/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        'status': 'healthy',
        'service': 'image_classification',
        'model': 'MobileNetV2',
        'version': '1.0.0'
    })

@app.route('/classify', methods=['POST'])
def classify_image():
    """
    Classify an image
    
    Request:
        - Method: POST
        - Content-Type: application/json
        - Body: {"image_path": "/path/to/image.jpg"}
        
    OR
        - Method: POST
        - Content-Type: multipart/form-data
        - Body: file upload with key "image"
        
    Response:
        JSON with classification results
    """
    try:
        # Check if image path provided in JSON
        if request.is_json:
            data = request.get_json()
            image_path = data.get('image_path')
            
            if not image_path:
                return jsonify({
                    'success': False,
                    'error_code': 'MISSING_IMAGE_PATH',
                    'error_message': 'image_path is required in JSON body'
                }), 400
            
            # Classify image
            result = classifier.classify_image(image_path)
            
            if not result.get('success', False):
                return jsonify(result), 400
            
            return jsonify(result)
        
        # Check if file uploaded
        elif 'image' in request.files:
            file = request.files['image']
            
            if file.filename == '':
                return jsonify({
                    'success': False,
                    'error_code': 'NO_FILE_SELECTED',
                    'error_message': 'No file selected'
                }), 400
            
            # Save uploaded file temporarily
            filename = secure_filename(file.filename)
            temp_path = os.path.join('/tmp', filename)
            file.save(temp_path)
            
            try:
                # Classify image
                result = classifier.classify_image(temp_path)
                
                # Clean up temp file
                if os.path.exists(temp_path):
                    os.remove(temp_path)
                
                if not result.get('success', False):
                    return jsonify(result), 400
                
                return jsonify(result)
                
            except Exception as e:
                # Clean up temp file on error
                if os.path.exists(temp_path):
                    os.remove(temp_path)
                raise
        
        else:
            return jsonify({
                'success': False,
                'error_code': 'INVALID_REQUEST',
                'error_message': 'Request must contain either JSON with image_path or multipart file upload'
            }), 400
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'SERVER_ERROR',
            'error_message': str(e)
        }), 500

@app.route('/classify/batch', methods=['POST'])
def classify_batch():
    """
    Classify multiple images
    
    Request:
        - Method: POST
        - Content-Type: application/json
        - Body: {"image_paths": ["/path/1.jpg", "/path/2.jpg"]}
        
    Response:
        JSON array with classification results
    """
    try:
        if not request.is_json:
            return jsonify({
                'success': False,
                'error_code': 'INVALID_REQUEST',
                'error_message': 'Request must be JSON'
            }), 400
        
        data = request.get_json()
        image_paths = data.get('image_paths', [])
        
        if not image_paths or not isinstance(image_paths, list):
            return jsonify({
                'success': False,
                'error_code': 'INVALID_IMAGE_PATHS',
                'error_message': 'image_paths must be a non-empty array'
            }), 400
        
        # Classify all images
        results = classifier.classify_batch(image_paths)
        
        return jsonify({
            'success': True,
            'results': results,
            'total': len(results)
        })
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error_code': 'SERVER_ERROR',
            'error_message': str(e)
        }), 500

@app.errorhandler(413)
def request_entity_too_large(error):
    """Handle file too large error"""
    return jsonify({
        'success': False,
        'error_code': 'FILE_TOO_LARGE',
        'error_message': 'File size exceeds 10MB limit'
    }), 413

if __name__ == '__main__':
    # Run Flask server
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=False)
