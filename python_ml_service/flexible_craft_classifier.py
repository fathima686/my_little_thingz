#!/usr/bin/env python3
"""
Flexible Craft Classifier
Handles different .keras model architectures
"""

import os
import sys
import json
import time
import numpy as np
from PIL import Image
import tensorflow as tf
from tensorflow.keras.models import load_model
from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2, preprocess_input, decode_predictions
import logging

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class FlexibleCraftClassifier:
    """
    Flexible craft classifier that can handle different model architectures
    """
    
    CRAFT_CATEGORIES = {
        0: 'candle_making',
        1: 'clay_modeling', 
        2: 'gift_making',
        3: 'hand_embroidery',
        4: 'jewelry_making',
        5: 'mehandi_art',
        6: 'resin_art'
    }
    
    def __init__(self, model_path=None):
        self.model_path = model_path or self._find_model_path()
        self.craft_model = None
        self.base_model = None
        self.has_fine_tuned_model = False
        
        self._load_models()
    
    def _find_model_path(self):
        """Find the trained model file"""
        possible_paths = [
            os.path.join(os.path.dirname(__file__), '..', 'backend', 'ai', 'model', 'craft_image_classifier.keras'),
            os.path.join(os.path.dirname(__file__), 'models', 'craft_classifier.keras'),
            os.path.join(os.path.dirname(__file__), 'craft_model.keras')
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                logger.info(f"Found model at: {path}")
                return path
        
        logger.warning("No fine-tuned model found")
        return None
    
    def _load_models(self):
        """Load models with flexible architecture handling"""
        try:
            # Load base MobileNet first
            logger.info("Loading base MobileNetV2")
            self.base_model = MobileNetV2(weights='imagenet', include_top=True)
            logger.info("Base MobileNet loaded successfully")
            
            # Try to load fine-tuned model with different strategies
            if self.model_path and os.path.exists(self.model_path):
                self._try_load_fine_tuned_model()
                
        except Exception as e:
            logger.error(f"Error loading models: {e}")
            raise
    
    def _try_load_fine_tuned_model(self):
        """Try different strategies to load the fine-tuned model"""
        logger.info(f"Attempting to load fine-tuned model: {self.model_path}")
        
        strategies = [
            self._load_with_compile_false,
            self._load_with_custom_objects,
            self._load_with_safe_mode,
            self._inspect_and_load_model
        ]
        
        for i, strategy in enumerate(strategies, 1):
            try:
                logger.info(f"Trying loading strategy {i}...")
                self.craft_model = strategy()
                if self.craft_model:
                    self.has_fine_tuned_model = True
                    logger.info(f"Fine-tuned model loaded successfully with strategy {i}")
                    self._validate_model()
                    return
            except Exception as e:
                logger.warning(f"Strategy {i} failed: {e}")
                continue
        
        logger.warning("All loading strategies failed, using base MobileNet only")
    
    def _load_with_compile_false(self):
        """Try loading without compilation"""
        return load_model(self.model_path, compile=False)
    
    def _load_with_custom_objects(self):
        """Try loading with custom objects"""
        custom_objects = {
            'tf': tf,
            'keras': tf.keras
        }
        return load_model(self.model_path, custom_objects=custom_objects)
    
    def _load_with_safe_mode(self):
        """Try loading in safe mode"""
        return tf.keras.models.load_model(self.model_path, safe_mode=False)
    
    def _inspect_and_load_model(self):
        """Inspect model architecture and try adaptive loading"""
        try:
            # Try to get model info without loading
            logger.info("Inspecting model architecture...")
            
            # Load with minimal requirements
            model = tf.keras.models.load_model(self.model_path, compile=False)
            
            # Check model structure
            logger.info(f"Model input shape: {model.input_shape}")
            logger.info(f"Model output shape: {model.output_shape}")
            logger.info(f"Number of layers: {len(model.layers)}")
            
            # Try to rebuild if needed
            if len(model.layers) > 0:
                return model
            
        except Exception as e:
            logger.error(f"Model inspection failed: {e}")
            return None
    
    def _validate_model(self):
        """Validate the loaded model"""
        try:
            # Test with dummy input
            dummy_input = np.random.random((1, 224, 224, 3)).astype(np.float32)
            dummy_input = preprocess_input(dummy_input)
            
            prediction = self.craft_model.predict(dummy_input, verbose=0)
            logger.info(f"Model validation successful - output shape: {prediction.shape}")
            
            # Check if output matches expected categories
            if prediction.shape[1] == len(self.CRAFT_CATEGORIES):
                logger.info("Model output matches expected craft categories")
            else:
                logger.warning(f"Model output ({prediction.shape[1]}) doesn't match expected categories ({len(self.CRAFT_CATEGORIES)})")
            
        except Exception as e:
            logger.error(f"Model validation failed: {e}")
            self.craft_model = None
            self.has_fine_tuned_model = False
    
    def classify_craft(self, image_path):
        """Classify craft image using fine-tuned model or fallback"""
        try:
            # Preprocess image
            img_array = self._preprocess_image(image_path)
            if img_array is None:
                return self._create_error_result("Image preprocessing failed")
            
            # Use fine-tuned model if available
            if self.has_fine_tuned_model and self.craft_model:
                return self._classify_with_fine_tuned_model(img_array)
            else:
                return self._classify_with_base_model_heuristics(img_array)
                
        except Exception as e:
            logger.error(f"Classification error: {e}")
            return self._create_error_result(f"Classification failed: {str(e)}")
    
    def _preprocess_image(self, image_path):
        """Preprocess image for model input"""
        try:
            img = Image.open(image_path)
            if img.mode != 'RGB':
                img = img.convert('RGB')
            
            img = img.resize((224, 224), Image.LANCZOS)
            img_array = np.array(img)
            img_array = np.expand_dims(img_array, axis=0)
            img_array = preprocess_input(img_array)
            
            return img_array
            
        except Exception as e:
            logger.error(f"Image preprocessing error: {e}")
            return None
    
    def _classify_with_fine_tuned_model(self, img_array):
        """Classify using fine-tuned model"""
        try:
            predictions = self.craft_model.predict(img_array, verbose=0)[0]
            
            # Convert to standard Python types
            predictions = [float(p) for p in predictions]
            
            # Get all predictions
            all_predictions = []
            for i, confidence in enumerate(predictions):
                if i < len(self.CRAFT_CATEGORIES):
                    category = self.CRAFT_CATEGORIES[i]
                    all_predictions.append({
                        'category': category,
                        'confidence': confidence
                    })
            
            # Sort by confidence
            all_predictions.sort(key=lambda x: x['confidence'], reverse=True)
            top_prediction = all_predictions[0]
            
            return {
                'success': True,
                'predicted_category': top_prediction['category'],
                'confidence': top_prediction['confidence'],
                'all_predictions': all_predictions,
                'is_craft_related': True,
                'non_craft_confidence': 0.0,
                'model_used': 'fine_tuned_keras_model'
            }
            
        except Exception as e:
            logger.error(f"Fine-tuned model classification error: {e}")
            return self._classify_with_base_model_heuristics(img_array)
    
    def _classify_with_base_model_heuristics(self, img_array):
        """Fallback classification using base MobileNet"""
        try:
            predictions = self.base_model.predict(img_array, verbose=0)
            decoded = decode_predictions(predictions, top=20)[0]
            
            # Craft-related keywords
            craft_keywords = {
                'candle_making': ['candle', 'wax', 'wick', 'flame'],
                'clay_modeling': ['pottery', 'ceramic', 'clay', 'vase'],
                'gift_making': ['gift', 'present', 'box', 'wrapping'],
                'hand_embroidery': ['embroidery', 'stitch', 'thread', 'fabric'],
                'jewelry_making': ['jewelry', 'necklace', 'bracelet', 'ring'],
                'mehandi_art': ['henna', 'mehandi', 'pattern', 'hand'],
                'resin_art': ['resin', 'epoxy', 'clear', 'artistic']
            }
            
            # Score categories based on ImageNet predictions
            category_scores = {category: 0.0 for category in self.CRAFT_CATEGORIES.values()}
            
            for class_id, label_name, confidence in decoded:
                label_lower = label_name.lower()
                
                for category, keywords in craft_keywords.items():
                    for keyword in keywords:
                        if keyword in label_lower:
                            category_scores[category] += float(confidence) * 0.7
            
            # Find best match
            if max(category_scores.values()) > 0:
                best_category = max(category_scores, key=category_scores.get)
                best_confidence = min(category_scores[best_category], 0.8)
            else:
                best_category = 'hand_embroidery'
                best_confidence = 0.3
            
            # Create all predictions
            all_predictions = []
            for category, score in sorted(category_scores.items(), key=lambda x: x[1], reverse=True):
                all_predictions.append({
                    'category': category,
                    'confidence': float(min(score, 0.8))
                })
            
            return {
                'success': True,
                'predicted_category': best_category,
                'confidence': best_confidence,
                'all_predictions': all_predictions,
                'is_craft_related': True,
                'non_craft_confidence': 0.0,
                'model_used': 'base_mobilenet_heuristics',
                'imagenet_predictions': [(label, float(conf)) for _, label, conf in decoded[:5]]
            }
            
        except Exception as e:
            logger.error(f"Base model classification error: {e}")
            return self._create_error_result(f"Classification failed: {str(e)}")
    
    def _create_error_result(self, error_message):
        """Create error result"""
        return {
            'success': False,
            'error_message': error_message,
            'predicted_category': None,
            'confidence': 0.0,
            'all_predictions': [],
            'is_craft_related': False,
            'non_craft_confidence': 0.0
        }
    
    def get_model_info(self):
        """Get information about loaded models"""
        return {
            'has_fine_tuned_model': self.has_fine_tuned_model,
            'model_path': self.model_path,
            'model_exists': os.path.exists(self.model_path) if self.model_path else False,
            'base_model_loaded': self.base_model is not None,
            'fine_tuned_model_loaded': self.craft_model is not None
        }

# Test the flexible classifier
if __name__ == "__main__":
    classifier = FlexibleCraftClassifier()
    
    print("=== Flexible Craft Classifier Test ===")
    print(f"Model Info: {json.dumps(classifier.get_model_info(), indent=2)}")
    
    if len(sys.argv) > 1:
        test_image = sys.argv[1]
        if os.path.exists(test_image):
            result = classifier.classify_craft(test_image)
            print(f"Classification Result: {json.dumps(result, indent=2)}")
        else:
            print(f"Test image not found: {test_image}")