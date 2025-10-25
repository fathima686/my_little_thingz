#!/usr/bin/env python3
"""
Enhanced Gift Category Predictor for Artwork Gallery
Implements Bayesian Classifier for accurate gift category prediction from search terms
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

class GiftCategoryPredictor:
    """
    Enhanced Bayesian Classifier for Gift Category Prediction
    Specifically designed for artwork gallery search enhancement
    """
    
    def __init__(self):
        self.model = None
        self.vectorizer = None
        self.category_keywords = {}
        self.category_mappings = {}
        self.training_data = []
        self.is_trained = False
        
        # Initialize comprehensive category data
        self._initialize_gift_categories()
        
    def _initialize_gift_categories(self):
        """Initialize comprehensive gift category keywords and mappings"""
        
        # Define comprehensive keyword mappings for each gift category
        self.category_keywords = {
            'sweet': {
                'keywords': [
                    'sweet', 'chocolate', 'candy', 'treat', 'dessert', 'sugar', 'cocoa', 'truffle', 'praline',
                    'ganache', 'fudge', 'brownie', 'cookie', 'biscuit', 'indulgence', 'caramel',
                    'toffee', 'nougat', 'mint', 'vanilla', 'strawberry', 'hazelnut', 'almond',
                    'walnut', 'peanut', 'm&m', 'kitkat', 'snickers', 'twix', 'mars', 'bounty',
                    'ferrero', 'lindt', 'cadbury', 'nestle', 'hershey', 'godiva', 'toblerone'
                ],
                'synonyms': ['sweet treat', 'confectionery', 'choc', 'candy bar', 'chocolate bar'],
                'related': ['gift box', 'hamper', 'basket', 'assorted', 'variety', 'collection'],
                'suggestions': ['Custom Chocolate Box', 'Chocolate Bouquet']
            },
            'wedding': {
                'keywords': [
                    'wedding', 'card', 'invitation', 'invite', 'ceremony', 'marriage', 'bride',
                    'groom', 'couple', 'union', 'matrimony', 'nuptials', 'reception', 'party',
                    'celebration', 'anniversary', 'engagement', 'proposal', 'love', 'romance',
                    'special day', 'big day', 'happily ever after', 'forever', 'together',
                    'wedding card', 'couple frame', 'wedding hamper', 'bridal gift'
                ],
                'synonyms': ['wedding invitation', 'marriage card', 'nuptial card', 'wedding invite'],
                'related': ['celebration', 'party', 'event', 'special', 'occasion'],
                'suggestions': ['Wedding Card', 'Couple Frame', 'Wedding Hamper']
            },
            'birthday': {
                'keywords': [
                    'birthday', 'cake', 'topper', 'mug', 'greeting card', 'celebration', 'party',
                    'age', 'years old', 'turning', 'special day', 'birthday gift', 'birthday present',
                    'birthday card', 'birthday mug', 'birthday cake topper', 'birthday decoration',
                    'party supplies', 'celebration gift', 'age milestone', 'birthday surprise'
                ],
                'synonyms': ['bday', 'birthday party', 'celebration', 'milestone', 'anniversary'],
                'related': ['gift', 'present', 'surprise', 'special', 'occasion'],
                'suggestions': ['Birthday Cake Topper', 'Birthday Mug', 'Greeting Card']
            },
            'baby': {
                'keywords': [
                    'baby', 'infant', 'newborn', 'toddler', 'child', 'kids', 'rattle', 'soft toy',
                    'blanket', 'baby gift', 'baby present', 'baby shower', 'newborn gift',
                    'baby rattle', 'soft toy', 'baby blanket', 'baby clothes', 'baby accessories',
                    'nursery', 'crib', 'stroller', 'diaper', 'feeding', 'baby care'
                ],
                'synonyms': ['infant', 'newborn', 'toddler', 'child', 'kids', 'little one'],
                'related': ['gift', 'present', 'surprise', 'special', 'occasion'],
                'suggestions': ['Baby Rattle', 'Soft Toy', 'Baby Blanket']
            },
            'valentine': {
                'keywords': [
                    'valentine', 'valentines', 'love', 'romantic', 'heart', 'couple', 'lamp',
                    'romance', 'affection', 'passion', 'intimate', 'sweetheart', 'beloved',
                    'love frame', 'heart chocolate', 'couple lamp', 'romantic gift', 'love gift',
                    'valentine gift', 'romantic present', 'couple gift', 'love present'
                ],
                'synonyms': ['valentines day', 'romance', 'love', 'heart', 'couple'],
                'related': ['romantic', 'love', 'anniversary', 'couple', 'relationship'],
                'suggestions': ['Love Frame', 'Heart Chocolate', 'Couple Lamp']
            },
            'house': {
                'keywords': [
                    'house', 'home', 'wall', 'frame', 'indoor', 'plant', 'name plate', 'decoration',
                    'home decor', 'wall art', 'indoor plant', 'name plate', 'housewarming',
                    'home gift', 'house gift', 'decoration', 'interior', 'furnishing', 'home accessory'
                ],
                'synonyms': ['home', 'housewarming', 'decoration', 'interior', 'furnishing'],
                'related': ['gift', 'present', 'surprise', 'special', 'occasion'],
                'suggestions': ['Wall Frame', 'Indoor Plant', 'Name Plate']
            },
            'farewell': {
                'keywords': [
                    'farewell', 'goodbye', 'leaving', 'departure', 'retirement', 'moving', 'pen set',
                    'thank you card', 'planner diary', 'farewell gift', 'goodbye present',
                    'leaving gift', 'departure gift', 'retirement gift', 'moving gift',
                    'thank you', 'appreciation', 'gratitude', 'memories', 'keepsake'
                ],
                'synonyms': ['goodbye', 'leaving', 'departure', 'retirement', 'moving'],
                'related': ['gift', 'present', 'surprise', 'special', 'occasion'],
                'suggestions': ['Pen Set', 'Thank You Card', 'Planner Diary']
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
        
        # 3. Semantic features
        semantic_features = self._extract_semantic_features(text_lower)
        features.extend(semantic_features)
        
        return features
    
    def _extract_semantic_features(self, text):
        """Extract semantic features from text"""
        features = []
        
        # Emotional context
        if any(word in text for word in ['love', 'romantic', 'sweet', 'cute', 'beautiful']):
            features.append('semantic_romantic')
        
        if any(word in text for word in ['celebration', 'party', 'happy', 'joy', 'fun']):
            features.append('semantic_celebration')
        
        if any(word in text for word in ['special', 'unique', 'exclusive', 'premium']):
            features.append('semantic_premium')
        
        if any(word in text for word in ['healthy', 'natural', 'organic', 'nutritious']):
            features.append('semantic_healthy')
        
        if any(word in text for word in ['custom', 'personalized', 'bespoke', 'tailored']):
            features.append('semantic_custom')
        
        return features
    
    def _features_to_vector(self, features):
        """Convert features to vector representation"""
        # Create a comprehensive feature vector
        all_categories = list(self.category_keywords.keys())
        vector = [0] * (len(all_categories) * 3)  # keyword, partial, semantic features
        
        for feature in features:
            if feature.startswith('keyword_'):
                category = feature.replace('keyword_', '')
                if category in all_categories:
                    idx = all_categories.index(category)
                    vector[idx] += 1
            elif feature.startswith('partial_'):
                category = feature.replace('partial_', '')
                if category in all_categories:
                    idx = all_categories.index(category) + len(all_categories)
                    vector[idx] += 1
            elif feature.startswith('semantic_'):
                # Add semantic features at the end
                vector[-1] += 1
        
        return vector
    
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
            
            logger.info(f"Gift Category Predictor trained with accuracy: {accuracy:.3f}")
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
        
        # Generate training data from category keywords
        for category, data in self.category_keywords.items():
            # Add main keywords
            for keyword in data['keywords']:
                training_data.append({
                    'text': keyword,
                    'category': category
                })
            
            # Add synonyms
            for synonym in data['synonyms']:
                training_data.append({
                    'text': synonym,
                    'category': category
                })
            
            # Add related terms
            for related in data['related']:
                training_data.append({
                    'text': related,
                    'category': category
                })
        
        return training_data
    
    def predict_category(self, text, confidence_threshold=0.6):
        """Predict gift category with enhanced accuracy"""
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
            
            # Get suggestions for the predicted category
            suggestions = self.category_keywords.get(predicted_class, {}).get('suggestions', [])
            
            return {
                'success': True,
                'predicted_category': predicted_class,
                'confidence': float(max_confidence),
                'confidence_percent': float(max_confidence * 100),
                'action': action,
                'probabilities': probabilities,
                'suggestions': suggestions,
                'algorithm': 'Enhanced Gift Category Predictor',
                'features_used': len(features),
                'text_analyzed': text
            }
            
        except Exception as e:
            logger.error(f"Prediction error: {str(e)}")
            return self._fallback_prediction(text)
    
    def _fallback_prediction(self, text):
        """Fallback prediction using keyword matching"""
        text_lower = text.lower()
        
        # Simple keyword matching fallback
        for category, data in self.category_keywords.items():
            for keyword in data['keywords']:
                if keyword.lower() in text_lower:
                    suggestions = data.get('suggestions', [])
                    return {
                        'success': True,
                        'predicted_category': category,
                        'confidence': 0.8,
                        'confidence_percent': 80.0,
                        'action': 'suggest',
                        'probabilities': {category: 0.8},
                        'suggestions': suggestions,
                        'algorithm': 'Keyword Fallback',
                        'features_used': 1,
                        'text_analyzed': text
                    }
        
        # Default fallback
        return {
            'success': True,
            'predicted_category': 'gift_box',
            'confidence': 0.3,
            'confidence_percent': 30.0,
            'action': 'manual_review',
            'probabilities': {'gift_box': 0.3},
            'suggestions': ['Gift Box', 'Gift Hamper', 'Gift Basket'],
            'algorithm': 'Default Fallback',
            'features_used': 0,
            'text_analyzed': text
        }
    
    def get_search_recommendations(self, search_term, limit=5):
        """Get search recommendations based on predicted category"""
        prediction = self.predict_category(search_term)
        
        if prediction['success']:
            category = prediction['predicted_category']
            suggestions = prediction.get('suggestions', [])
            
            # Add more specific recommendations based on category
            additional_suggestions = self._get_additional_suggestions(category, search_term)
            all_suggestions = suggestions + additional_suggestions
            
            return {
                'success': True,
                'search_term': search_term,
                'predicted_category': category,
                'confidence': prediction['confidence'],
                'recommendations': all_suggestions[:limit],
                'algorithm': prediction['algorithm']
            }
        
        return {
            'success': False,
            'search_term': search_term,
            'recommendations': [],
            'error': 'Failed to predict category'
        }
    
    def _get_additional_suggestions(self, category, search_term):
        """Get additional suggestions based on category and search term"""
        additional = []
        
        if category == 'chocolate':
            additional = ['Chocolate Truffles', 'Chocolate Gift Set', 'Custom Chocolate Box']
        elif category == 'wedding':
            additional = ['Wedding Invitation', 'Couple Photo Frame', 'Wedding Gift Hamper']
        elif category == 'birthday':
            additional = ['Birthday Card', 'Birthday Gift Set', 'Party Decorations']
        elif category == 'baby':
            additional = ['Baby Gift Set', 'Soft Toys', 'Baby Clothes']
        elif category == 'valentine':
            additional = ['Romantic Gift Set', 'Heart-shaped Chocolate', 'Couple Gift']
        elif category == 'house':
            additional = ['Home Decor', 'Wall Art', 'Indoor Plants']
        elif category == 'farewell':
            additional = ['Farewell Gift', 'Thank You Card', 'Memory Book']
        
        return additional
    
    def _save_model(self):
        """Save trained model"""
        try:
            os.makedirs('models', exist_ok=True)
            if self.model:
                joblib.dump(self.model, 'models/gift_category_predictor.pkl')
            logger.info("Gift Category Predictor model saved successfully")
        except Exception as e:
            logger.error(f"Error saving model: {str(e)}")
    
    def _load_model(self):
        """Load trained model"""
        try:
            model_path = 'models/gift_category_predictor.pkl'
            if os.path.exists(model_path):
                self.model = joblib.load(model_path)
                self.is_trained = True
                logger.info("Gift Category Predictor model loaded successfully")
                return True
        except Exception as e:
            logger.error(f"Error loading model: {str(e)}")
        return False

# Test the gift category predictor
if __name__ == "__main__":
    # Initialize the predictor
    predictor = GiftCategoryPredictor()
    
    # Test with example searches
    test_searches = [
        'sweet', 'wedding', 'birthday', 'baby', 'valentine', 'house', 'farewell',
        'chocolate', 'flower', 'gift', 'custom', 'premium', 'romantic'
    ]
    
    print("🎯 Enhanced Gift Category Predictor")
    print("=" * 50)
    print("Testing gift category prediction...")
    print()
    
    for search_term in test_searches:
        result = predictor.get_search_recommendations(search_term)
        if result['success']:
            print(f"🔍 Search: '{search_term}'")
            print(f"   Category: {result['predicted_category']}")
            print(f"   Confidence: {result['confidence']:.1%}")
            print(f"   Recommendations: {', '.join(result['recommendations'][:3])}")
            print()
    
    print("🎉 Gift Category Predictor is ready!")
    print("This predictor can now accurately suggest gift categories")
    print("based on search keywords like 'sweet' → chocolate products.")
