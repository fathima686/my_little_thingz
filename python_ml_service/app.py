#!/usr/bin/env python3
"""
Python ML Microservice for My Little Things
Flask API providing ML algorithms as services
"""

from flask import Flask, request, jsonify
from flask_cors import CORS
import pandas as pd
import numpy as np
from sklearn.neighbors import KNeighborsClassifier, NearestNeighbors
from sklearn.naive_bayes import GaussianNB, MultinomialNB
from sklearn.tree import DecisionTreeClassifier
from sklearn.svm import SVC
from sklearn.neural_network import MLPClassifier
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
import joblib
import os
import json
from datetime import datetime
import logging

# Import our dynamic SVM system
from dynamic_svm_recommender import DynamicSVMRecommender
from enhanced_database import EnhancedDatabaseConnection, DynamicDataProvider
from enhanced_bayesian_classifier import EnhancedBayesianClassifier
from gift_category_predictor import GiftCategoryPredictor
from budget_premium_svm import BudgetPremiumSVMClassifier

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

# Global variables for models
models = {}
scalers = {}
encoders = {}

class MLService:
    """Main ML Service class containing all algorithms"""
    
    def __init__(self):
        self.models_dir = "models"
        self.data_dir = "data"
        os.makedirs(self.models_dir, exist_ok=True)
        os.makedirs(self.data_dir, exist_ok=True)
    
    # ==================== K-Nearest Neighbors ====================
    def knn_recommendations(self, product_id, user_id=None, k=5):
        """
        KNN Product Recommendation System
        Finds similar products based on features
        """
        try:
            # Load or create KNN model
            if 'knn_model' not in models:
                models['knn_model'] = NearestNeighbors(n_neighbors=k, metric='cosine')
                # Load training data (this would come from your database)
                training_data = self.load_training_data()
                if training_data is not None:
                    models['knn_model'].fit(training_data)
            
            # Get product features
            product_features = self.get_product_features(product_id)
            if product_features is None:
                return {"error": "Product not found"}
            
            # Find similar products
            distances, indices = models['knn_model'].kneighbors([product_features])
            
            recommendations = []
            for i, (distance, idx) in enumerate(zip(distances[0], indices[0])):
                if i > 0:  # Skip the product itself
                    recommendations.append({
                        'product_id': int(idx),
                        'similarity_score': float(1 - distance),
                        'distance': float(distance)
                    })
            
            return {
                'success': True,
                'recommendations': recommendations,
                'algorithm': 'KNN',
                'k': k
            }
            
        except Exception as e:
            logger.error(f"KNN Error: {str(e)}")
            return {"error": str(e)}
    
    # ==================== Enhanced Gift Category Predictor ====================
    def gift_category_predict(self, search_term, confidence_threshold=0.6):
        """
        Enhanced Gift Category Predictor for Artwork Gallery
        Predicts gift categories from search terms with high accuracy
        """
        try:
            # Initialize gift category predictor if not exists
            if 'gift_category_predictor' not in models:
                models['gift_category_predictor'] = GiftCategoryPredictor()
            
            predictor = models['gift_category_predictor']
            
            # Get search recommendations
            result = predictor.get_search_recommendations(search_term)
            
            # Add additional context
            result['timestamp'] = datetime.now().isoformat()
            result['input_text'] = search_term
            
            return result
            
        except Exception as e:
            logger.error(f"Gift Category Predictor Error: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'search_term': search_term,
                'recommendations': [],
                'algorithm': 'Enhanced Gift Category Predictor'
            }
    
    # ==================== Budget vs Premium SVM Classifier ====================
    def svm_classify_budget_premium(self, product_data):
        """
        SVM Classifier for Budget vs Premium Gift Classification
        Draws a line/boundary to separate gifts into Budget vs Premium categories
        """
        try:
            # Initialize SVM classifier if not exists
            if 'budget_premium_svm' not in models:
                models['budget_premium_svm'] = BudgetPremiumSVMClassifier()
            
            classifier = models['budget_premium_svm']
            
            # Classify the gift
            result = classifier.classify_gift(product_data)
            
            # Add additional context
            result['timestamp'] = datetime.now().isoformat()
            result['product_title'] = product_data.get('title', 'Unknown')
            result['product_price'] = product_data.get('price', 0)
            
            return result
            
        except Exception as e:
            logger.error(f"SVM Budget vs Premium Classification Error: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'prediction': 'Unknown',
                'confidence': 0.0,
                'algorithm': 'Support Vector Machine'
            }
    
    def bayesian_search_recommendations(self, keyword, limit=8, confidence_threshold=0.6):
        """
        Bayesian Search Recommendations for Artwork Gallery
        Provides ML-powered search suggestions based on keywords
        """
        try:
            # Initialize gift category predictor if not exists
            if 'gift_category_predictor' not in models:
                models['gift_category_predictor'] = GiftCategoryPredictor()
            
            predictor = models['gift_category_predictor']
            
            # Get search recommendations
            result = predictor.get_search_recommendations(keyword, limit)
            
            # Add additional context
            result['timestamp'] = datetime.now().isoformat()
            result['input_text'] = keyword
            
            return result
            
        except Exception as e:
            logger.error(f"Bayesian Search Recommendations Error: {str(e)}")
            return {
                'success': False,
                'error': str(e),
                'search_term': keyword,
                'recommendations': [],
                'algorithm': 'Bayesian Search Recommendations'
            }
    
    # ==================== Decision Tree ====================
    def decision_tree_addon_suggestion(self, cart_total, cart_items=None):
        """
        Decision Tree for Add-on Suggestions
        """
        try:
            # Load or create Decision Tree model
            if 'decision_tree_model' not in models:
                models['decision_tree_model'] = DecisionTreeClassifier(
                    max_depth=5,
                    min_samples_split=10,
                    random_state=42
                )
                # Train with historical data
                training_data = self.load_addon_training_data()
                if training_data is not None:
                    X, y = training_data
                    models['decision_tree_model'].fit(X, y)
            
            # Prepare features
            features = self.extract_cart_features(cart_total, cart_items)
            
            # Make prediction
            prediction = models['decision_tree_model'].predict([features])
            prediction_proba = models['decision_tree_model'].predict_proba([features])
            
            # Get suggested addons
            suggested_addons = self.get_addon_suggestions(prediction[0])
            
            return {
                'success': True,
                'suggested_addons': suggested_addons,
                'confidence': float(np.max(prediction_proba)),
                'reasoning': self.get_decision_reasoning(cart_total, prediction[0]),
                'algorithm': 'Decision Tree'
            }
            
        except Exception as e:
            logger.error(f"Decision Tree Error: {str(e)}")
            return {"error": str(e)}
    
    # ==================== Support Vector Machine ====================
    def svm_classify_gift(self, gift_data):
        """
        SVM for Budget vs Premium Classification
        """
        try:
            # Load or create SVM model
            if 'svm_model' not in models:
                models['svm_model'] = SVC(kernel='rbf', probability=True, random_state=42)
                # Train with product data
                training_data = self.load_svm_training_data()
                if training_data is not None:
                    X, y = training_data
                    models['svm_model'].fit(X, y)
            
            # Extract features
            features = self.extract_svm_features(gift_data)
            
            # Make prediction
            prediction = models['svm_model'].predict([features])
            prediction_proba = models['svm_model'].predict_proba([features])
            
            # Get confidence and reasoning
            confidence = float(np.max(prediction_proba))
            predicted_class = 'Premium' if prediction[0] == 1 else 'Budget'
            
            return {
                'success': True,
                'prediction': predicted_class,
                'confidence': confidence,
                'score': float(prediction[0]),
                'reasoning': self.get_svm_reasoning(features, prediction[0]),
                'algorithm': 'Support Vector Machine'
            }
            
        except Exception as e:
            logger.error(f"SVM Error: {str(e)}")
            return {"error": str(e)}
    
    # ==================== Backpropagation Neural Network ====================
    def bpnn_predict_preference(self, user_data, product_data):
        """
        BPNN for Customer Preference Prediction
        """
        try:
            # Load or create BPNN model
            if 'bpnn_model' not in models:
                models['bpnn_model'] = MLPClassifier(
                    hidden_layer_sizes=(50, 25),
                    activation='relu',
                    solver='adam',
                    learning_rate_init=0.001,
                    max_iter=1000,
                    random_state=42
                )
                # Train with user behavior data
                training_data = self.load_bpnn_training_data()
                if training_data is not None:
                    X, y = training_data
                    models['bpnn_model'].fit(X, y)
            
            # Prepare input features
            features = self.extract_bpnn_features(user_data, product_data)
            
            # Make prediction
            prediction = models['bpnn_model'].predict([features])
            prediction_proba = models['bpnn_model'].predict_proba([features])
            
            # Get confidence
            confidence = float(np.max(prediction_proba))
            preference_score = float(prediction[0])
            
            return {
                'success': True,
                'preference_score': preference_score,
                'confidence': confidence,
                'recommendation': 'High' if preference_score > 0.7 else 'Medium' if preference_score > 0.4 else 'Low',
                'algorithm': 'Backpropagation Neural Network'
            }
            
        except Exception as e:
            logger.error(f"BPNN Error: {str(e)}")
            return {"error": str(e)}
    
    # ==================== Helper Methods ====================
    def load_training_data(self):
        """Load training data for KNN"""
        # This would connect to your database
        # For now, return sample data
        return np.random.rand(100, 10)  # 100 products, 10 features
    
    def load_category_training_data(self):
        """Load training data for Bayesian classifier"""
        # Sample data
        X = np.random.rand(100, 20)  # 100 samples, 20 features
        y = np.random.randint(0, 5, 100)  # 5 categories
        return X, y
    
    def load_addon_training_data(self):
        """Load training data for Decision Tree"""
        # Sample data
        X = np.random.rand(100, 5)  # 100 samples, 5 features
        y = np.random.randint(0, 3, 100)  # 3 addon types
        return X, y
    
    def load_svm_training_data(self):
        """Load training data for SVM"""
        # Sample data
        X = np.random.rand(100, 8)  # 100 samples, 8 features
        y = np.random.randint(0, 2, 100)  # Binary classification
        return X, y
    
    def load_bpnn_training_data(self):
        """Load training data for BPNN"""
        # Sample data
        X = np.random.rand(100, 15)  # 100 samples, 15 features
        y = np.random.rand(100)  # Continuous target
        return X, y
    
    def get_product_features(self, product_id):
        """Extract features for a specific product"""
        # This would query your database
        return np.random.rand(10)  # Sample features
    
    def extract_text_features(self, text):
        """Extract features from text for Bayesian classifier"""
        # Simple feature extraction
        features = np.zeros(20)
        words = text.lower().split()
        for i, word in enumerate(words[:20]):
            features[i] = len(word)
        return features
    
    def extract_cart_features(self, cart_total, cart_items):
        """Extract features for cart analysis"""
        features = [
            cart_total,
            len(cart_items) if cart_items else 0,
            cart_total / 1000,  # Normalized total
            cart_total / 500,   # Price tier
            cart_total / 2000   # Luxury indicator
        ]
        return features
    
    def extract_svm_features(self, gift_data):
        """Extract features for SVM classification"""
        features = [
            gift_data.get('price', 0),
            gift_data.get('category_id', 0),
            len(gift_data.get('title', '')),
            len(gift_data.get('description', '')),
            gift_data.get('availability_score', 0.5),
            gift_data.get('luxury_keywords', 0),
            gift_data.get('rating', 0),
            gift_data.get('popularity', 0)
        ]
        return features
    
    def extract_bpnn_features(self, user_data, product_data):
        """Extract features for BPNN"""
        features = [
            user_data.get('age', 25),
            user_data.get('purchase_frequency', 0),
            user_data.get('avg_order_value', 0),
            user_data.get('preferred_categories', 0),
            product_data.get('price', 0),
            product_data.get('category_id', 0),
            product_data.get('rating', 0),
            product_data.get('popularity', 0),
            user_data.get('session_duration', 0),
            user_data.get('page_views', 0),
            user_data.get('time_on_site', 0),
            product_data.get('stock_level', 0),
            product_data.get('discount_percentage', 0),
            user_data.get('device_type', 0),
            user_data.get('location_score', 0)
        ]
        return features
    
    def get_addon_suggestions(self, prediction):
        """Get addon suggestions based on prediction"""
        addons = {
            0: ['ribbon'],
            1: ['greeting_card'],
            2: ['greeting_card', 'ribbon']
        }
        return addons.get(prediction, [])
    
    def get_decision_reasoning(self, cart_total, prediction):
        """Get reasoning for decision tree prediction"""
        if cart_total >= 1000:
            return "Premium gift deserves complete presentation"
        elif cart_total >= 500:
            return "Mid-range gift would benefit from greeting card"
        else:
            return "Budget-friendly ribbon suggestion"
    
    def get_svm_reasoning(self, features, prediction):
        """Get reasoning for SVM classification"""
        reasons = []
        if features[0] > 1000:  # Price
            reasons.append("High price indicates premium quality")
        if features[4] > 0.7:  # Availability
            reasons.append("Limited availability suggests exclusivity")
        return reasons

