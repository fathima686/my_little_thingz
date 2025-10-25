"""
Database connection and data access for Python ML Microservice
Connects to the same database as your PHP application
"""

import pymysql
import pandas as pd
import numpy as np
from config import Config
import logging

logger = logging.getLogger(__name__)

class DatabaseConnection:
    """Database connection class for ML service"""
    
    def __init__(self, config):
        self.config = config
        self.connection = None
        self.connect()
    
    def connect(self):
        """Establish database connection"""
        try:
            self.connection = pymysql.connect(
                host=self.config.DB_HOST,
                port=self.config.DB_PORT,
                user=self.config.DB_USER,
                password=self.config.DB_PASSWORD,
                database=self.config.DB_NAME,
                charset='utf8mb4',
                cursorclass=pymysql.cursors.DictCursor
            )
            logger.info("Database connection established")
        except Exception as e:
            logger.error(f"Database connection failed: {str(e)}")
            raise
    
    def get_connection(self):
        """Get database connection"""
        if not self.connection or not self.connection.open:
            self.connect()
        return self.connection
    
    def execute_query(self, query, params=None):
        """Execute SQL query and return results"""
        try:
            connection = self.get_connection()
            with connection.cursor() as cursor:
                cursor.execute(query, params)
                return cursor.fetchall()
        except Exception as e:
            logger.error(f"Query execution failed: {str(e)}")
            raise
    
    def execute_query_df(self, query, params=None):
        """Execute SQL query and return as DataFrame"""
        try:
            results = self.execute_query(query, params)
            return pd.DataFrame(results)
        except Exception as e:
            logger.error(f"DataFrame creation failed: {str(e)}")
            raise

class MLDataProvider:
    """Data provider for ML algorithms"""
    
    def __init__(self, db_connection):
        self.db = db_connection
    
    def get_products_data(self, limit=1000):
        """Get products data for training"""
        query = """
            SELECT 
                a.id, a.title, a.description, a.price, a.category_id, 
                a.image_url, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT %s
        """
        return self.db.execute_query_df(query, (limit,))
    
    def get_user_behavior_data(self, limit=1000):
        """Get user behavior data for training"""
        query = """
            SELECT 
                u.id as user_id, u.name, u.email,
                o.id as order_id, o.total_amount, o.created_at as order_date,
                oi.artwork_id, oi.quantity, oi.price
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status != 'cancelled'
            ORDER BY o.created_at DESC
            LIMIT %s
        """
        return self.db.execute_query_df(query, (limit,))
    
    def get_categories_data(self):
        """Get categories data"""
        query = """
            SELECT id, name, description, status
            FROM categories
            WHERE status = 'active'
            ORDER BY name
        """
        return self.db.execute_query_df(query)
    
    def get_wishlist_data(self, limit=1000):
        """Get wishlist data for collaborative filtering"""
        query = """
            SELECT w.user_id, w.artwork_id, w.created_at
            FROM wishlist w
            ORDER BY w.created_at DESC
            LIMIT %s
        """
        return self.db.execute_query_df(query, (limit,))
    
    def get_ratings_data(self, limit=1000):
        """Get ratings data if available"""
        try:
            query = """
                SELECT user_id, artwork_id, rating, created_at
                FROM ratings
                ORDER BY created_at DESC
                LIMIT %s
            """
            return self.db.execute_query_df(query, (limit,))
        except:
            # Ratings table might not exist
            return pd.DataFrame()
    
    def get_training_data_for_knn(self):
        """Prepare training data for KNN"""
        products_df = self.get_products_data()
        
        if products_df.empty:
            return None
        
        # Create feature matrix
        features = []
        for _, product in products_df.iterrows():
            feature_vector = [
                product['price'],
                product['category_id'],
                len(product['title']),
                len(product['description']),
                product['effective_price'],
                hash(product['category_name']) % 1000,  # Category hash
                product['availability'] == 'in_stock',
                product['availability'] == 'limited',
                product['availability'] == 'out_of_stock',
                product['availability'] == 'pre_order'
            ]
            features.append(feature_vector)
        
        return np.array(features)
    
    def get_training_data_for_bayesian(self):
        """Prepare training data for Bayesian classifier"""
        products_df = self.get_products_data()
        
        if products_df.empty:
            return None, None
        
        # Extract text features
        X = []
        y = []
        
        for _, product in products_df.iterrows():
            # Text features
            title_features = self.extract_text_features(product['title'])
            desc_features = self.extract_text_features(product['description'])
            features = np.concatenate([title_features, desc_features])
            
            X.append(features)
            y.append(product['category_id'])
        
        return np.array(X), np.array(y)
    
    def get_training_data_for_svm(self):
        """Prepare training data for SVM"""
        products_df = self.get_products_data()
        
        if products_df.empty:
            return None, None
        
        X = []
        y = []
        
        for _, product in products_df.iterrows():
            features = [
                product['price'],
                product['category_id'],
                len(product['title']),
                len(product['description']),
                product['effective_price'],
                self.get_luxury_keyword_score(product['title']),
                self.get_luxury_keyword_score(product['description']),
                self.get_availability_score(product['availability'])
            ]
            
            # Binary classification: 1 if price >= 1000, 0 otherwise
            is_premium = 1 if product['price'] >= 1000 else 0
            
            X.append(features)
            y.append(is_premium)
        
        return np.array(X), np.array(y)
    
    def get_training_data_for_bpnn(self):
        """Prepare training data for BPNN"""
        behavior_df = self.get_user_behavior_data()
        
        if behavior_df.empty:
            return None, None
        
        X = []
        y = []
        
        # Group by user
        for user_id, user_data in behavior_df.groupby('user_id'):
            if len(user_data) < 2:  # Need at least 2 interactions
                continue
            
            # User features
            user_features = [
                user_data['total_amount'].mean(),
                len(user_data),
                user_data['quantity'].sum(),
                user_data['artwork_id'].nunique(),
                user_data['total_amount'].std() if len(user_data) > 1 else 0
            ]
            
            # Product features (latest interaction)
            latest_product = user_data.iloc[-1]
            product_features = [
                latest_product['price'],
                latest_product['quantity'],
                latest_product['artwork_id']
            ]
            
            # Combine features
            features = user_features + product_features
            
            # Target: preference score (0-1)
            preference_score = min(1.0, user_data['quantity'].sum() / 10)
            
            X.append(features)
            y.append(preference_score)
        
        return np.array(X), np.array(y)
    
    def extract_text_features(self, text, max_features=20):
        """Extract features from text"""
        if not text:
            return np.zeros(max_features)
        
        words = text.lower().split()
        features = np.zeros(max_features)
        
        for i, word in enumerate(words[:max_features]):
            features[i] = len(word)
        
        return features
    
    def get_luxury_keyword_score(self, text):
        """Get luxury keyword score"""
        if not text:
            return 0
        
        luxury_keywords = [
            'luxury', 'premium', 'exclusive', 'designer', 'handmade',
            'custom', 'artisan', 'deluxe', 'elegant', 'sophisticated'
        ]
        
        text_lower = text.lower()
        score = 0
        for keyword in luxury_keywords:
            if keyword in text_lower:
                score += 1
        
        return min(score / len(luxury_keywords), 1.0)
    
    def get_availability_score(self, availability):
        """Get availability score"""
        scores = {
            'limited': 0.9,
            'rare': 0.8,
            'exclusive': 0.8,
            'out_of_stock': 0.7,
            'pre_order': 0.6,
            'in_stock': 0.3,
            'available': 0.2
        }
        return scores.get(availability, 0.3)








