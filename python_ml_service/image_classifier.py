#!/usr/bin/env python3
"""
Local Image Classification Service using MobileNetV2
No paid APIs, no billing, completely free and local
"""

import os
import sys
import json
import numpy as np
from PIL import Image
import tensorflow as tf
from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2, preprocess_input, decode_predictions

class ImageClassifier:
    """
    Free local image classifier using pre-trained MobileNetV2
    """
    
    # Labels that indicate unrelated content for craft/tutorial images
    UNRELATED_LABELS = [
        'person', 'people', 'human', 'face', 'portrait', 'man', 'woman', 'child',
        'landscape', 'scenery', 'nature', 'outdoor', 'mountain', 'sky', 'cloud',
        'animal', 'pet', 'dog', 'cat', 'bird', 'horse', 'cow', 'sheep',
        'food', 'meal', 'dish', 'restaurant', 'plate', 'pizza', 'burger',
        'vehicle', 'car', 'automobile', 'transportation', 'truck', 'bus',
        'building', 'architecture', 'city', 'urban', 'house', 'street'
    ]
    
    def __init__(self):
        """Initialize MobileNetV2 model"""
        print("Loading MobileNetV2 model...", file=sys.stderr)
        try:
            # Load pre-trained MobileNetV2 (will download on first run, ~14MB)
            self.model = MobileNetV2(weights='imagenet', include_top=True)
            print("Model loaded successfully!", file=sys.stderr)
        except Exception as e:
            print(f"Error loading model: {e}", file=sys.stderr)
            raise
    
    def preprocess_image(self, image_path):
        """
        Load and preprocess image for MobileNetV2
        
        Args:
            image_path: Path to image file
            
        Returns:
            Preprocessed image array
        """
        try:
            # Load image
            img = Image.open(image_path)
            
            # Convert to RGB if needed
            if img.mode != 'RGB':
                img = img.convert('RGB')
            
            # Resize to 224x224 (MobileNetV2 input size)
            img = img.resize((224, 224), Image.LANCZOS)
            
            # Convert to array
            img_array = np.array(img)
            
            # Add batch dimension
            img_array = np.expand_dims(img_array, axis=0)
            
            # Preprocess for MobileNetV2
            img_array = preprocess_input(img_array)
            
            return img_array
            
        except Exception as e:
            raise Exception(f"Image preprocessing failed: {str(e)}")
    
    def classify_image(self, image_path, top_k=10):
        """
        Classify image and return top predictions
        
        Args:
            image_path: Path to image file
            top_k: Number of top predictions to return
            
        Returns:
            dict with classification results
        """
        try:
            # Validate file exists
            if not os.path.exists(image_path):
                return {
                    'success': False,
                    'error_code': 'FILE_NOT_FOUND',
                    'error_message': f'Image file not found: {image_path}'
                }
            
            # Preprocess image
            img_array = self.preprocess_image(image_path)
            
            # Make prediction
            predictions = self.model.predict(img_array, verbose=0)
            
            # Decode predictions
            decoded = decode_predictions(predictions, top=top_k)[0]
            
            # Format results
            labels = []
            possibly_unrelated = False
            max_confidence = 0.0
            warning_label = None
            
            for class_id, label_name, confidence in decoded:
                label_lower = label_name.lower().replace('_', ' ')
                
                labels.append({
                    'name': label_name,
                    'confidence': float(confidence)
                })
                
                # Check if label indicates unrelated content
                if confidence >= 0.80:
                    for unrelated in self.UNRELATED_LABELS:
                        if unrelated in label_lower:
                            possibly_unrelated = True
                            if confidence > max_confidence:
                                max_confidence = confidence
                                warning_label = label_name
            
            # Build warning message
            warning_message = None
            if possibly_unrelated and warning_label:
                warning_message = (
                    f"Image may contain unrelated content: {warning_label} "
                    f"(confidence: {max_confidence * 100:.1f}%)"
                )
            
            return {
                'success': True,
                'ai_enabled': True,
                'possibly_unrelated': possibly_unrelated,
                'labels': labels,
                'confidence': max_confidence,
                'warning_message': warning_message,
                'model': 'MobileNetV2',
                'model_type': 'local_free'
            }
            
        except Exception as e:
            return {
                'success': False,
                'error_code': 'CLASSIFICATION_FAILED',
                'error_message': str(e)
            }
    
    def classify_batch(self, image_paths, top_k=10):
        """
        Classify multiple images
        
        Args:
            image_paths: List of image file paths
            top_k: Number of top predictions per image
            
        Returns:
            List of classification results
        """
        results = []
        for image_path in image_paths:
            result = self.classify_image(image_path, top_k)
            results.append(result)
        return results


def main():
    """
    CLI interface for image classification
    Usage: python image_classifier.py <image_path>
    """
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error_code': 'MISSING_ARGUMENT',
            'error_message': 'Usage: python image_classifier.py <image_path>'
        }))
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    try:
        classifier = ImageClassifier()
        result = classifier.classify_image(image_path)
        print(json.dumps(result, indent=2))
        
        # Exit with error code if classification failed
        if not result.get('success', False):
            sys.exit(1)
            
    except Exception as e:
        print(json.dumps({
            'success': False,
            'error_code': 'EXCEPTION',
            'error_message': str(e)
        }))
        sys.exit(1)


if __name__ == '__main__':
    main()
