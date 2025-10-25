"""
Enhanced Database Integration for Dynamic SVM Recommendations
Handles real-time data updates and user behavior tracking
"""

import pymysql
import pandas as pd
import numpy as np
from datetime import datetime, timedelta
import logging
from typing import Dict, List, Tuple, Optional, Any
import json

logger = logging.getLogger(__name__)

class EnhancedDatabaseConnection:
    """Enhanced database connection with real-time data access"""
    
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
                cursorclass=pymysql.cursors.DictCursor,
                autocommit=True
            )
            logger.info("Enhanced database connection established")
        except Exception as e:
            logger.error(f"Database connection failed: {str(e)}")
            raise
    
    def get_connection(self):
        """Get database connection"""
        if not self.connection or not self.connection.open:
            self.connect()
        return self.connection
    
    def execute_query(self, query: str, params=None):
        """Execute SQL query and return results"""
        try:
            connection = self.get_connection()
            with connection.cursor() as cursor:
                cursor.execute(query, params)
                return cursor.fetchall()
        except Exception as e:
            logger.error(f"Query execution failed: {str(e)}")
            raise
    
    def execute_query_df(self, query: str, params=None):
        """Execute SQL query and return as DataFrame"""
        try:
            results = self.execute_query(query, params)
            return pd.DataFrame(results)
        except Exception as e:
            logger.error(f"DataFrame creation failed: {str(e)}")
            raise