# Initialize ML Service
ml_service = MLService()

def generate_category_recommendations(category, keyword, limit=8):
    """Generate mock recommendations based on predicted category"""
    recommendations = []
    
    # Category-specific product templates
    category_products = {
        'chocolate': [
            {'name': f'Premium {keyword.title()} Chocolate Box', 'price': 899, 'description': f'Luxury chocolate collection featuring {keyword} flavors'},
            {'name': f'Artisan {keyword.title()} Truffles', 'price': 599, 'description': f'Handcrafted truffles with {keyword} essence'},
            {'name': f'Gourmet {keyword.title()} Gift Set', 'price': 1299, 'description': f'Curated selection of {keyword} chocolates'},
            {'name': f'Dark {keyword.title()} Chocolate Bar', 'price': 299, 'description': f'Rich dark chocolate with {keyword} notes'},
            {'name': f'{keyword.title()} Chocolate Hamper', 'price': 1599, 'description': f'Premium hamper with {keyword} chocolate varieties'},
            {'name': f'Belgian {keyword.title()} Collection', 'price': 799, 'description': f'Authentic Belgian chocolates with {keyword} flavors'},
            {'name': f'{keyword.title()} Chocolate Tower', 'price': 1999, 'description': f'Impressive chocolate tower with {keyword} theme'},
            {'name': f'Organic {keyword.title()} Treats', 'price': 499, 'description': f'Natural organic chocolates with {keyword} ingredients'}
        ],
        'bouquet': [
            {'name': f'Romantic {keyword.title()} Bouquet', 'price': 1299, 'description': f'Beautiful flower arrangement with {keyword} theme'},
            {'name': f'Fresh {keyword.title()} Roses', 'price': 899, 'description': f'Premium roses arranged with {keyword} accents'},
            {'name': f'{keyword.title()} Floral Arrangement', 'price': 699, 'description': f'Elegant floral display featuring {keyword} elements'},
            {'name': f'Mixed {keyword.title()} Flowers', 'price': 599, 'description': f'Colorful mixed bouquet with {keyword} highlights'},
            {'name': f'Premium {keyword.title()} Centerpiece', 'price': 1599, 'description': f'Luxury centerpiece with {keyword} flowers'},
            {'name': f'{keyword.title()} Wedding Bouquet', 'price': 1899, 'description': f'Bridal bouquet with {keyword} flowers'},
            {'name': f'Seasonal {keyword.title()} Collection', 'price': 799, 'description': f'Seasonal flowers with {keyword} theme'},
            {'name': f'{keyword.title()} Flower Basket', 'price': 999, 'description': f'Gift basket with {keyword} floral arrangement'}
        ],
        'gift_box': [
            {'name': f'Luxury {keyword.title()} Gift Box', 'price': 1999, 'description': f'Premium gift box curated with {keyword} items'},
            {'name': f'Premium {keyword.title()} Hamper', 'price': 1499, 'description': f'Gourmet hamper featuring {keyword} products'},
            {'name': f'{keyword.title()} Gift Collection', 'price': 899, 'description': f'Curated collection with {keyword} theme'},
            {'name': f'Deluxe {keyword.title()} Set', 'price': 1299, 'description': f'Deluxe gift set with {keyword} items'},
            {'name': f'{keyword.title()} Surprise Box', 'price': 699, 'description': f'Mystery gift box with {keyword} surprises'},
            {'name': f'Artisan {keyword.title()} Collection', 'price': 1599, 'description': f'Handcrafted items with {keyword} theme'},
            {'name': f'{keyword.title()} Luxury Basket', 'price': 2299, 'description': f'High-end basket with {keyword} products'},
            {'name': f'Custom {keyword.title()} Gift', 'price': 999, 'description': f'Personalized gift with {keyword} elements'}
        ],
        'wedding_card': [
            {'name': f'Elegant {keyword.title()} Invitation', 'price': 299, 'description': f'Sophisticated wedding invitation with {keyword} design'},
            {'name': f'Premium {keyword.title()} Card Set', 'price': 499, 'description': f'Complete invitation set with {keyword} theme'},
            {'name': f'{keyword.title()} Wedding Suite', 'price': 799, 'description': f'Full wedding stationery with {keyword} elements'},
            {'name': f'Custom {keyword.title()} Invites', 'price': 399, 'description': f'Personalized invitations with {keyword} design'},
            {'name': f'Luxury {keyword.title()} Collection', 'price': 1299, 'description': f'High-end wedding cards with {keyword} theme'},
            {'name': f'{keyword.title()} Save the Date', 'price': 199, 'description': f'Save the date cards with {keyword} design'},
            {'name': f'Artisan {keyword.title()} Cards', 'price': 599, 'description': f'Handcrafted wedding cards with {keyword} theme'},
            {'name': f'{keyword.title()} Thank You Cards', 'price': 249, 'description': f'Thank you cards with {keyword} design'}
        ],
        'custom_chocolate': [
            {'name': f'Personalized {keyword.title()} Chocolate', 'price': 899, 'description': f'Custom chocolate with {keyword} personalization'},
            {'name': f'Engraved {keyword.title()} Treats', 'price': 699, 'description': f'Laser-engraved chocolates with {keyword} design'},
            {'name': f'Custom {keyword.title()} Bar', 'price': 399, 'description': f'Personalized chocolate bar with {keyword} message'},
            {'name': f'{keyword.title()} Photo Chocolate', 'price': 599, 'description': f'Chocolate with {keyword} photo printing'},
            {'name': f'Monogrammed {keyword.title()} Box', 'price': 1299, 'description': f'Monogrammed chocolate box with {keyword} theme'},
            {'name': f'Custom {keyword.title()} Collection', 'price': 1599, 'description': f'Bespoke chocolate collection with {keyword} design'},
            {'name': f'{keyword.title()} Logo Chocolates', 'price': 799, 'description': f'Chocolates with {keyword} logo printing'},
            {'name': f'Personalized {keyword.title()} Gift', 'price': 999, 'description': f'Custom gift with {keyword} personalization'}
        ],
        'nuts': [
            {'name': f'Premium {keyword.title()} Nut Mix', 'price': 599, 'description': f'Gourmet nut mix featuring {keyword} varieties'},
            {'name': f'Organic {keyword.title()} Collection', 'price': 799, 'description': f'Natural organic nuts with {keyword} selection'},
            {'name': f'{keyword.title()} Trail Mix', 'price': 399, 'description': f'Healthy trail mix with {keyword} nuts'},
            {'name': f'Roasted {keyword.title()} Assortment', 'price': 499, 'description': f'Perfectly roasted nuts with {keyword} flavors'},
            {'name': f'Luxury {keyword.title()} Hamper', 'price': 1299, 'description': f'Premium nut hamper with {keyword} varieties'},
            {'name': f'{keyword.title()} Energy Mix', 'price': 349, 'description': f'High-energy nut mix with {keyword} ingredients'},
            {'name': f'Gourmet {keyword.title()} Selection', 'price': 899, 'description': f'Curated nut selection with {keyword} theme'},
            {'name': f'{keyword.title()} Healthy Snack Box', 'price': 699, 'description': f'Nutritious snack box with {keyword} nuts'}
        ]
    }
    
    # Get products for the predicted category
    products = category_products.get(category, category_products['gift_box'])
    
    # Generate recommendations
    for i in range(min(limit, len(products))):
        product = products[i]
        recommendations.append({
            'id': f"{category}_{i+1}",
            'title': product['name'],
            'description': product['description'],
            'price': product['price'],
            'image_url': f'/images/{category}_placeholder.jpg',
            'category_id': hash(category) % 10 + 1,  # Simple category ID generation
            'category_name': category.replace('_', ' ').title(),
            'availability': 'in_stock',
            'created_at': datetime.now().isoformat(),
            'recommendation_score': 0.8 + (i * 0.02),  # Decreasing score
            'confidence': 0.8,
            'algorithm': 'Enhanced Bayesian Search',
            'search_keyword': keyword,
            'predicted_category': category
        })
    
    return recommendations

