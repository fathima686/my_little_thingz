#!/usr/bin/env python3
"""
Enhanced SVM Classifier for Budget vs Premium Gift Classification
Specifically designed for artwork gallery products
"""

import numpy as np
import pandas as pd
from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import joblib
import os
from datetime import datetime
import logging
import json

logger = logging.getLogger(__name__)

class BudgetPremiumSVMClassifier:
    """
    SVM Classifier for Budget vs Premium Gift Classification
    Draws a line/boundary to separate gifts into Budget vs Premium categories
    """
    
    def __init__(self):
        self.model = None
        self.scaler = None
        self.is_trained = False
        self.feature_names = [
            'price', 'title_length', 'description_length', 'category_id',
            'luxury_keywords', 'premium_indicators', 'availability_score',
            'customization_level', 'material_quality', 'brand_premium'
        ]
        
        # Initialize with sample training data
        self._initialize_training_data()
        
    def _initialize_training_data(self):
        """Initialize with sample training data for artwork gallery"""
        
        # Sample training data based on typical artwork gallery products
        self.training_data = [
            # Budget products (price < 1000)
            {'title': 'Simple Photo Frame', 'description': 'Basic wooden frame', 'price': 299, 'category_id': 1, 'availability': 'in_stock'},
            {'title': 'Standard Gift Box', 'description': 'Regular gift packaging', 'price': 199, 'category_id': 2, 'availability': 'in_stock'},
            {'title': 'Basic Wedding Card', 'description': 'Simple invitation card', 'price': 149, 'category_id': 3, 'availability': 'in_stock'},
            {'title': 'Small Bouquet', 'description': 'Fresh flower arrangement', 'price': 399, 'category_id': 4, 'availability': 'in_stock'},
            {'title': 'Mini Frame Set', 'description': 'Set of small frames', 'price': 249, 'category_id': 1, 'availability': 'in_stock'},
            {'title': 'Basic Hamper', 'description': 'Simple gift hamper', 'price': 599, 'category_id': 2, 'availability': 'in_stock'},
            {'title': 'Standard Album', 'description': 'Photo album', 'price': 349, 'category_id': 5, 'availability': 'in_stock'},
            {'title': 'Simple Chocolate Box', 'description': 'Basic chocolate collection', 'price': 449, 'category_id': 6, 'availability': 'in_stock'},
            
            # Premium products (price >= 1000)
            {'title': 'Luxury Photo Frame', 'description': 'Premium wooden frame with gold accents', 'price': 1299, 'category_id': 1, 'availability': 'limited'},
            {'title': 'Premium Gift Box', 'description': 'Deluxe gift packaging with premium materials', 'price': 1599, 'category_id': 2, 'availability': 'limited'},
            {'title': 'Designer Wedding Card', 'description': 'Custom designed invitation with premium paper', 'price': 899, 'category_id': 3, 'availability': 'limited'},
            {'title': 'Premium Bouquet', 'description': 'Exotic flower arrangement with premium flowers', 'price': 1899, 'category_id': 4, 'availability': 'limited'},
            {'title': 'Luxury Frame Collection', 'description': 'Premium frame set with designer materials', 'price': 2299, 'category_id': 1, 'availability': 'limited'},
            {'title': 'Deluxe Hamper', 'description': 'Premium gift hamper with luxury items', 'price': 2999, 'category_id': 2, 'availability': 'limited'},
            {'title': 'Premium Album', 'description': 'Luxury photo album with leather binding', 'price': 1499, 'category_id': 5, 'availability': 'limited'},
            {'title': 'Artisan Chocolate Box', 'description': 'Handcrafted premium chocolate collection', 'price': 1799, 'category_id': 6, 'availability': 'limited'},
            {'title': 'Custom Engraved Frame', 'description': 'Personalized frame with laser engraving', 'price': 1199, 'category_id': 1, 'availability': 'limited'},
            {'title': 'Exclusive Gift Set', 'description': 'Limited edition gift collection', 'price': 2499, 'category_id': 2, 'availability': 'limited'},
            {'title': 'Designer Wedding Suite', 'description': 'Complete wedding stationery set', 'price': 1999, 'category_id': 3, 'availability': 'limited'},
            {'title': 'Luxury Flower Arrangement', 'description': 'Premium floral centerpiece', 'price': 2599, 'category_id': 4, 'availability': 'limited'}
        ]
    
    def _extract_features(self, product_data):
        """Extract features from product data"""
        features = []
        
        # Price feature
        price = float(product_data.get('price', 0))
        features.append(price)
        
        # Title length
        title_length = len(str(product_data.get('title', '')))
        features.append(title_length)
        
        # Description length
        desc_length = len(str(product_data.get('description', '')))
        features.append(desc_length)
        
        # Category ID
        category_id = int(product_data.get('category_id', 1))
        features.append(category_id)
        
        # Luxury keywords score
        luxury_keywords = self._get_luxury_keywords_score(product_data)
        features.append(luxury_keywords)
        
        # Premium indicators score
        premium_indicators = self._get_premium_indicators_score(product_data)
        features.append(premium_indicators)
        
        # Availability score
        availability_score = self._get_availability_score(product_data.get('availability', 'in_stock'))
        features.append(availability_score)
        
        # Customization level
        customization_level = self._get_customization_level(product_data)
        features.append(customization_level)
        
        # Material quality score
        material_quality = self._get_material_quality_score(product_data)
        features.append(material_quality)
        
        # Brand premium score
        brand_premium = self._get_brand_premium_score(product_data)
        features.append(brand_premium)
        
        return np.array(features)
    
    def _get_luxury_keywords_score(self, product_data):
        """Calculate luxury keywords score"""
        luxury_keywords = [
            'luxury', 'premium', 'deluxe', 'exclusive', 'designer', 'artisan',
            'handcrafted', 'bespoke', 'custom', 'personalized', 'gold', 'silver',
            'leather', 'crystal', 'diamond', 'platinum', 'exotic', 'rare'
        ]
        
        text = f"{product_data.get('title', '')} {product_data.get('description', '')}".lower()
        score = sum(1 for keyword in luxury_keywords if keyword in text)
        return min(score, 5)  # Cap at 5
    
    def _get_premium_indicators_score(self, product_data):
        """Calculate premium indicators score"""
        premium_indicators = [
            'limited', 'exclusive', 'edition', 'collection', 'suite', 'set',
            'premium', 'luxury', 'deluxe', 'designer', 'artisan'
        ]
        
        text = f"{product_data.get('title', '')} {product_data.get('description', '')}".lower()
        score = sum(1 for indicator in premium_indicators if indicator in text)
        return min(score, 5)  # Cap at 5
    
    def _get_availability_score(self, availability):
        """Convert availability to numeric score"""
        availability_scores = {
            'in_stock': 1,
            'limited': 3,
            'out_of_stock': 0,
            'pre_order': 2
        }
        return availability_scores.get(availability, 1)
    
    def _get_customization_level(self, product_data):
        """Calculate customization level score"""
        customization_keywords = [
            'custom', 'personalized', 'bespoke', 'tailored', 'engraved',
            'printed', 'monogram', 'name', 'message', 'design'
        ]
        
        text = f"{product_data.get('title', '')} {product_data.get('description', '')}".lower()
        score = sum(1 for keyword in customization_keywords if keyword in text)
        return min(score, 3)  # Cap at 3
    
    def _get_material_quality_score(self, product_data):
        """Calculate material quality score"""
        premium_materials = [
            'wood', 'leather', 'crystal', 'glass', 'metal', 'gold', 'silver',
            'premium', 'quality', 'durable', 'handcrafted', 'artisan'
        ]
        
        text = f"{product_data.get('title', '')} {product_data.get('description', '')}".lower()
        score = sum(1 for material in premium_materials if material in text)
        return min(score, 4)  # Cap at 4
    
    def _get_brand_premium_score(self, product_data):
        """Calculate brand premium score"""
        # This would typically use brand data, for now using title/description analysis
        premium_brands = [
            'designer', 'artisan', 'luxury', 'premium', 'exclusive', 'boutique'
        ]
        
        text = f"{product_data.get('title', '')} {product_data.get('description', '')}".lower()
        score = sum(1 for brand in premium_brands if brand in text)
        return min(score, 3)  # Cap at 3
    
    def train_model(self):
        """Train the SVM model"""
        try:
            # Prepare training data
            X = []
            y = []
            
            for product in self.training_data:
                features = self._extract_features(product)
                X.append(features)
                
                # Binary classification: 1 for Premium (price >= 1000), 0 for Budget
                is_premium = 1 if product['price'] >= 1000 else 0
                y.append(is_premium)
            
            X = np.array(X)
            y = np.array(y)
            
            # Scale features
            self.scaler = StandardScaler()
            X_scaled = self.scaler.fit_transform(X)
            
            # Split data
            X_train, X_test, y_train, y_test = train_test_split(
                X_scaled, y, test_size=0.2, random_state=42
            )
            
            # Train SVM model
            self.model = SVC(
                kernel='rbf',
                probability=True,
                random_state=42,
                C=1.0,
                gamma='scale'
            )
            
            self.model.fit(X_train, y_train)
            
            # Evaluate model
            y_pred = self.model.predict(X_test)
            accuracy = accuracy_score(y_test, y_pred)
            
            logger.info(f"SVM Model trained with accuracy: {accuracy:.3f}")
            logger.info(f"Classification Report:\n{classification_report(y_test, y_pred)}")
            
            self.is_trained = True
            
            # Save model
            self._save_model()
            
            return True
            
        except Exception as e:
            logger.error(f"SVM Training error: {str(e)}")
            return False
    
    def classify_gift(self, product_data):
        """Classify a gift as Budget or Premium"""
        try:
            if not self.is_trained:
                if not self.train_model():
                    return self._fallback_classification(product_data)
            
            # Extract features
            features = self._extract_features(product_data)
            features_scaled = self.scaler.transform([features])
            
            # Make prediction
            prediction = self.model.predict(features_scaled)[0]
            prediction_proba = self.model.predict_proba(features_scaled)[0]
            
            # Get confidence
            confidence = float(np.max(prediction_proba))
            predicted_class = 'Premium' if prediction == 1 else 'Budget'
            
            # Get reasoning
            reasoning = self._get_classification_reasoning(features, prediction)
            
            return {
                'success': True,
                'prediction': predicted_class,
                'confidence': confidence,
                'confidence_percent': confidence * 100,
                'score': float(prediction),
                'reasoning': reasoning,
                'features_analyzed': dict(zip(self.feature_names, features)),
                'algorithm': 'Support Vector Machine',
                'boundary_distance': float(self.model.decision_function(features_scaled)[0])
            }
            
        except Exception as e:
            logger.error(f"SVM Classification error: {str(e)}")
            return self._fallback_classification(product_data)
    
    def _get_classification_reasoning(self, features, prediction):
        """Generate reasoning for the classification"""
        reasoning = []
        
        price = features[0]
        luxury_keywords = features[4]
        premium_indicators = features[5]
        availability_score = features[6]
        customization_level = features[7]
        material_quality = features[8]
        
        if prediction == 1:  # Premium
            if price >= 1000:
                reasoning.append(f"High price (₹{price}) indicates premium positioning")
            if luxury_keywords > 0:
                reasoning.append(f"Contains {luxury_keywords} luxury keywords")
            if premium_indicators > 0:
                reasoning.append(f"Has {premium_indicators} premium indicators")
            if availability_score >= 3:
                reasoning.append("Limited availability suggests exclusivity")
            if customization_level > 0:
                reasoning.append(f"Customization level ({customization_level}) adds value")
            if material_quality > 0:
                reasoning.append(f"Premium materials mentioned ({material_quality})")
        else:  # Budget
            if price < 1000:
                reasoning.append(f"Price (₹{price}) is in budget range")
            if luxury_keywords == 0:
                reasoning.append("No luxury keywords detected")
            if premium_indicators == 0:
                reasoning.append("No premium indicators found")
            if availability_score == 1:
                reasoning.append("Standard availability suggests mass market")
        
        return reasoning
    
    def _fallback_classification(self, product_data):
        """Fallback classification based on price"""
        price = float(product_data.get('price', 0))
        predicted_class = 'Premium' if price >= 1000 else 'Budget'
        confidence = 0.8 if price >= 1000 or price < 1000 else 0.5
        
        return {
            'success': True,
            'prediction': predicted_class,
            'confidence': confidence,
            'confidence_percent': confidence * 100,
            'score': 1 if price >= 1000 else 0,
            'reasoning': [f"Price-based classification: ₹{price}"],
            'algorithm': 'Price-based Fallback'
        }
    
    def _save_model(self):
        """Save trained model"""
        try:
            os.makedirs('models', exist_ok=True)
            model_data = {
                'model': self.model,
                'scaler': self.scaler,
                'feature_names': self.feature_names,
                'trained_at': datetime.now().isoformat()
            }
            joblib.dump(model_data, 'models/budget_premium_svm.pkl')
            logger.info("SVM model saved successfully")
        except Exception as e:
            logger.error(f"Error saving SVM model: {str(e)}")
    
    def _load_model(self):
        """Load trained model"""
        try:
            model_path = 'models/budget_premium_svm.pkl'
            if os.path.exists(model_path):
                model_data = joblib.load(model_path)
                self.model = model_data['model']
                self.scaler = model_data['scaler']
                self.feature_names = model_data['feature_names']
                self.is_trained = True
                logger.info("SVM model loaded successfully")
                return True
        except Exception as e:
            logger.error(f"Error loading SVM model: {str(e)}")
        return False