class DynamicDataProvider:
    """Dynamic data provider for real-time recommendations"""
    
    def __init__(self, db_connection):
        self.db = db_connection
    
    def get_user_profile(self, user_id: int) -> Dict:
        """Get comprehensive user profile for recommendations"""
        query = """
            SELECT 
                u.id, u.name, u.email, u.created_at,
                u.age, u.gender, u.location,
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(o.total_amount), 0) as total_spent,
                COALESCE(AVG(o.total_amount), 0) as avg_order_value,
                DATEDIFF(NOW(), u.created_at) as days_since_registration
            FROM users u
            LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
            WHERE u.id = %s
            GROUP BY u.id
        """
        
        result = self.db.execute_query(query, (user_id,))
        if not result:
            return {}
        
        user_data = result[0]
        
        # Get additional user behavior data
        behavior_data = self.get_user_behavior_stats(user_id)
        user_data.update(behavior_data)
        
        return user_data
    
    def get_user_behavior_stats(self, user_id: int) -> Dict:
        """Get user behavior statistics"""
        stats = {}
        
        # Wishlist count
        try:
            wishlist_query = "SELECT COUNT(*) as count FROM wishlist WHERE user_id = %s"
            wishlist_result = self.db.execute_query(wishlist_query, (user_id,))
            stats['wishlist_items'] = wishlist_result[0]['count'] if wishlist_result else 0
        except:
            stats['wishlist_items'] = 0
        
        # Cart abandonment rate
        try:
            cart_query = """
                SELECT 
                    COUNT(*) as total_carts,
                    COUNT(CASE WHEN o.status = 'completed' THEN 1 END) as completed_orders
                FROM cart c
                LEFT JOIN orders o ON c.user_id = o.user_id
                WHERE c.user_id = %s
            """
            cart_result = self.db.execute_query(cart_query, (user_id,))
            if cart_result and cart_result[0]['total_carts'] > 0:
                stats['cart_abandonment_rate'] = 1 - (cart_result[0]['completed_orders'] / cart_result[0]['total_carts'])
            else:
                stats['cart_abandonment_rate'] = 0.0
        except:
            stats['cart_abandonment_rate'] = 0.0
        
        # Purchase frequency (orders per month)
        try:
            freq_query = """
                SELECT COUNT(*) as order_count
                FROM orders 
                WHERE user_id = %s AND status != 'cancelled' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
            """
            freq_result = self.db.execute_query(freq_query, (user_id,))
            stats['purchase_frequency'] = (freq_result[0]['order_count'] / 3) if freq_result else 0.0
        except:
            stats['purchase_frequency'] = 0.0
        
        # Category diversity
        try:
            cat_query = """
                SELECT COUNT(DISTINCT a.category_id) as category_count
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN artworks a ON oi.artwork_id = a.id
                WHERE o.user_id = %s AND o.status != 'cancelled'
            """
            cat_result = self.db.execute_query(cat_query, (user_id,))
            stats['category_diversity'] = cat_result[0]['category_count'] if cat_result else 0
        except:
            stats['category_diversity'] = 0
        
        # Recent activity
        try:
            activity_query = """
                SELECT 
                    COUNT(DISTINCT DATE(created_at)) as active_days,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_session_duration
                FROM user_sessions 
                WHERE user_id = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS)
            """
            activity_result = self.db.execute_query(activity_query, (user_id,))
            if activity_result:
                stats['recent_views'] = activity_result[0]['active_days'] * 10  # Estimate
                stats['session_duration_avg'] = activity_result[0]['avg_session_duration'] or 0
            else:
                stats['recent_views'] = 0
                stats['session_duration_avg'] = 0
        except:
            stats['recent_views'] = 0
            stats['session_duration_avg'] = 0
        
        return stats
    
    def get_products_with_features(self, limit: int = 1000) -> pd.DataFrame:
        """Get products with enhanced features"""
        query = """
            SELECT 
                a.id as product_id,
                a.title, a.description, a.price, a.image_url,
                a.category_id, a.availability, a.created_at,
                c.name as category_name,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN a.offer_price
                    ELSE a.price 
                END as effective_price,
                CASE 
                    WHEN a.offer_price IS NOT NULL AND a.offer_price > 0 THEN 
                        ROUND(((a.price - a.offer_price) / a.price) * 100, 2)
                    ELSE 0 
                END as discount_percentage,
                COALESCE(a.stock_quantity, 0) as stock_level
            FROM artworks a 
            LEFT JOIN categories c ON a.category_id = c.id 
            WHERE a.status = 'active'
            ORDER BY a.created_at DESC
            LIMIT %s
        """
        
        products_df = self.db.execute_query_df(query, (limit,))
        
        # Add computed features
        products_df = self._add_computed_features(products_df)
        
        return products_df
    
    def _add_computed_features(self, products_df: pd.DataFrame) -> pd.DataFrame:
        """Add computed features to products"""
        # Rating (mock data - replace with actual ratings if available)
        products_df['rating'] = np.random.uniform(3.0, 5.0, len(products_df))
        
        # Review count (mock data)
        products_df['review_count'] = np.random.randint(0, 50, len(products_df))
        
        # Popularity score (based on creation date and price)
        products_df['popularity_score'] = self._calculate_popularity_score(products_df)
        
        # Trending score (recent products get higher score)
        products_df['trending_score'] = self._calculate_trending_score(products_df)
        
        # Seasonal relevance
        products_df['seasonal_relevance'] = self._calculate_seasonal_relevance(products_df)
        
        # Availability score
        products_df['availability_score'] = products_df['availability'].map({
            'in_stock': 0.9,
            'limited': 0.7,
            'out_of_stock': 0.1,
            'pre_order': 0.5
        }).fillna(0.5)
        
        return products_df
    
    def _calculate_popularity_score(self, products_df: pd.DataFrame) -> np.ndarray:
        """Calculate popularity score based on various factors"""
        scores = []
        for _, product in products_df.iterrows():
            score = 0.5  # Base score
            
            # Price factor (mid-range products are more popular)
            price = product['price']
            if 500 <= price <= 2000:
                score += 0.2
            elif price < 500:
                score += 0.1
            
            # Category factor (some categories are more popular)
            category = product['category_name']
            if category in ['Gift Boxes', 'Chocolates', 'Flowers']:
                score += 0.2
            
            # Discount factor
            if product['discount_percentage'] > 0:
                score += 0.1
            
            scores.append(min(score, 1.0))
        
        return np.array(scores)
    
    def _calculate_trending_score(self, products_df: pd.DataFrame) -> np.ndarray:
        """Calculate trending score based on recency"""
        now = datetime.now()
        scores = []
        
        for _, product in products_df.iterrows():
            days_old = (now - product['created_at']).days
            if days_old <= 7:
                score = 1.0
            elif days_old <= 30:
                score = 0.8
            elif days_old <= 90:
                score = 0.6
            else:
                score = 0.4
            
            scores.append(score)
        
        return np.array(scores)
    
    def _calculate_seasonal_relevance(self, products_df: pd.DataFrame) -> np.ndarray:
        """Calculate seasonal relevance score"""
        current_month = datetime.now().month
        scores = []
        
        for _, product in products_df.iterrows():
            title_lower = product['title'].lower()
            score = 0.5  # Base score
            
            # Seasonal keywords
            if current_month in [12, 1, 2]:  # Winter
                if any(word in title_lower for word in ['winter', 'christmas', 'new year', 'holiday']):
                    score = 1.0
            elif current_month in [3, 4, 5]:  # Spring
                if any(word in title_lower for word in ['spring', 'easter', 'fresh', 'bloom']):
                    score = 1.0
            elif current_month in [6, 7, 8]:  # Summer
                if any(word in title_lower for word in ['summer', 'beach', 'sunny', 'bright']):
                    score = 1.0
            elif current_month in [9, 10, 11]:  # Fall
                if any(word in title_lower for word in ['autumn', 'fall', 'harvest', 'cozy']):
                    score = 1.0
            
            scores.append(score)
        
        return np.array(scores)
    
    def get_user_interactions(self, user_id: int, days_back: int = 30) -> pd.DataFrame:
        """Get user interactions for training"""
        query = """
            SELECT 
                'purchase' as interaction_type,
                o.user_id,
                oi.artwork_id as product_id,
                o.created_at as timestamp,
                oi.quantity,
                oi.price,
                1 as liked
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = %s AND o.status != 'cancelled'
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            
            UNION ALL
            
            SELECT 
                'wishlist' as interaction_type,
                w.user_id,
                w.artwork_id as product_id,
                w.created_at as timestamp,
                1 as quantity,
                0 as price,
                1 as liked
            FROM wishlist w
            WHERE w.user_id = %s
            AND w.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            
            ORDER BY timestamp DESC
        """
        
        return self.db.execute_query_df(query, (user_id, days_back, user_id, days_back))
    
    def get_all_user_interactions(self, days_back: int = 90) -> pd.DataFrame:
        """Get all user interactions for model training"""
        query = """
            SELECT 
                'purchase' as interaction_type,
                o.user_id,
                oi.artwork_id as product_id,
                o.created_at as timestamp,
                oi.quantity,
                oi.price,
                1 as liked
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status != 'cancelled'
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            
            UNION ALL
            
            SELECT 
                'wishlist' as interaction_type,
                w.user_id,
                w.artwork_id as product_id,
                w.created_at as timestamp,
                1 as quantity,
                0 as price,
                1 as liked
            FROM wishlist w
            WHERE w.created_at >= DATE_SUB(NOW(), INTERVAL %s DAY)
            
            ORDER BY timestamp DESC
        """
        
        return self.db.execute_query_df(query, (days_back, days_back))
    
    def log_user_behavior(self, user_id: int, product_id: int, 
                         behavior_type: str, additional_data: Dict = None):
        """Log user behavior for dynamic learning"""
        try:
            query = """
                INSERT INTO user_behavior_log 
                (user_id, product_id, behavior_type, additional_data, created_at)
                VALUES (%s, %s, %s, %s, NOW())
            """
            
            additional_data_json = json.dumps(additional_data) if additional_data else None
            
            self.db.execute_query(query, (
                user_id, product_id, behavior_type, additional_data_json
            ))
            
            logger.info(f"Logged behavior: {behavior_type} for user {user_id}, product {product_id}")
            
        except Exception as e:
            logger.error(f"Failed to log behavior: {e}")
    
    def get_recent_purchases(self, hours_back: int = 24) -> pd.DataFrame:
        """Get recent purchases for dynamic retraining"""
        query = """
            SELECT 
                o.user_id,
                oi.artwork_id as product_id,
                o.created_at,
                oi.quantity,
                oi.price,
                o.total_amount
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status != 'cancelled'
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL %s HOUR)
            ORDER BY o.created_at DESC
        """
        
        return self.db.execute_query_df(query, (hours_back,))
    
    def create_behavior_log_table(self):
        """Create user behavior log table if it doesn't exist"""
        query = """
            CREATE TABLE IF NOT EXISTS user_behavior_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                behavior_type VARCHAR(50) NOT NULL,
                additional_data JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_behavior (user_id, behavior_type, created_at),
                INDEX idx_product_behavior (product_id, behavior_type, created_at)
            )
        """
        
        try:
            self.db.execute_query(query)
            logger.info("User behavior log table created/verified")
        except Exception as e:
            logger.error(f"Failed to create behavior log table: {e}")
    
    def get_model_performance_metrics(self) -> Dict:
        """Get model performance metrics"""
        try:
            # Get recent recommendation accuracy
            query = """
                SELECT 
                    COUNT(*) as total_recommendations,
                    COUNT(CASE WHEN behavior_type = 'purchase' THEN 1 END) as successful_recommendations
                FROM user_behavior_log 
                WHERE behavior_type IN ('purchase', 'add_to_cart', 'view')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            """
            
            result = self.db.execute_query(query)
            if result:
                total = result[0]['total_recommendations']
                successful = result[0]['successful_recommendations']
                accuracy = (successful / total) if total > 0 else 0
                
                return {
                    'total_recommendations': total,
                    'successful_recommendations': successful,
                    'accuracy': accuracy,
                    'period_days': 7
                }
            else:
                return {'total_recommendations': 0, 'successful_recommendations': 0, 'accuracy': 0}
                
        except Exception as e:
            logger.error(f"Failed to get performance metrics: {e}")
            return {'error': str(e)}






