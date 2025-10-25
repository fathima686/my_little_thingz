"""
Configuration file for Python ML Microservice
"""

import os
from dotenv import load_dotenv

# Load environment variables
load_dotenv()

class Config:
    """Base configuration class"""
    
    # Flask Configuration
    SECRET_KEY = os.environ.get('SECRET_KEY', 'your-secret-key-here')
    DEBUG = os.environ.get('DEBUG', 'False').lower() == 'true'
    HOST = os.environ.get('HOST', '0.0.0.0')
    PORT = int(os.environ.get('PORT', 5000))
    
    # Database Configuration (for connecting to your PHP database)
    DB_HOST = os.environ.get('DB_HOST', 'localhost')
    DB_PORT = int(os.environ.get('DB_PORT', 3306))
    DB_NAME = os.environ.get('DB_NAME', 'my_little_thingz')
    DB_USER = os.environ.get('DB_USER', 'root')
    DB_PASSWORD = os.environ.get('DB_PASSWORD', '')
    
    # ML Configuration
    MODELS_DIR = os.environ.get('MODELS_DIR', 'models')
    DATA_DIR = os.environ.get('DATA_DIR', 'data')
    CACHE_DIR = os.environ.get('CACHE_DIR', 'cache')
    
    # Algorithm Parameters
    KNN_K = int(os.environ.get('KNN_K', 5))
    BAYESIAN_CONFIDENCE_THRESHOLD = float(os.environ.get('BAYESIAN_CONFIDENCE_THRESHOLD', 0.75))
    SVM_KERNEL = os.environ.get('SVM_KERNEL', 'rbf')
    BPNN_HIDDEN_LAYERS = eval(os.environ.get('BPNN_HIDDEN_LAYERS', '(50, 25)'))
    BPNN_LEARNING_RATE = float(os.environ.get('BPNN_LEARNING_RATE', 0.001))
    
    # API Configuration
    CORS_ORIGINS = os.environ.get('CORS_ORIGINS', '*').split(',')
    RATE_LIMIT = os.environ.get('RATE_LIMIT', '1000/hour')
    
    # Logging Configuration
    LOG_LEVEL = os.environ.get('LOG_LEVEL', 'INFO')
    LOG_FILE = os.environ.get('LOG_FILE', 'ml_service.log')

class DevelopmentConfig(Config):
    """Development configuration"""
    DEBUG = True
    LOG_LEVEL = 'DEBUG'

class ProductionConfig(Config):
    """Production configuration"""
    DEBUG = False
    LOG_LEVEL = 'WARNING'

class TestingConfig(Config):
    """Testing configuration"""
    TESTING = True
    DEBUG = True
    DB_NAME = 'test_my_little_things'

# Configuration mapping
config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'testing': TestingConfig,
    'default': DevelopmentConfig
}