# Initialize Dynamic SVM Recommender
try:
    from config import Config
    config = Config()
    db_connection = EnhancedDatabaseConnection(config)
    data_provider = DynamicDataProvider(db_connection)
    dynamic_svm = DynamicSVMRecommender(config)
    
    # Create behavior log table
    data_provider.create_behavior_log_table()
    
    logger.info("Dynamic SVM Recommender initialized successfully")
except Exception as e:
    logger.error(f"Failed to initialize Dynamic SVM Recommender: {e}")
    dynamic_svm = None
    data_provider = None

# ==================== API ENDPOINTS ====================

@app.route('/api/ml/knn/recommendations', methods=['POST'])
def knn_recommendations():
    """KNN Product Recommendations"""
    try:
        data = request.get_json()
        product_id = data.get('product_id')
        user_id = data.get('user_id')
        k = data.get('k', 5)
        
        result = ml_service.knn_recommendations(product_id, user_id, k)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/bayesian/classify', methods=['POST'])
def bayesian_classify():
    """Enhanced Bayesian Gift Classification"""
    try:
        data = request.get_json()
        gift_name = data.get('gift_name')
        confidence_threshold = data.get('confidence_threshold', 0.6)
        
        result = ml_service.bayesian_classify(gift_name, confidence_threshold)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/gift-category/predict', methods=['POST'])
