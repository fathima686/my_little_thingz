#!/usr/bin/env python3
"""
Enhanced Craft Image Classifier for Academic Research
Implements explainable AI validation pipeline with comprehensive logging
"""

import os
import sys
import json
import time
import numpy as np
from PIL import Image, ImageStat
import tensorflow as tf
from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2, preprocess_input, decode_predictions
from tensorflow.keras.models import load_model
import hashlib
import logging

# Configure logging for academic purposes
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('craft_validation.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)

class EnhancedCraftClassifier:
    """
    Enhanced craft classifier with explainable AI features
    Designed for academic demonstration and research purposes
    """
    
    # Craft categories mapping (matching training data)
    CRAFT_CATEGORIES = {
        0: 'candle_making',
        1: 'clay_modeling',
        2: 'gift_making', 
        3: 'hand_embroidery',
        4: 'jewelry_making',
        5: 'mehandi_art',
        6: 'resin_art'
    }
    
    CATEGORY_NAMES = {
        'candle_making': 'Candle Making',
        'clay_modeling': 'Clay Modeling',
        'gift_making': 'Gift Making',
        'hand_embroidery': 'Hand Embroidery', 
        'jewelry_making': 'Jewelry Making',
        'mehandi_art': 'Mylanchi / Mehandi Art',
        'resin_art': 'Resin Art'
    }
    
    # Non-craft indicators for base model analysis
    NON_CRAFT_INDICATORS = [
        # People and portraits
        'person', 'people', 'human', 'face', 'portrait', 'man', 'woman', 'child', 'baby',
        'selfie', 'group', 'crowd', 'wedding', 'party',
        
        # Nature and landscapes
        'landscape', 'scenery', 'nature', 'outdoor', 'mountain', 'hill', 'valley',
        'sky', 'cloud', 'sunset', 'sunrise', 'beach', 'ocean', 'sea', 'lake', 'river',
        'forest', 'tree', 'flower', 'plant', 'garden', 'park',
        
        # Animals
        'animal', 'pet', 'dog', 'cat', 'bird', 'horse', 'cow', 'sheep', 'lion',
        'elephant', 'tiger', 'bear', 'fish', 'butterfly', 'insect',
        
        # Food and dining
        'food', 'meal', 'dish', 'restaurant', 'plate', 'pizza', 'burger', 'sandwich',
        'cake', 'bread', 'fruit', 'vegetable', 'drink', 'coffee', 'wine',
        
        # Vehicles and transportation
        'vehicle', 'car', 'automobile', 'truck', 'bus', 'motorcycle', 'bicycle',
        'train', 'airplane', 'boat', 'ship', 'transportation',
        
        # Buildings and architecture
        'building', 'architecture', 'city', 'urban', 'house', 'home', 'office',
        'street', 'road', 'bridge', 'tower', 'church', 'school',
        
        # Technology and screens
        'screen', 'monitor', 'computer', 'laptop', 'phone', 'smartphone', 'tablet',
        'television', 'tv', 'screenshot', 'interface', 'app', 'website',
        
        # Documents and text
        'document', 'text', 'book', 'paper', 'letter', 'sign', 'poster',
        'newspaper', 'magazine', 'menu', 'receipt'
    ]
    
    # Craft-related keywords for enhanced detection
    CRAFT_KEYWORDS = {
        'candle_making': [
            'candle', 'wax', 'wick', 'flame', 'light', 'lantern', 'scented',
            'paraffin', 'soy', 'beeswax', 'mold', 'melting'
        ],
        'clay_modeling': [
            'pottery', 'ceramic', 'clay', 'vase', 'bowl', 'sculpture', 'kiln',
            'wheel', 'glazing', 'terracotta', 'porcelain', 'earthenware'
        ],
        'gift_making': [
            'gift', 'present', 'box', 'package', 'wrapping', 'ribbon', 'bow',
            'decoration', 'handmade', 'craft', 'diy', 'creative'
        ],
        'hand_embroidery': [
            'embroidery', 'stitch', 'thread', 'needle', 'fabric', 'textile',
            'pattern', 'design', 'cross-stitch', 'needlework', 'sewing'
        ],
        'jewelry_making': [
            'jewelry', 'jewellery', 'necklace', 'bracelet', 'ring', 'earring',
            'bead', 'chain', 'pendant', 'gemstone', 'metal', 'silver', 'gold'
        ],
        'mehandi_art': [
            'henna', 'mehandi', 'mehndi', 'pattern', 'design', 'hand', 'art',
            'decoration', 'traditional', 'indian', 'temporary', 'tattoo'
        ],
        'resin_art': [
            'resin', 'epoxy', 'clear', 'transparent', 'casting', 'mold',
            'hardener', 'pigment', 'coating', 'glossy', 'artistic'
        ]
    }
    
    def __init__(self, model_path=None):
        """Initialize enhanced craft classifier"""
        logger.info("Initializing Enhanced Craft Classifier for Academic Research")
        
        self.model_path = model_path or self._find_model_path()
        self.craft_model = None
        self.base_model = None
        self.has_fine_tuned_model = False
        
        # Performance metrics
        self.classification_count = 0
        self.total_processing_time = 0.0
        
        self._load_models()
        logger.info("Enhanced Craft Classifier initialized successfully")
    
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
        
        logger.warning("No fine-tuned model found, will use base MobileNet with heuristics")
        return None
    
    def _load_models(self):
        """Load both fine-tuned and base models"""
        try:
            # Try to load fine-tuned model
            if self.model_path and os.path.exists(self.model_path):
                try:
                    logger.info(f"Loading fine-tuned model from {self.model_path}")
                    self.craft_model = load_model(self.model_path)
                    self.has_fine_tuned_model = True
                    logger.info("Fine-tuned craft model loaded successfully")
                except Exception as e:
                    logger.error(f"Failed to load fine-tuned model: {e}")
                    self.has_fine_tuned_model = False
            
            # Load base MobileNet for fallback and non-craft detection
            logger.info("Loading base MobileNetV2 for non-craft detection")
            self.base_model = MobileNetV2(weights='imagenet', include_top=True)
            logger.info("Base MobileNet model loaded successfully")
            
        except Exception as e:
            logger.error(f"Critical error loading models: {e}")
            raise
    
    def classify_craft(self, image_path, explain_decision=True):
        """
        Comprehensive craft classification with explainable results
        """
        start_time = time.time()
        classification_id = hashlib.md5(f"{image_path}_{start_time}".encode()).hexdigest()[:8]
        
        logger.info(f"[{classification_id}] Starting craft classification for: {os.path.basename(image_path)}")
        
        try:
            # Validate input
            if not os.path.exists(image_path):
                return self._create_error_result(f"Image file not found: {image_path}")
            
            # Preprocess image
            img_array = self._preprocess_image(image_path)
            if img_array is None:
                return self._create_error_result("Image preprocessing failed")
            
            # Perform classification
            if self.has_fine_tuned_model:
                craft_result = self._classify_with_fine_tuned_model(img_array, classification_id)
            else:
                craft_result = self._classify_with_base_model_heuristics(img_array, classification_id)
            
            # Analyze non-craft content
            non_craft_analysis = self._analyze_non_craft_content(img_array, classification_id)
            
            # Combine results
            result = self._combine_classification_results(
                craft_result, non_craft_analysis, image_path, classification_id
            )
            
            # Add explanation if requested
            if explain_decision:
                result['explanation'] = self._generate_explanation(result)
            
            # Update metrics
            processing_time = time.time() - start_time
            self.classification_count += 1
            self.total_processing_time += processing_time
            
            result['processing_time'] = processing_time
            result['classification_id'] = classification_id
            
            logger.info(f"[{classification_id}] Classification completed in {processing_time:.3f}s")
            return result
            
        except Exception as e:
            logger.error(f"[{classification_id}] Classification error: {e}")
            return self._create_error_result(f"Classification exception: {str(e)}")
    
    def _preprocess_image(self, image_path):
        """Preprocess image for model input"""
        try:
            # Load and convert image
            img = Image.open(image_path)
            
            # Convert to RGB if needed
            if img.mode != 'RGB':
                img = img.convert('RGB')
            
            # Resize to MobileNet input size
            img = img.resize((224, 224), Image.LANCZOS)
            
            # Convert to array and preprocess
            img_array = np.array(img)
            img_array = np.expand_dims(img_array, axis=0)
            img_array = preprocess_input(img_array)
            
            return img_array
            
        except Exception as e:
            logger.error(f"Image preprocessing error: {e}")
            return None
    
    def _classify_with_fine_tuned_model(self, img_array, classification_id):
        """Classify using fine-tuned craft model"""
        try:
            logger.info(f"[{classification_id}] Using fine-tuned craft model")
            
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
            top_prediction = all_predictions[0]
            
            return {
                'success': True,
                'predicted_category': top_prediction['category'],
                'confidence': top_prediction['confidence'],
                'all_predictions': all_predictions,
                'model_used': 'fine_tuned_mobilenet'
            }
            
        except Exception as e:
            logger.error(f"[{classification_id}] Fine-tuned model error: {e}")
            return self._classify_with_base_model_heuristics(img_array, classification_id)
    
    def _classify_with_base_model_heuristics(self, img_array, classification_id):
        """Fallback classification using base MobileNet with keyword matching"""
        try:
            logger.info(f"[{classification_id}] Using base MobileNet with heuristics")
            
            # Get base model predictions
            predictions = self.base_model.predict(img_array, verbose=0)
            decoded = decode_predictions(predictions, top=20)[0]
            
            # Score craft categories based on keyword matching
            category_scores = {category: 0.0 for category in self.CRAFT_CATEGORIES.values()}
            
            for class_id, label_name, confidence in decoded:
                label_lower = label_name.lower().replace('_', ' ')
                
                # Check each craft category for keyword matches
                for category, keywords in self.CRAFT_KEYWORDS.items():
                    for keyword in keywords:
                        if keyword in label_lower:
                            # Boost score based on confidence and keyword relevance
                            boost = confidence * 0.7  # Reduce confidence for heuristic matching
                            category_scores[category] += boost
            
            # Find best match
            if max(category_scores.values()) > 0:
                best_category = max(category_scores, key=category_scores.get)
                best_confidence = min(category_scores[best_category], 0.8)  # Cap heuristic confidence
            else:
                # Default fallback
                best_category = 'hand_embroidery'
                best_confidence = 0.3
            
            # Create all predictions list
            all_predictions = []
            for category, score in sorted(category_scores.items(), key=lambda x: x[1], reverse=True):
                all_predictions.append({
                    'category': category,
                    'name': self.CATEGORY_NAMES.get(category, category),
                    'confidence': float(min(score, 0.8))
                })
            
            return {
                'success': True,
                'predicted_category': best_category,
                'confidence': best_confidence,
                'all_predictions': all_predictions,
                'model_used': 'base_mobilenet_heuristics',
                'imagenet_predictions': [(label, float(conf)) for _, label, conf in decoded[:5]]
            }
            
        except Exception as e:
            logger.error(f"[{classification_id}] Base model heuristics error: {e}")
            return {
                'success': False,
                'error': f"Heuristic classification failed: {str(e)}"
            }
    
    def _analyze_non_craft_content(self, img_array, classification_id):
        """Analyze if image contains non-craft content"""
        try:
            logger.info(f"[{classification_id}] Analyzing non-craft content")
            
            # Get base model predictions
            predictions = self.base_model.predict(img_array, verbose=0)
            decoded = decode_predictions(predictions, top=15)[0]
            
            non_craft_evidence = []
            max_non_craft_confidence = 0.0
            
            for class_id, label_name, confidence in decoded:
                label_lower = label_name.lower().replace('_', ' ')
                
                # Check against non-craft indicators
                for indicator in self.NON_CRAFT_INDICATORS:
                    if indicator in label_lower and confidence > 0.1:
                        non_craft_evidence.append({
                            'label': label_name,
                            'confidence': float(confidence),
                            'indicator': indicator
                        })
                        max_non_craft_confidence = max(max_non_craft_confidence, confidence)
            
            # Determine if image is craft-related
            is_craft_related = self._determine_craft_relatedness(
                non_craft_evidence, max_non_craft_confidence, decoded
            )
            
            return {
                'is_craft_related': is_craft_related,
                'non_craft_confidence': max_non_craft_confidence,
                'non_craft_evidence': non_craft_evidence,
                'imagenet_top_predictions': [(label, float(conf)) for _, label, conf in decoded[:5]]
            }
            
        except Exception as e:
            logger.error(f"[{classification_id}] Non-craft analysis error: {e}")
            return {
                'is_craft_related': True,  # Default to craft-related on error
                'non_craft_confidence': 0.0,
                'non_craft_evidence': [],
                'error': str(e)
            }
    
    def _determine_craft_relatedness(self, non_craft_evidence, max_confidence, all_predictions):
        """Determine if image is craft-related using improved logic"""
        
        # Count strong non-craft indicators
        strong_indicators = sum(1 for evidence in non_craft_evidence if evidence['confidence'] > 0.5)
        
        # Check for craft-related terms in predictions
        craft_indicators = 0
        for _, label, confidence in all_predictions:
            label_lower = label.lower()
            for category_keywords in self.CRAFT_KEYWORDS.values():
                if any(keyword in label_lower for keyword in category_keywords):
                    craft_indicators += confidence
        
        # Decision logic
        if max_confidence > 0.7 and strong_indicators >= 2:
            return False  # Strong evidence of non-craft content
        elif max_confidence > 0.5 and strong_indicators >= 1 and craft_indicators < 0.2:
            return False  # Moderate non-craft evidence, no craft indicators
        elif craft_indicators > 0.3:
            return True   # Some craft indicators found
        elif max_confidence < 0.3:
            return True   # Low non-craft confidence, assume craft-related
        else:
            return False  # Ambiguous, err on side of caution
    
    def _combine_classification_results(self, craft_result, non_craft_analysis, image_path, classification_id):
        """Combine craft classification and non-craft analysis"""
        
        result = {
            'success': craft_result.get('success', False),
            'classification_id': classification_id,
            'image_path': os.path.basename(image_path),
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S'),
            
            # Craft classification results
            'predicted_category': craft_result.get('predicted_category'),
            'confidence': craft_result.get('confidence', 0.0),
            'all_predictions': craft_result.get('all_predictions', []),
            
            # Non-craft analysis results
            'is_craft_related': non_craft_analysis.get('is_craft_related', True),
            'non_craft_confidence': non_craft_analysis.get('non_craft_confidence', 0.0),
            'non_craft_evidence': non_craft_analysis.get('non_craft_evidence', []),
            
            # Model information
            'model_used': craft_result.get('model_used', 'unknown'),
            'has_fine_tuned_model': self.has_fine_tuned_model,
            
            # Additional data for research
            'imagenet_predictions': craft_result.get('imagenet_predictions', []),
            'processing_metadata': {
                'total_classifications': self.classification_count,
                'average_processing_time': self.total_processing_time / max(self.classification_count, 1)
            }
        }
        
        # Add error information if classification failed
        if not craft_result.get('success', False):
            result['error_message'] = craft_result.get('error', 'Unknown classification error')
        
        return result
    
    def _generate_explanation(self, result):
        """Generate human-readable explanation of the classification decision"""
        
        if not result['success']:
            return f"Classification failed: {result.get('error_message', 'Unknown error')}"
        
        explanation_parts = []
        
        # Model information
        model_info = "fine-tuned MobileNet" if self.has_fine_tuned_model else "base MobileNet with heuristics"
        explanation_parts.append(f"Using {model_info}")
        
        # Classification result
        category = result['predicted_category']
        confidence = result['confidence']
        category_name = self.CATEGORY_NAMES.get(category, category)
        
        explanation_parts.append(f"Predicted category: {category_name} ({confidence:.1%} confidence)")
        
        # Craft-relatedness
        if result['is_craft_related']:
            explanation_parts.append("Image appears to be craft-related")
        else:
            non_craft_conf = result['non_craft_confidence']
            explanation_parts.append(f"Image may not be craft-related ({non_craft_conf:.1%} non-craft confidence)")
        
        # Evidence
        if result['non_craft_evidence']:
            top_evidence = sorted(result['non_craft_evidence'], key=lambda x: x['confidence'], reverse=True)[:2]
            evidence_labels = [e['label'] for e in top_evidence]
            explanation_parts.append(f"Detected: {', '.join(evidence_labels)}")
        
        return ". ".join(explanation_parts) + "."
    
    def _create_error_result(self, error_message):
        """Create standardized error result"""
        return {
            'success': False,
            'error_message': error_message,
            'predicted_category': None,
            'confidence': 0.0,
            'all_predictions': [],
            'is_craft_related': False,
            'non_craft_confidence': 0.0,
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
    
    def get_performance_stats(self):
        """Get classifier performance statistics"""
        return {
            'total_classifications': self.classification_count,
            'total_processing_time': self.total_processing_time,
            'average_processing_time': self.total_processing_time / max(self.classification_count, 1),
            'has_fine_tuned_model': self.has_fine_tuned_model,
            'model_path': self.model_path
        }

# Example usage for testing
if __name__ == "__main__":
    classifier = EnhancedCraftClassifier()
    
    # Test with a sample image (if provided)
    if len(sys.argv) > 1:
        test_image = sys.argv[1]
        if os.path.exists(test_image):
            result = classifier.classify_craft(test_image)
            print(json.dumps(result, indent=2))
        else:
            print(f"Test image not found: {test_image}")
    else:
        print("Enhanced Craft Classifier initialized successfully")
        print("Usage: python enhanced_craft_classifier.py <image_path>")