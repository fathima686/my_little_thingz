#!/usr/bin/env python3
"""
Enhanced Bayesian Classifier for Gift Category Prediction
Handles keyword-based category suggestions with high accuracy
"""

import re
import json
import numpy as np
from collections import defaultdict, Counter
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.naive_bayes import MultinomialNB
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
import joblib
import os
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

class EnhancedBayesianClassifier:
    """
    Enhanced Bayesian Classifier for Gift Category Prediction
    Uses keyword matching, TF-IDF, and semantic understanding
    """
    
    def __init__(self):
        self.model = None
        self.vectorizer = None
        self.category_keywords = {}
        self.category_mappings = {}
        self.training_data = []
        self.is_trained = False
        
        # Initialize category keywords and mappings
        self._initialize_category_data()
        
    def _initialize_category_data(self):
        """Initialize comprehensive category keywords and mappings"""
        
        # Define comprehensive keyword mappings for each category
        self.category_keywords = {
            'chocolate': {
                'keywords': [
                    'chocolate', 'sweet', 'candy', 'cocoa', 'milk chocolate', 'dark chocolate',
                    'white chocolate', 'truffle', 'praline', 'ganache', 'fudge', 'brownie',
                    'cookie', 'biscuit', 'dessert', 'treat', 'indulgence', 'sugar', 'caramel',
                    'toffee', 'nougat', 'mint', 'vanilla', 'strawberry', 'hazelnut', 'almond',
                    'walnut', 'peanut', 'm&m', 'kitkat', 'snickers', 'twix', 'mars', 'bounty',
                    'ferrero', 'lindt', 'cadbury', 'nestle', 'hershey', 'godiva', 'toblerone'
                ],
                'synonyms': ['sweet treat', 'confectionery', 'choc', 'candy bar', 'chocolate bar'],
                'related': ['gift box', 'hamper', 'basket', 'assorted', 'variety', 'collection']
            },
            'bouquet': {
                'keywords': [
                    'bouquet', 'flower', 'roses', 'lily', 'tulip', 'daisy', 'sunflower',
                    'orchid', 'carnation', 'gerbera', 'chrysanthemum', 'peony', 'hydrangea',
                    'arrangement', 'floral', 'bloom', 'petal', 'stem', 'vase', 'fresh',
                    'colorful', 'beautiful', 'romantic', 'love', 'anniversary', 'valentine',
                    'mother', 'birthday', 'celebration', 'wedding', 'bridal', 'centerpiece'
                ],
                'synonyms': ['flower arrangement', 'floral bouquet', 'flower basket', 'posy'],
                'related': ['gift', 'present', 'surprise', 'special', 'occasion']
            },
            'gift_box': {
                'keywords': [
                    'gift box', 'hamper', 'basket', 'package', 'box', 'container', 'basket',
                    'assorted', 'variety', 'collection', 'set', 'combo', 'mixed', 'selection',
                    'premium', 'luxury', 'deluxe', 'special', 'exclusive', 'curated', 'handpicked',
                    'gourmet', 'artisan', 'crafted', 'custom', 'personalized', 'unique'
                ],
                'synonyms': ['gift basket', 'gift hamper', 'gift set', 'gift package'],
                'related': ['present', 'surprise', 'celebration', 'occasion', 'special']
            },
            'wedding_card': {
                'keywords': [
                    'wedding', 'card', 'invitation', 'invite', 'ceremony', 'marriage', 'bride',
                    'groom', 'couple', 'union', 'matrimony', 'nuptials', 'reception', 'party',
                    'celebration', 'anniversary', 'engagement', 'proposal', 'love', 'romance',
                    'special day', 'big day', 'happily ever after', 'forever', 'together'
                ],
                'synonyms': ['wedding invitation', 'marriage card', 'nuptial card', 'wedding invite'],
                'related': ['celebration', 'party', 'event', 'special', 'occasion']
            },
            'custom_chocolate': {
                'keywords': [
                    'custom', 'personalized', 'bespoke', 'tailored', 'made to order', 'special order',
                    'unique', 'exclusive', 'one of a kind', 'handmade', 'artisan', 'crafted',
                    'engraved', 'printed', 'monogram', 'name', 'message', 'text', 'logo', 'design',
                    'photo', 'picture', 'image', 'customized', 'individual', 'personal'
                ],
                'synonyms': ['personalized chocolate', 'custom made', 'bespoke chocolate', 'tailored treat'],
                'related': ['chocolate', 'sweet', 'gift', 'present', 'special', 'unique']
            },
            'nuts': {
                'keywords': [
                    'nuts', 'almond', 'walnut', 'cashew', 'pistachio', 'hazelnut', 'pecan',
                    'macadamia', 'brazil', 'peanut', 'dry fruits', 'trail mix', 'mixed nuts',
                    'roasted', 'salted', 'unsalted', 'raw', 'organic', 'premium', 'gourmet',
                    'healthy', 'snack', 'protein', 'energy', 'natural', 'crunchy'
                ],
                'synonyms': ['dry fruits', 'trail mix', 'nut mix', 'mixed nuts', 'nut collection'],
                'related': ['healthy', 'snack', 'gift', 'present', 'nutrition', 'energy']
            }
        }
        
        # Create reverse mapping for quick lookup
        self.category_mappings = {}
        for category, data in self.category_keywords.items():
            for keyword in data['keywords'] + data['synonyms'] + data['related']:
                keyword_lower = keyword.lower()
                if keyword_lower not in self.category_mappings:
                    self.category_mappings[keyword_lower] = []
                self.category_mappings[keyword_lower].append(category)
    
    def _extract_features(self, text):
        """Extract features from text using multiple methods"""
        if not text:
            return []
        
        text_lower = text.lower()
        features = []
        
        # 1. Direct keyword matching
        for keyword, categories in self.category_mappings.items():
            if keyword in text_lower:
                features.extend([f"keyword_{cat}" for cat in categories])
        
        # 2. Partial word matching
        words = re.findall(r'\b\w+\b', text_lower)
        for word in words:
            if len(word) >= 3:  # Only consider words with 3+ characters
                for keyword, categories in self.category_mappings.items():
                    if keyword.startswith(word) or word.startswith(keyword):
                        features.extend([f"partial_{cat}" for cat in categories])
        
        # 3. Semantic similarity (simplified)
        semantic_features = self._extract_semantic_features(text_lower)
        features.extend(semantic_features)
        
        return features
    
    def _extract_semantic_features(self, text):
        """Extract semantic features based on context"""
        features = []
        
        # Sweetness indicators
        if any(word in text for word in ['sweet', 'sugar', 'candy', 'dessert', 'treat']):
            features.append('semantic_sweet')
        
        # Gift indicators
        if any(word in text for word in ['gift', 'present', 'surprise', 'special']):
            features.append('semantic_gift')
        
        # Celebration indicators
        if any(word in text for word in ['celebration', 'party', 'occasion', 'anniversary']):
            features.append('semantic_celebration')
        
        # Size indicators
        if any(word in text for word in ['large', 'big', 'huge', 'massive', 'giant']):
            features.append('semantic_large')
        elif any(word in text for word in ['small', 'mini', 'tiny', 'little']):
            features.append('semantic_small')
        
        # Quality indicators
        if any(word in text for word in ['premium', 'luxury', 'deluxe', 'high quality']):
            features.append('semantic_premium')
        
        return features
    
    def train_model(self, training_data=None):
        """Train the Bayesian model with enhanced features"""
        try:
            if training_data is None:
                training_data = self._generate_training_data()
            
            if not training_data:
                logger.warning("No training data available")
                return False
            
            # Prepare data
            texts = [item['text'] for item in training_data]
            labels = [item['category'] for item in training_data]
            
            # Create feature vectors
            feature_vectors = []
            for text in texts:
                features = self._extract_features(text)
                feature_vector = self._features_to_vector(features)
                feature_vectors.append(feature_vector)
            
            # Convert to numpy array
            X = np.array(feature_vectors)
            y = np.array(labels)
            
            # Split data
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42
            )
            
            # Train model
            self.model = MultinomialNB(alpha=0.1)
            self.model.fit(X_train, y_train)
            
            # Evaluate
            y_pred = self.model.predict(X_test)
            accuracy = accuracy_score(y_test, y_pred)
            
            logger.info(f"Bayesian model trained with accuracy: {accuracy:.3f}")
            self.is_trained = True
            
            # Save model
            self._save_model()
            
            return True
            
        except Exception as e:
            logger.error(f"Training error: {str(e)}")
            return False
    
    def _generate_training_data(self):
        """Generate comprehensive training data"""
        training_data = []
        
        # Generate training examples for each category
        for category, data in self.category_keywords.items():
            # Add direct keyword examples
            for keyword in data['keywords']:
                training_data.append({
                    'text': keyword,
                    'category': category
                })
            
            # Add phrase examples
            for keyword in data['keywords'][:10]:  # Limit to avoid too much data
                training_data.append({
                    'text': f"{keyword} gift",
                    'category': category
                })
                training_data.append({
                    'text': f"premium {keyword}",
                    'category': category
                })
                training_data.append({
                    'text': f"luxury {keyword} box",
                    'category': category
                })
        
        return training_data
    
    def _features_to_vector(self, features):
        """Convert features to numerical vector"""
        # Create a comprehensive feature vector
        all_possible_features = set()
        
        # Add all possible keyword features
        for category in self.category_keywords.keys():
            all_possible_features.add(f"keyword_{category}")
            all_possible_features.add(f"partial_{category}")
        
        # Add semantic features
        semantic_features = [
            'semantic_sweet', 'semantic_gift', 'semantic_celebration',
            'semantic_large', 'semantic_small', 'semantic_premium'
        ]
        all_possible_features.update(semantic_features)
        
        # Convert to sorted list for consistent indexing
        all_features = sorted(list(all_possible_features))
        
        # Create vector
        vector = [0] * len(all_features)
        for feature in features:
            if feature in all_features:
                idx = all_features.index(feature)
                vector[idx] = 1
        
        return vector
    
    def predict_category(self, text, confidence_threshold=0.6):
        """Predict category with enhanced accuracy"""
        try:
            if not self.is_trained:
                # Train model if not already trained
                if not self.train_model():
                    return self._fallback_prediction(text)
            
            # Extract features
            features = self._extract_features(text)
            feature_vector = self._features_to_vector(features)
            
            # Make prediction
            if self.model:
                prediction_proba = self.model.predict_proba([feature_vector])
                predicted_class = self.model.predict([feature_vector])[0]
                max_confidence = np.max(prediction_proba)
            else:
                return self._fallback_prediction(text)
            
            # Get all category probabilities
            categories = list(self.category_keywords.keys())
            probabilities = {}
            if self.model:
                for i, category in enumerate(categories):
                    if i < len(prediction_proba[0]):
                        probabilities[category] = float(prediction_proba[0][i])
            
            # Determine action based on confidence
            action = 'manual_review'
            if max_confidence >= confidence_threshold:
                action = 'auto_assign'
            elif max_confidence >= 0.4:
                action = 'suggest'
            
            return {
                'success': True,
                'predicted_category': predicted_class,
                'confidence': float(max_confidence),
                'confidence_percent': float(max_confidence * 100),
                'action': action,
                'probabilities': probabilities,
                'algorithm': 'Enhanced Bayesian Classifier',
                'features_used': len(features),
                'text_analyzed': text
            }
            
        except Exception as e:
            logger.error(f"Prediction error: {str(e)}")
            return self._fallback_prediction(text)
    
    def _fallback_prediction(self, text):
        """Fallback prediction using keyword matching"""
        if not text:
            return {
                'success': False,
                'error': 'No text provided',
                'predicted_category': 'unknown',
                'confidence': 0.0
            }
        
        text_lower = text.lower()
        category_scores = defaultdict(int)
        
        # Score each category based on keyword matches
        for category, data in self.category_keywords.items():
            score = 0
            for keyword in data['keywords']:
                if keyword in text_lower:
                    score += 2  # Direct match
                elif any(word in text_lower for word in keyword.split()):
                    score += 1  # Partial match
            
            category_scores[category] = score
        
        # Find best category
        if category_scores:
            best_category = max(category_scores.items(), key=lambda x: x[1])
            confidence = min(0.8, best_category[1] / 10)  # Normalize to 0-0.8
            
            return {
                'success': True,
                'predicted_category': best_category[0],
                'confidence': confidence,
                'confidence_percent': confidence * 100,
                'action': 'suggest',
                'algorithm': 'Keyword Matching Fallback',
                'text_analyzed': text
            }
        else:
            return {
                'success': True,
                'predicted_category': 'gift_box',  # Default category
                'confidence': 0.3,
                'confidence_percent': 30,
                'action': 'manual_review',
                'algorithm': 'Default Fallback',
                'text_analyzed': text
            }
    
    def _save_model(self):
        """Save trained model"""
        try:
            os.makedirs('models', exist_ok=True)
            if self.model:
                joblib.dump(self.model, 'models/enhanced_bayesian_model.pkl')
            logger.info("Model saved successfully")
        except Exception as e:
            logger.error(f"Error saving model: {str(e)}")
    
    def _load_model(self):
        """Load trained model"""
        try:
            model_path = 'models/enhanced_bayesian_model.pkl'
            if os.path.exists(model_path):
                self.model = joblib.load(model_path)
                self.is_trained = True
                logger.info("Model loaded successfully")
                return True
        except Exception as e:
            logger.error(f"Error loading model: {str(e)}")
        return False

# Test the enhanced classifier
if __name__ == "__main__":
    classifier = EnhancedBayesianClassifier()
    
    # Test cases
    test_cases = [
        "sweet chocolate",
        "flower bouquet",
        "gift box",
        "wedding card",
        "custom chocolate",
        "mixed nuts",
        "premium chocolate",
        "romantic flowers",
        "luxury gift hamper",
        "anniversary card"
    ]
    
    print("Testing Enhanced Bayesian Classifier:")
    print("=" * 50)
    
    for test_text in test_cases:
        result = classifier.predict_category(test_text)
        print(f"Text: '{test_text}'")
        print(f"Predicted: {result['predicted_category']}")
        print(f"Confidence: {result['confidence_percent']:.1f}%")
        print(f"Action: {result['action']}")
        print("-" * 30)





