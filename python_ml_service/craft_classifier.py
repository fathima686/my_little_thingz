#!/usr/bin/env python3
"""
Craft Image Classifier - Production Version
Exclusively uses the trained craft_image_classifier.keras model for all AI validation decisions.
No fallback logic - service terminates if model cannot be loaded.
"""

import os
import sys
import json
import numpy as np
from PIL import Image
import tensorflow as tf
from tensorflow.keras.models import load_model

class CraftImageClassifier:
    """
    Production craft image classifier using exclusively the trained .keras model
    """
    
    # Craft categories mapping (must match training data)
    CRAFT_CATEGORIES = {
        0: 'candle_making',
        1: 'clay_modeling', 
        2: 'gift_making',
        3: 'hand_embroidery',
        4: 'jewelry_making',
        5: 'mehandi_art',
        6: 'resin_art'
    }
    
    # Category display names
    CATEGORY_NAMES = {
        'candle_making': 'Candle Making',
        'clay_modeling': 'Clay Modeling',
        'gift_making': 'Gift Making', 
        'hand_embroidery': 'Hand Embroidery',
        'jewelry_making': 'Jewelry Making',
        'mehandi_art': 'Mylanchi / Mehandi Art',
        'resin_art': 'Resin Art'
    }
    
    def __init__(self, model_path=None):
        """Initialize craft classifier - STRICT MODE: No fallbacks allowed"""
        print("=== CRAFT IMAGE CLASSIFIER - PRODUCTION MODE ===", file=sys.stderr)
        print("Loading trained craft_image_classifier.keras model...", file=sys.stderr)
        
        # Default model path
        if model_path is None:
            model_path = os.path.join(os.path.dirname(__file__), '..', 'backend', 'ai', 'model', 'craft_image_classifier.keras')
        
        # Verify model file exists
        if not os.path.exists(model_path):
            error_msg = f"CRITICAL ERROR: Trained model not found at {model_path}"
            print(error_msg, file=sys.stderr)
            print("Dataset preparation and model training must be completed first.", file=sys.stderr)
            print("Service terminating - no fallback logic allowed.", file=sys.stderr)
            raise FileNotFoundError(error_msg)
        
        try:
            # Load the trained model with explicit logging
            print(f"Loading model from: {model_path}", file=sys.stderr)
            self.craft_model = load_model(model_path)
            print("✓ Trained craft model loaded successfully!", file=sys.stderr)
            
            # Verify model architecture
            model_input_shape = self.craft_model.input_shape
            model_output_shape = self.craft_model.output_shape
            print(f"✓ Model input shape: {model_input_shape}", file=sys.stderr)
            print(f"✓ Model output shape: {model_output_shape}", file=sys.stderr)
            
            # Verify output classes match our categories
            expected_classes = len(self.CRAFT_CATEGORIES)
            actual_classes = model_output_shape[-1] if model_output_shape else 0
            
            if actual_classes != expected_classes:
                error_msg = f"Model output mismatch: expected {expected_classes} classes, got {actual_classes}"
                print(f"CRITICAL ERROR: {error_msg}", file=sys.stderr)
                raise ValueError(error_msg)
            
            print(f"✓ Model verified: {expected_classes} craft categories", file=sys.stderr)
            print("=== CLASSIFIER READY FOR PRODUCTION USE ===", file=sys.stderr)
            
        except Exception as e:
            error_msg = f"CRITICAL ERROR: Failed to load trained model: {str(e)}"
            print(error_msg, file=sys.stderr)
            print("Service terminating - no fallback logic allowed.", file=sys.stderr)
            raise RuntimeError(error_msg)
    
    def preprocess_image(self, image_path):
        """
        Load and preprocess image for the trained model
        
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
            
            # Resize to model's expected input size (224x224 for MobileNet-based models)
            img = img.resize((224, 224), Image.LANCZOS)
            
            # Convert to array
            img_array = np.array(img)
            
            # Add batch dimension
            img_array = np.expand_dims(img_array, axis=0)
            
            # Normalize to [0,1] range (standard for most trained models)
            img_array = img_array.astype(np.float32) / 255.0
            
            return img_array
            
        except Exception as e:
            raise Exception(f"Image preprocessing failed: {str(e)}")
    
    def classify_craft(self, image_path):
        """
        Classify image into craft categories using the trained model ONLY
        
        Args:
            image_path: Path to image file
            
        Returns:
            dict with craft classification results
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
            
            # Get predictions from trained model
            predictions = self.craft_model.predict(img_array, verbose=0)[0]
            
            # Get all predictions with confidence scores
            all_predictions = []
            for i, confidence in enumerate(predictions):
                category_key = self.CRAFT_CATEGORIES.get(i, f'category_{i}')
                category_name = self.CATEGORY_NAMES.get(category_key, category_key)
                all_predictions.append({
                    'category': category_key,
                    'name': category_name,
                    'confidence': float(confidence)
                })
            
            # Sort by confidence
            all_predictions.sort(key=lambda x: x['confidence'], reverse=True)
            
            # Get top prediction
            top_prediction = all_predictions[0]
            
            # Determine if image is craft-related based on model confidence
            # If the highest confidence is very low, it might not be craft-related
            is_craft_related = top_prediction['confidence'] > 0.1  # Threshold for craft detection
            
            result = {
                'success': True,
                'predicted_category': top_prediction['category'],
                'confidence': top_prediction['confidence'],
                'all_predictions': all_predictions,
                'is_craft_related': is_craft_related,
                'model_used': 'trained_keras_model',
                'explanation': self.generate_explanation(top_prediction, all_predictions, is_craft_related)
            }
            
            return result
            
        except Exception as e:
            return {
                'success': False,
                'error_code': 'CLASSIFICATION_FAILED',
                'error_message': str(e)
            }
    
    def generate_explanation(self, top_prediction, all_predictions, is_craft_related):
        """
        Generate human-readable explanation of classification
        """
        explanation = []
        
        # Craft-related status
        if is_craft_related:
            explanation.append("Image appears to be craft-related")
        else:
            explanation.append("Image may not be craft-related (very low confidence)")
        
        # Category prediction
        category_name = top_prediction['name']
        confidence = top_prediction['confidence']
        
        if confidence >= 0.8:
            explanation.append(f"High confidence prediction: {category_name}")
        elif confidence >= 0.5:
            explanation.append(f"Medium confidence prediction: {category_name}")
        elif confidence >= 0.2:
            explanation.append(f"Low confidence prediction: {category_name}")
        else:
            explanation.append(f"Very low confidence prediction: {category_name}")
        
        # Add second-best prediction if significantly different
        if len(all_predictions) > 1:
            second_best = all_predictions[1]
            if second_best['confidence'] > 0.2 and (confidence - second_best['confidence']) < 0.3:
                explanation.append(f"Alternative possibility: {second_best['name']} ({second_best['confidence']:.1%})")
        
        # Model information
        explanation.append("Classification using trained craft_image_classifier.keras model")
        
        return "; ".join(explanation)
    
    def classify_batch(self, image_paths):
        """
        Classify multiple images
        
        Args:
            image_paths: List of image file paths
            
        Returns:
            List of classification results
        """
        results = []
        for image_path in image_paths:
            result = self.classify_craft(image_path)
            results.append(result)
        return results


def main():
    """
    CLI interface for craft image classification
    Usage: python craft_classifier.py <image_path>
    """
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error_code': 'MISSING_ARGUMENT',
            'error_message': 'Usage: python craft_classifier.py <image_path>'
        }))
        sys.exit(1)
    
    image_path = sys.argv[1]
    
    try:
        classifier = CraftImageClassifier()
        result = classifier.classify_craft(image_path)
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