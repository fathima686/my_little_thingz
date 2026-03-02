#!/usr/bin/env python3
"""
Mock Craft Image Classifier for Testing Without TensorFlow
This is a temporary mock for testing the API structure when TensorFlow is not available
"""

import os
import sys
import json
import time
import random
from PIL import Image

class CraftImageClassifier:
    """
    Mock craft image classifier for testing without TensorFlow
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
        """Initialize mock classifier - simulates trained model loading"""
        print("=== MOCK CRAFT IMAGE CLASSIFIER - TESTING MODE ===", file=sys.stderr)
        print("WARNING: This is a mock classifier for testing without TensorFlow", file=sys.stderr)
        print("For production use, install TensorFlow and use the real classifier", file=sys.stderr)
        
        # Default model path
        if model_path is None:
            model_path = os.path.join(os.path.dirname(__file__), '..', 'backend', 'ai', 'model', 'craft_image_classifier.keras')
        
        # Check if real model exists
        if os.path.exists(model_path):
            print(f"✓ Real trained model found at: {model_path}", file=sys.stderr)
            print("  Install TensorFlow to use the real model", file=sys.stderr)
        else:
            print(f"⚠ Trained model not found at: {model_path}", file=sys.stderr)
            print("  This mock will simulate AI responses", file=sys.stderr)
        
        print("=== MOCK CLASSIFIER READY FOR TESTING ===", file=sys.stderr)
    
    def preprocess_image(self, image_path):
        """
        Mock image preprocessing - just validates the image exists
        """
        try:
            # Load image to verify it's valid
            img = Image.open(image_path)
            
            # Convert to RGB if needed
            if img.mode != 'RGB':
                img = img.convert('RGB')
            
            # Get dimensions
            width, height = img.size
            
            return {
                'width': width,
                'height': height,
                'mode': img.mode,
                'format': img.format
            }
            
        except Exception as e:
            raise Exception(f"Image preprocessing failed: {str(e)}")
    
    def classify_craft(self, image_path):
        """
        Mock classification - returns simulated results based on filename patterns
        """
        try:
            # Validate file exists
            if not os.path.exists(image_path):
                return {
                    'success': False,
                    'error_code': 'FILE_NOT_FOUND',
                    'error_message': f'Image file not found: {image_path}'
                }
            
            # Preprocess image (validates it's a real image)
            img_info = self.preprocess_image(image_path)
            
            # Simulate processing time
            time.sleep(0.5)
            
            # Generate mock predictions based on filename patterns
            filename = os.path.basename(image_path).lower()
            
            # Determine predicted category based on filename hints
            predicted_category = 'hand_embroidery'  # default
            base_confidence = 0.6
            
            if any(word in filename for word in ['candle', 'wax', 'flame']):
                predicted_category = 'candle_making'
                base_confidence = 0.8
            elif any(word in filename for word in ['clay', 'pottery', 'ceramic']):
                predicted_category = 'clay_modeling'
                base_confidence = 0.75
            elif any(word in filename for word in ['gift', 'box', 'package']):
                predicted_category = 'gift_making'
                base_confidence = 0.7
            elif any(word in filename for word in ['embroidery', 'stitch', 'thread']):
                predicted_category = 'hand_embroidery'
                base_confidence = 0.85
            elif any(word in filename for word in ['jewelry', 'necklace', 'bracelet']):
                predicted_category = 'jewelry_making'
                base_confidence = 0.8
            elif any(word in filename for word in ['henna', 'mehandi', 'pattern']):
                predicted_category = 'mehandi_art'
                base_confidence = 0.75
            elif any(word in filename for word in ['resin', 'epoxy', 'clear']):
                predicted_category = 'resin_art'
                base_confidence = 0.8
            
            # Add some randomness
            confidence = base_confidence + random.uniform(-0.1, 0.1)
            confidence = max(0.1, min(0.95, confidence))  # Clamp between 0.1 and 0.95
            
            # Generate all predictions
            all_predictions = []
            remaining_confidence = 1.0 - confidence
            
            for i, (key, name) in enumerate(self.CATEGORY_NAMES.items()):
                if key == predicted_category:
                    pred_confidence = confidence
                else:
                    # Distribute remaining confidence among other categories
                    pred_confidence = remaining_confidence / (len(self.CATEGORY_NAMES) - 1)
                    pred_confidence += random.uniform(-0.05, 0.05)
                    pred_confidence = max(0.01, pred_confidence)
                
                all_predictions.append({
                    'category': key,
                    'name': name,
                    'confidence': float(pred_confidence)
                })
            
            # Sort by confidence
            all_predictions.sort(key=lambda x: x['confidence'], reverse=True)
            
            # Normalize confidences to sum to 1.0
            total_conf = sum(p['confidence'] for p in all_predictions)
            for pred in all_predictions:
                pred['confidence'] = pred['confidence'] / total_conf
            
            # Determine if craft-related (mock logic)
            is_craft_related = True
            if any(word in filename for word in ['selfie', 'person', 'face', 'landscape', 'nature']):
                is_craft_related = False
                confidence = 0.05  # Very low confidence for non-craft
            
            # Generate explanation
            explanation = self.generate_explanation(all_predictions[0], all_predictions, is_craft_related)
            
            result = {
                'success': True,
                'predicted_category': predicted_category,
                'confidence': float(all_predictions[0]['confidence']),
                'all_predictions': all_predictions,
                'is_craft_related': is_craft_related,
                'model_used': 'mock_classifier_for_testing',
                'explanation': explanation,
                'image_info': img_info,
                'mock_mode': True,
                'warning': 'This is a mock response - install TensorFlow for real AI classification'
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
        Generate human-readable explanation of mock classification
        """
        explanation = []
        
        # Craft-related status
        if is_craft_related:
            explanation.append("Image appears to be craft-related (mock analysis)")
        else:
            explanation.append("Image may not be craft-related (mock analysis)")
        
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
        explanation.append("MOCK classification - install TensorFlow for real AI")
        
        return "; ".join(explanation)
    
    def classify_batch(self, image_paths):
        """
        Mock batch classification
        """
        results = []
        for image_path in image_paths:
            result = self.classify_craft(image_path)
            results.append(result)
        return results


def main():
    """
    CLI interface for mock craft image classification
    """
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error_code': 'MISSING_ARGUMENT',
            'error_message': 'Usage: python craft_classifier_mock.py <image_path>'
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