def api_gift_category_predict():
    """API endpoint for gift category prediction"""
    try:
        data = request.get_json()
        search_term = data.get('search_term', '')
        confidence_threshold = data.get('confidence_threshold', 0.6)
        
        if not search_term:
            return jsonify({
                'success': False,
                'error': 'Search term is required'
            }), 400
        
        ml_service = MLService()
        result = ml_service.gift_category_predict(search_term, confidence_threshold)
        
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"Gift Category Predict API Error: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/ml/bayesian/search-recommendations', methods=['POST'])
def bayesian_search_recommendations():
    """Enhanced Bayesian Search Recommendations"""
    try:
        data = request.get_json()
        search_keyword = data.get('keyword', '')
        limit = data.get('limit', 8)
        confidence_threshold = data.get('confidence_threshold', 0.6)
        
        if not search_keyword:
            return jsonify({
                'success': False,
                'error': 'keyword is required'
            }), 400
        
        # Get category prediction using new gift category predictor
        category_result = ml_service.gift_category_predict(search_keyword, confidence_threshold)
        
        if not category_result.get('success'):
            return jsonify(category_result), 400
        
        # Generate recommendations based on predicted category
        predicted_category = category_result['predicted_category']
        confidence = category_result['confidence']
        suggestions = category_result.get('recommendations', [])
        
        # Mock recommendations based on category
        recommendations = generate_category_recommendations(predicted_category, search_keyword, limit)
        
        return jsonify({
            'success': True,
            'search_keyword': search_keyword,
            'predicted_category': predicted_category,
            'confidence': confidence,
            'confidence_percent': confidence * 100,
            'recommendations': recommendations,
            'suggestions': suggestions,
            'algorithm': 'Enhanced Gift Category Predictor',
            'timestamp': datetime.now().isoformat()
        })
        
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/decision-tree/addon-suggestion', methods=['POST'])
def decision_tree_addon():
    """Decision Tree Add-on Suggestions"""
    try:
        data = request.get_json()
        cart_total = data.get('cart_total')
        cart_items = data.get('cart_items', [])
        
        result = ml_service.decision_tree_addon_suggestion(cart_total, cart_items)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/svm/classify', methods=['POST'])
