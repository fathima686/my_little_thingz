#!/usr/bin/env python3
"""
Start script for Python ML Microservice
Handles environment setup and service startup
"""

import os
import sys
import subprocess
import time
from pathlib import Path

def check_python_version():
    """Check if Python version is compatible"""
    if sys.version_info < (3, 8):
        print("❌ Python 3.8+ is required")
        print(f"   Current version: {sys.version}")
        return False
    print(f"✅ Python version: {sys.version}")
    return True

def check_virtual_environment():
    """Check if virtual environment is activated"""
    if hasattr(sys, 'real_prefix') or (hasattr(sys, 'base_prefix') and sys.base_prefix != sys.prefix):
        print("✅ Virtual environment detected")
        return True
    else:
        print("⚠️  Virtual environment not detected")
        print("   Consider running: python -m venv venv && source venv/bin/activate")
        return True  # Not required, but recommended

def install_dependencies():
    """Install required dependencies"""
    print("📦 Installing dependencies...")
    try:
        subprocess.run([sys.executable, "-m", "pip", "install", "-r", "requirements.txt"], 
                      check=True, capture_output=True, text=True)
        print("✅ Dependencies installed successfully")
        return True
    except subprocess.CalledProcessError as e:
        print(f"❌ Failed to install dependencies: {e}")
        print(f"   Error: {e.stderr}")
        return False

def check_environment_file():
    """Check if environment file exists"""
    env_file = Path(".env")
    if env_file.exists():
        print("✅ Environment file (.env) found")
        return True
    else:
        print("⚠️  Environment file (.env) not found")
        print("   Copy env_example.txt to .env and configure your settings")
        return False

def create_directories():
    """Create necessary directories"""
    directories = ["models", "data", "cache", "logs"]
    for directory in directories:
        Path(directory).mkdir(exist_ok=True)
    print("✅ Directories created")

def start_service():
    """Start the Flask service"""
    print("🚀 Starting Python ML Microservice...")
    try:
        # Import and run the app
        from app import app
        app.run(host='0.0.0.0', port=5000, debug=True)
    except KeyboardInterrupt:
        print("\n👋 Service stopped by user")
    except Exception as e:
        print(f"❌ Failed to start service: {e}")
        return False
    return True

def main():
    """Main startup function"""
    print("🐍 Python ML Microservice Startup")
    print("=" * 40)
    
    # Check Python version
    if not check_python_version():
        sys.exit(1)
    
    # Check virtual environment
    check_virtual_environment()
    
    # Install dependencies
    if not install_dependencies():
        print("❌ Failed to install dependencies")
        sys.exit(1)
    
    # Check environment file
    check_environment_file()
    
    # Create directories
    create_directories()
    
    print("\n🎯 Starting service...")
    print("   Service will be available at: http://localhost:5000")
    print("   API endpoints: http://localhost:5000/api/ml/")
    print("   Health check: http://localhost:5000/api/ml/health")
    print("\n   Press Ctrl+C to stop the service")
    print("=" * 40)
    
    # Start the service
    start_service()

if __name__ == "__main__":
    main()