# Test the SVM classifier
if __name__ == "__main__":
    # Initialize the classifier
    classifier = BudgetPremiumSVMClassifier()
    
    # Test with sample products
    test_products = [
        {
            'title': 'Simple Photo Frame',
            'description': 'Basic wooden frame',
            'price': 299,
            'category_id': 1,
            'availability': 'in_stock'
        },
        {
            'title': 'Luxury Designer Frame',
            'description': 'Premium wooden frame with gold accents and handcrafted details',
            'price': 1299,
            'category_id': 1,
            'availability': 'limited'
        },
        {
            'title': 'Custom Engraved Gift Box',
            'description': 'Personalized gift box with laser engraving and premium materials',
            'price': 899,
            'category_id': 2,
            'availability': 'limited'
        },
        {
            'title': 'Standard Wedding Card',
            'description': 'Simple invitation card',
            'price': 149,
            'category_id': 3,
            'availability': 'in_stock'
        }
    ]
    
    print("🎯 SVM Budget vs Premium Classifier")
    print("=" * 50)
    print("Testing gift classification...")
    print()
    
    for i, product in enumerate(test_products, 1):
        result = classifier.classify_gift(product)
        
        if result['success']:
            print(f"🔍 Test {i}: {product['title']}")
            print(f"   Price: ₹{product['price']}")
            print(f"   Classification: {result['prediction']}")
            print(f"   Confidence: {result['confidence_percent']:.1f}%")
            print(f"   Reasoning: {', '.join(result['reasoning'])}")
            print()
    
    print("🎉 SVM Classifier is ready!")
    print("This classifier draws a boundary to separate gifts into Budget vs Premium categories.")