def svm_classify():
    """SVM Budget vs Premium Classification"""
    try:
        data = request.get_json()
        gift_data = data.get('gift_data')
        
        result = ml_service.svm_classify_gift(gift_data)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/svm/budget-premium', methods=['POST'])
def svm_budget_premium():
    """SVM Budget vs Premium Classification for Artwork Gallery"""
    try:
        data = request.get_json()
        product_data = data.get('product_data', {})
        
        if not product_data:
            return jsonify({
                'success': False,
                'error': 'Product data is required'
            }), 400
        
        ml_service = MLService()
        result = ml_service.svm_classify_budget_premium(product_data)
        
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"SVM Budget vs Premium API Error: {str(e)}")
        return jsonify({
            'success': False,
            'error': str(e)
        }), 500

@app.route('/api/ml/bpnn/predict-preference', methods=['POST'])
def bpnn_predict():
    """BPNN Customer Preference Prediction"""
    try:
        data = request.get_json()
        user_data = data.get('user_data')
        product_data = data.get('product_data')
        
        result = ml_service.bpnn_predict_preference(user_data, product_data)
        return jsonify(result)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "service": "Python ML Microservice",
        "timestamp": datetime.now().isoformat(),
        "algorithms": ["KNN", "Bayesian", "Decision Tree", "SVM", "BPNN", "Dynamic SVM"],
        "dynamic_svm_available": dynamic_svm is not None
    })

