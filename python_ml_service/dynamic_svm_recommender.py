"""
Dynamic SVM-based Gift Recommendation System
Handles real-time updates based on new purchases and user behavior
"""

import numpy as np
import pandas as pd
from sklearn.svm import SVC
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
from sklearn.ensemble import RandomForestClassifier
from sklearn.linear_model import SGDClassifier
import joblib
import os
import json
from datetime import datetime, timedelta
import logging
from typing import Dict, List, Tuple, Optional, Any
import threading
import time
from collections import defaultdict, deque

logger = logging.getLogger(__name__)

class DynamicSVMRecommender:
    """
    Dynamic SVM-based recommendation system that adapts to new user behavior
    """
    
    def __init__(self, config):
        self.config = config
        self.models_dir = config.MODELS_DIR
        self.cache_dir = config.CACHE_DIR
        
        # Ensure directories exist
        os.makedirs(self.models_dir, exist_ok=True)
        os.makedirs(self.cache_dir, exist_ok=True)
        
        # Model components
        self.svm_model = None
        self.scaler = StandardScaler()
        self.label_encoders = {}
        self.feature_columns = []
        
        # Dynamic learning parameters
        self.min_samples_for_retrain = 10
        self.retrain_threshold = 0.1  # Retrain if accuracy drops by 10%
        self.last_retrain_time = None
        self.model_version = 0
        
        # User behavior tracking
        self.user_interactions = defaultdict(list)
        self.user_preferences = defaultdict(dict)
        self.recent_purchases = deque(maxlen=1000)
        
        # Threading for background retraining
        self.retrain_lock = threading.Lock()
        self.background_retrain = True
        
        # Load existing model or create new one
        self.load_or_create_model()
    
    def load_or_create_model(self):
        """Load existing model or create a new one"""
        model_path = os.path.join(self.models_dir, 'dynamic_svm_model.pkl')
        
        if os.path.exists(model_path):
            try:
                model_data = joblib.load(model_path)
                self.svm_model = model_data['model']
                self.scaler = model_data['scaler']
                self.label_encoders = model_data['label_encoders']
                self.feature_columns = model_data['feature_columns']
                self.model_version = model_data.get('version', 0)
                self.last_retrain_time = model_data.get('last_retrain_time')
                
                logger.info(f"Loaded SVM model version {self.model_version}")
            except Exception as e:
                logger.error(f"Failed to load model: {e}")
                self._create_new_model()
        else:
            self._create_new_model()
    
    def _create_new_model(self):
        """Create a new SVM model"""
        self.svm_model = SVC(
            kernel='rbf',
            probability=True,
            random_state=42,
            C=1.0,
            gamma='scale'
        )
        self.model_version = 0
        self.last_retrain_time = datetime.now()
        logger.info("Created new SVM model")
    
    def save_model(self):
        """Save the current model"""
        model_path = os.path.join(self.models_dir, 'dynamic_svm_model.pkl')
        
        model_data = {
            'model': self.svm_model,
            'scaler': self.scaler,
            'label_encoders': self.label_encoders,
            'feature_columns': self.feature_columns,
            'version': self.model_version,
            'last_retrain_time': self.last_retrain_time
        }
        
        joblib.dump(model_data, model_path)
        logger.info(f"Saved SVM model version {self.model_version}")
    
    def extract_user_features(self, user_id: int, user_data: Dict) -> np.ndarray:
        """Extract features for a specific user"""
        features = []
        
        # Basic user features
        features.extend([
            user_data.get('age', 25),
            user_data.get('total_orders', 0),
            user_data.get('total_spent', 0.0),
            user_data.get('avg_order_value', 0.0),
            user_data.get('days_since_registration', 0),
            user_data.get('preferred_categories_count', 0),
            user_data.get('wishlist_items', 0),
            user_data.get('cart_abandonment_rate', 0.0)
        ])
        
        # Purchase behavior features
        features.extend([
            user_data.get('purchase_frequency', 0.0),
            user_data.get('price_sensitivity', 0.5),
            user_data.get('category_diversity', 0.0),
            user_data.get('seasonal_preference', 0.0),
            user_data.get('brand_loyalty', 0.0)
        ])
        
        # Recent activity features
        features.extend([
            user_data.get('recent_views', 0),
            user_data.get('recent_searches', 0),
            user_data.get('session_duration_avg', 0.0),
            user_data.get('page_views_avg', 0.0),
            user_data.get('device_type', 0)  # 0=mobile, 1=desktop, 2=tablet
        ])
        
        return np.array(features, dtype=np.float32)
    
    def extract_product_features(self, product_data: Dict) -> np.ndarray:
        """Extract features for a specific product"""
        features = []
        
        # Basic product features
        features.extend([
            product_data.get('price', 0.0),
            product_data.get('category_id', 0),
            product_data.get('rating', 0.0),
            product_data.get('review_count', 0),
            product_data.get('stock_level', 0),
            product_data.get('discount_percentage', 0.0)
        ])
        
        # Content features
        title = product_data.get('title', '')
        description = product_data.get('description', '')
        
        features.extend([
            len(title),
            len(description),
            title.count(' ') + 1,  # Word count
            self._extract_luxury_keywords(title + ' ' + description),
            self._extract_sentiment_keywords(title + ' ' + description)
        ])
        
        # Availability and popularity
        features.extend([
            product_data.get('availability_score', 0.5),
            product_data.get('popularity_score', 0.0),
            product_data.get('trending_score', 0.0),
            product_data.get('seasonal_relevance', 0.0)
        ])
        
        return np.array(features, dtype=np.float32)
    
    def _extract_luxury_keywords(self, text: str) -> float:
        """Extract luxury keyword score"""
        luxury_keywords = [
            'luxury', 'premium', 'exclusive', 'designer', 'handmade',
            'custom', 'artisan', 'deluxe', 'elegant', 'sophisticated',
            'premium', 'high-end', 'boutique', 'gourmet', 'champagne'
        ]
        
        text_lower = text.lower()
        score = sum(1 for keyword in luxury_keywords if keyword in text_lower)
        return min(score / len(luxury_keywords), 1.0)
    
    def _extract_sentiment_keywords(self, text: str) -> float:
        """Extract sentiment keyword score"""
        positive_keywords = [
            'beautiful', 'amazing', 'wonderful', 'perfect', 'lovely',
            'stunning', 'gorgeous', 'fantastic', 'excellent', 'brilliant'
        ]
        
        text_lower = text.lower()
        score = sum(1 for keyword in positive_keywords if keyword in text_lower)
        return min(score / len(positive_keywords), 1.0)
    
    def prepare_training_data(self, user_data: pd.DataFrame, product_data: pd.DataFrame, 
                            interaction_data: pd.DataFrame) -> Tuple[np.ndarray, np.ndarray]:
        """Prepare training data for SVM"""
        X = []
        y = []
        
        # Process each user-product interaction
        for _, interaction in interaction_data.iterrows():
            user_id = interaction['user_id']
            product_id = interaction['product_id']
            
            # Get user and product data
            user_row = user_data[user_data['user_id'] == user_id]
            product_row = product_data[product_data['product_id'] == product_id]
            
            if user_row.empty or product_row.empty:
                continue
            
            # Extract features
            user_features = self.extract_user_features(user_id, user_row.iloc[0].to_dict())
            product_features = self.extract_product_features(product_row.iloc[0].to_dict())
            
            # Combine features
            combined_features = np.concatenate([user_features, product_features])
            X.append(combined_features)
            
            # Target: 1 if user liked the product (purchased, wishlisted, or high rating)
            target = 1 if interaction.get('liked', False) else 0
            y.append(target)
        
        return np.array(X), np.array(y)
    
    def train_model(self, X: np.ndarray, y: np.ndarray, retrain: bool = False):
        """Train the SVM model"""
        if len(X) < self.min_samples_for_retrain:
            logger.warning(f"Not enough samples for training: {len(X)}")
            return False
        
        try:
            # Split data
            X_train, X_test, y_train, y_test = train_test_split(
                X, y, test_size=0.2, random_state=42, stratify=y
            )
            
            # Scale features
            X_train_scaled = self.scaler.fit_transform(X_train)
            X_test_scaled = self.scaler.transform(X_test)
            
            # Train model
            self.svm_model.fit(X_train_scaled, y_train)
            
            # Evaluate
            y_pred = self.svm_model.predict(X_test_scaled)
            accuracy = accuracy_score(y_test, y_pred)
            
            logger.info(f"SVM Model trained - Accuracy: {accuracy:.3f}")
            
            # Update model version
            self.model_version += 1
            self.last_retrain_time = datetime.now()
            
            # Save model
            self.save_model()
            
            return True
            
        except Exception as e:
            logger.error(f"Training failed: {e}")
            return False
    
    def predict_preference(self, user_id: int, product_id: int, 
                          user_data: Dict, product_data: Dict) -> Dict:
        """Predict user preference for a product"""
        try:
            # Extract features
            user_features = self.extract_user_features(user_id, user_data)
            product_features = self.extract_product_features(product_data)
            combined_features = np.concatenate([user_features, product_features])
            
            # Scale features
            features_scaled = self.scaler.transform([combined_features])
            
            # Make prediction
            prediction = self.svm_model.predict(features_scaled)[0]
            probability = self.svm_model.predict_proba(features_scaled)[0]
            
            # Get confidence
            confidence = max(probability)
            
            return {
                'success': True,
                'prediction': int(prediction),
                'confidence': float(confidence),
                'probability_like': float(probability[1]),
                'probability_dislike': float(probability[0]),
                'model_version': self.model_version
            }
            
        except Exception as e:
            logger.error(f"Prediction failed: {e}")
            return {'success': False, 'error': str(e)}
    
    def get_recommendations(self, user_id: int, user_data: Dict, 
                          available_products: List[Dict], limit: int = 10) -> List[Dict]:
        """Get personalized recommendations for a user"""
        recommendations = []
        
        for product in available_products:
            prediction = self.predict_preference(
                user_id, product['id'], user_data, product
            )
            
            if prediction['success'] and prediction['probability_like'] > 0.3:
                recommendations.append({
                    'product_id': product['id'],
                    'title': product.get('title', ''),
                    'price': product.get('price', 0),
                    'image_url': product.get('image_url', ''),
                    'category_name': product.get('category_name', ''),
                    'confidence': prediction['confidence'],
                    'probability_like': prediction['probability_like'],
                    'model_version': prediction['model_version']
                })
        
        # Sort by probability and return top recommendations
        recommendations.sort(key=lambda x: x['probability_like'], reverse=True)
        return recommendations[:limit]
    
    def update_user_behavior(self, user_id: int, product_id: int, 
                           behavior_type: str, additional_data: Dict = None):
        """Update user behavior for dynamic learning"""
        interaction = {
            'user_id': user_id,
            'product_id': product_id,
            'behavior_type': behavior_type,
            'timestamp': datetime.now(),
            'additional_data': additional_data or {}
        }
        
        # Store interaction
        self.user_interactions[user_id].append(interaction)
        
        # Update user preferences
        if behavior_type in ['purchase', 'add_to_cart', 'add_to_wishlist']:
            if 'preferred_products' not in self.user_preferences[user_id]:
                self.user_preferences[user_id]['preferred_products'] = []
            self.user_preferences[user_id]['preferred_products'].append(product_id)
        
        # Add to recent purchases for retraining
        if behavior_type == 'purchase':
            self.recent_purchases.append(interaction)
        
        # Check if retraining is needed
        if self._should_retrain():
            self._schedule_retraining()
    
    def _should_retrain(self) -> bool:
        """Check if model should be retrained"""
        # Retrain if enough new data
        if len(self.recent_purchases) >= self.min_samples_for_retrain:
            return True
        
        # Retrain if model is old
        if self.last_retrain_time:
            days_since_retrain = (datetime.now() - self.last_retrain_time).days
            if days_since_retrain >= 7:  # Retrain weekly
                return True
        
        return False
    
    def _schedule_retraining(self):
        """Schedule background retraining"""
        if self.background_retrain:
            thread = threading.Thread(target=self._background_retrain)
            thread.daemon = True
            thread.start()
    
    def _background_retrain(self):
        """Background retraining process"""
        with self.retrain_lock:
            try:
                logger.info("Starting background retraining...")
                
                # This would typically load fresh data from database
                # For now, we'll use the stored interactions
                if len(self.recent_purchases) >= self.min_samples_for_retrain:
                    # Prepare training data from recent purchases
                    X, y = self._prepare_retraining_data()
                    
                    if X is not None and len(X) > 0:
                        self.train_model(X, y, retrain=True)
                        logger.info("Background retraining completed")
                
            except Exception as e:
                logger.error(f"Background retraining failed: {e}")
    
    def _prepare_retraining_data(self) -> Tuple[np.ndarray, np.ndarray]:
        """Prepare data for retraining from recent interactions"""
        # This is a simplified version - in practice, you'd load from database
        X = []
        y = []
        
        for interaction in self.recent_purchases:
            # Create sample features (in practice, load from database)
            user_features = np.random.rand(20)  # 20 user features
            product_features = np.random.rand(15)  # 15 product features
            combined_features = np.concatenate([user_features, product_features])
            
            X.append(combined_features)
            y.append(1)  # Positive interaction
        
        return np.array(X), np.array(y)
    
    def get_new_user_recommendations(self, user_data: Dict, 
                                   available_products: List[Dict], limit: int = 10) -> List[Dict]:
        """Get recommendations for new users based on popular items and demographics"""
        recommendations = []
        
        # For new users, use popularity-based recommendations with demographic filtering
        age = user_data.get('age', 25)
        gender = user_data.get('gender', 'unknown')
        
        # Filter products based on demographics
        filtered_products = []
        for product in available_products:
            # Simple demographic filtering
            if age < 25 and 'trendy' in product.get('title', '').lower():
                filtered_products.append(product)
            elif age > 40 and 'classic' in product.get('title', '').lower():
                filtered_products.append(product)
            else:
                filtered_products.append(product)
        
        # Sort by popularity/rating
        filtered_products.sort(key=lambda x: x.get('rating', 0), reverse=True)
        
        # Return top products with confidence scores
        for i, product in enumerate(filtered_products[:limit]):
            # New users get medium confidence
            confidence = 0.6 - (i * 0.05)  # Decreasing confidence
            
            recommendations.append({
                'product_id': product['id'],
                'title': product.get('title', ''),
                'price': product.get('price', 0),
                'image_url': product.get('image_url', ''),
                'category_name': product.get('category_name', ''),
                'confidence': max(confidence, 0.3),
                'probability_like': confidence,
                'model_version': self.model_version,
                'recommendation_type': 'new_user_popular'
            })
        
        return recommendations
    
    def get_model_info(self) -> Dict:
        """Get information about the current model"""
        return {
            'model_version': self.model_version,
            'last_retrain_time': self.last_retrain_time.isoformat() if self.last_retrain_time else None,
            'total_interactions': sum(len(interactions) for interactions in self.user_interactions.values()),
            'recent_purchases_count': len(self.recent_purchases),
            'is_trained': self.svm_model is not None,
            'feature_count': len(self.feature_columns) if self.feature_columns else 0
        }






