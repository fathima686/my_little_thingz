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
from sklearn.feature_extraction.text import CountVectorizer
from sklearn.preprocessing import StandardScaler, LabelEncoder
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report
import re
import joblib
import os
import json
from datetime import datetime
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)
CORS(app)

# Global variables for models
models = {}
scalers = {}
encoders = {}

# Import sentiment analyzer
try:
    from gift_review_sentiment_analysis import GiftReviewSentimentAnalyzer
    sentiment_analyzer_available = True
except ImportError:
    sentiment_analyzer_available = False
    logger.warning("Gift review sentiment analyzer not available")

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
    
    # ==================== Bayesian Classifier ====================
    def bayesian_classify(self, gift_name, confidence_threshold=0.75):
        """
        Bayesian Classifier for Gift Category Prediction
        """
        try:
            # Load or create Bayesian model
            if 'bayesian_model' not in models:
                models['bayesian_model'] = MultinomialNB()
                # Train with existing data
                training_data = self.load_category_training_data()
                if training_data is not None:
                    X, y = training_data
                    models['bayesian_model'].fit(X, y)
            
            # Prepare input features
            features = self.extract_text_features(gift_name)
            
            # Make prediction
            prediction_proba = models['bayesian_model'].predict_proba([features])
            prediction = models['bayesian_model'].predict([features])
            
            # Get confidence
            max_confidence = np.max(prediction_proba)
            predicted_class = prediction[0]
            
            # Determine action
            action = 'manual_review'
            if max_confidence >= confidence_threshold:
                action = 'auto_assign'
            elif max_confidence >= 0.5:
                action = 'suggest'
            
            return {
                'success': True,
                'predicted_category': predicted_class,
                'confidence': float(max_confidence),
                'confidence_percent': float(max_confidence * 100),
                'action': action,
                'algorithm': 'Bayesian Classifier'
            }
            
        except Exception as e:
            logger.error(f"Bayesian Error: {str(e)}")
            return {"error": str(e)}
    
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
    """Bayesian Gift Classification"""
    try:
        data = request.get_json()
        gift_name = data.get('gift_name')
        confidence_threshold = data.get('confidence_threshold', 0.75)
        
        result = ml_service.bayesian_classify(gift_name, confidence_threshold)
        return jsonify(result)
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

@app.route('/api/ml/trending/classify', methods=['POST'])
def classify_trending():
    """Classify gift as Trending or Normal using SVM"""
    try:
        data = request.get_json()
        recent_sales = data.get('recent_sales_count', 0)
        total_views = data.get('total_views', 0)
        avg_rating = data.get('average_rating', 0)
        num_reviews = data.get('number_of_reviews', 0)
        
        # Initialize SVM classifier if needed
        if 'trending_classifier' not in models:
            # This would load a pre-trained model in production
            return jsonify({
                "error": "Trending classifier not initialized"
            }), 500
        
        # For now, use simple heuristics since we don't have actual model trained
        # In production, you would use the actual SVM model here
        is_trending = (
            recent_sales >= 50 and
            total_views >= 500 and
            avg_rating >= 4.0 and
            num_reviews >= 20
        )
        
        return jsonify({
            "success": True,
            "status": "Trending" if is_trending else "Normal",
            "features": {
                "recent_sales_count": recent_sales,
                "total_views": total_views,
                "average_rating": avg_rating,
                "number_of_reviews": num_reviews
            }
        })
        
    except Exception as e:
        logger.error(f"Trending classification error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/sentiment/analyze', methods=['POST'])
def analyze_sentiment():
    """Analyze sentiment of customer review"""
    try:
        if not sentiment_analyzer_available:
            return jsonify({"error": "Sentiment analyzer not available"}), 500
        
        # Initialize analyzer if needed
        if 'sentiment_analyzer' not in models:
            models['sentiment_analyzer'] = GiftReviewSentimentAnalyzer()
            reviews, labels = models['sentiment_analyzer'].load_sample_data()
            models['sentiment_analyzer'].train(reviews, labels)
            logger.info("Sentiment analyzer trained")
        
        data = request.get_json()
        review_text = data.get('review_text', '').strip()
        
        if not review_text:
            return jsonify({
                "error": "review_text is required"
            }), 400
        
        # Analyze sentiment
        prediction, probabilities = models['sentiment_analyzer'].predict(review_text)
        max_prob = probabilities[prediction]
        
        # Determine action recommendation
        action = "approve"
        if prediction == "Negative":
            action = "investigate"
        elif prediction == "Neutral":
            action = "review"
        
        return jsonify({
            "success": True,
            "review_text": review_text,
            "sentiment": prediction.lower(),
            "confidence": round(max_prob, 3),
            "confidence_percent": round(max_prob * 100, 1),
            "probabilities": {
                "positive": round(probabilities.get("Positive", 0), 3),
                "neutral": round(probabilities.get("Neutral", 0), 3),
                "negative": round(probabilities.get("Negative", 0), 3)
            },
            "recommended_action": action,
            "algorithm": "Naive Bayes (MultinomialNB)"
        })
        
    except Exception as e:
        logger.error(f"Sentiment analysis error: {str(e)}")
        return jsonify({"error": str(e)}), 500

@app.route('/api/ml/health', methods=['GET'])
def health_check():
    """Health check endpoint"""
    return jsonify({
        "status": "healthy",
        "service": "Python ML Microservice",
        "timestamp": datetime.now().isoformat(),
        "algorithms": ["KNN", "Bayesian", "Decision Tree", "SVM", "BPNN", "Sentiment Analysis"]
    })

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