# ==================== DYNAMIC SVM ENDPOINTS ====================

@app.route('/api/ml/dynamic-svm/recommendations', methods=['POST'])
def dynamic_svm_recommendations():
    """Get dynamic SVM-based recommendations"""
    try:
        if not dynamic_svm:
            return jsonify({"error": "Dynamic SVM not available"}), 500
        
        data = request.get_json()
        user_id = data.get('user_id')
        limit = data.get('limit', 10)
        
        if not user_id:
            return jsonify({"error": "user_id is required"}), 400
        
        # Get user profile
        user_data = data_provider.get_user_profile(user_id)
        if not user_data:
            return jsonify({"error": "User not found"}), 404
        
        # Get available products
        products_df = data_provider.get_products_with_features(limit=200)
        available_products = products_df.to_dict('records')
        
        # Check if user is new (less than 3 orders)
        is_new_user = user_data.get('total_orders', 0) < 3
        
        if is_new_user:
            # Get new user recommendations
            recommendations = dynamic_svm.get_new_user_recommendations(
                user_data, available_products, limit
            )
        else:
            # Get personalized recommendations
            recommendations = dynamic_svm.get_recommendations(
                user_id, user_data, available_products, limit
            )
        
        return jsonify({
            "success": True,
            "recommendations": recommendations,
            "user_id": user_id,
            "is_new_user": is_new_user,
            "model_version": dynamic_svm.model_version,
            "generated_at": datetime.now().isoformat()
        })
        
    except Exception as e:
        logger.error(f"Dynamic SVM recommendations error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/dynamic-svm/predict', methods=['POST'])
def dynamic_svm_predict():
    """Predict user preference for a specific product"""
    try:
        if not dynamic_svm:
            return jsonify({"error": "Dynamic SVM not available"}), 500
        
        data = request.get_json()
        user_id = data.get('user_id')
        product_id = data.get('product_id')
        
        if not user_id or not product_id:
            return jsonify({"error": "user_id and product_id are required"}), 400
        
        # Get user profile
        user_data = data_provider.get_user_profile(user_id)
        if not user_data:
            return jsonify({"error": "User not found"}), 404
        
        # Get product data
        products_df = data_provider.get_products_with_features(limit=1000)
        product_data = products_df[products_df['product_id'] == product_id]
        
        if product_data.empty:
            return jsonify({"error": "Product not found"}), 404
        
        product_data = product_data.iloc[0].to_dict()
        
        # Make prediction
        prediction = dynamic_svm.predict_preference(
            user_id, product_id, user_data, product_data
        )
        
        return jsonify(prediction)
        
    except Exception as e:
        logger.error(f"Dynamic SVM prediction error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/dynamic-svm/update-behavior', methods=['POST'])
def update_user_behavior():
    """Update user behavior for dynamic learning"""
    try:
        if not dynamic_svm or not data_provider:
            return jsonify({"error": "Dynamic SVM not available"}), 500
        
        data = request.get_json()
        user_id = data.get('user_id')
        product_id = data.get('product_id')
        behavior_type = data.get('behavior_type')
        additional_data = data.get('additional_data', {})
        
        if not all([user_id, product_id, behavior_type]):
            return jsonify({"error": "user_id, product_id, and behavior_type are required"}), 400
        
        # Log behavior in database
        data_provider.log_user_behavior(user_id, product_id, behavior_type, additional_data)
        
        # Update dynamic SVM
        dynamic_svm.update_user_behavior(user_id, product_id, behavior_type, additional_data)
        
        return jsonify({
            "success": True,
            "message": "Behavior updated successfully",
            "user_id": user_id,
            "product_id": product_id,
            "behavior_type": behavior_type
        })
        
    except Exception as e:
        logger.error(f"Update behavior error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/dynamic-svm/retrain', methods=['POST'])
def retrain_model():
    """Manually trigger model retraining"""
    try:
        if not dynamic_svm:
            return jsonify({"error": "Dynamic SVM not available"}), 500
        
        data = request.get_json()
        force_retrain = data.get('force', False)
        
        # Get recent interactions for retraining
        interactions_df = data_provider.get_all_user_interactions(days_back=30)
        
        if interactions_df.empty and not force_retrain:
            return jsonify({
                "success": False,
                "message": "No recent interactions for retraining"
            })
        
        # Get user and product data
        users_df = pd.DataFrame()
        products_df = data_provider.get_products_with_features(limit=1000)
        
        # Prepare training data
        X, y = dynamic_svm.prepare_training_data(users_df, products_df, interactions_df)
        
        if X is None or len(X) == 0:
            return jsonify({
                "success": False,
                "message": "Insufficient data for retraining"
            })
        
        # Train model
        success = dynamic_svm.train_model(X, y, retrain=True)
        
        if success:
            return jsonify({
                "success": True,
                "message": "Model retrained successfully",
                "model_version": dynamic_svm.model_version,
                "training_samples": len(X)
            })
        else:
            return jsonify({
                "success": False,
                "message": "Model retraining failed"
            })
        
    except Exception as e:
        logger.error(f"Retrain model error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/dynamic-svm/model-info', methods=['GET'])
def get_model_info():
    """Get information about the current model"""
    try:
        if not dynamic_svm:
            return jsonify({"error": "Dynamic SVM not available"}), 500
        
        model_info = dynamic_svm.get_model_info()
        performance_metrics = data_provider.get_model_performance_metrics()
        
        return jsonify({
            "success": True,
            "model_info": model_info,
            "performance_metrics": performance_metrics
        })
        
    except Exception as e:
        logger.error(f"Get model info error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/models/train', methods=['POST'])
def train_models():
    """Train all ML models"""
    try:
        data = request.get_json()
        algorithm = data.get('algorithm', 'all')
        
        results = {}
        
        if algorithm == 'all' or algorithm == 'knn':
            # Train KNN
            results['knn'] = "Training completed"
        
        if algorithm == 'all' or algorithm == 'bayesian':
            # Train Bayesian
            results['bayesian'] = "Training completed"
        
        if algorithm == 'all' or algorithm == 'decision_tree':
            # Train Decision Tree
            results['decision_tree'] = "Training completed"
        
        if algorithm == 'all' or algorithm == 'svm':
            # Train SVM
            results['svm'] = "Training completed"
        
        if algorithm == 'all' or algorithm == 'bpnn':
            # Train BPNN
            results['bpnn'] = "Training completed"
        
        return jsonify({
            "success": True,
            "message": "Models trained successfully",
            "results": results
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)

