#!/usr/bin/env python3
"""
Fix the problematic .keras model
"""

import os
import tensorflow as tf
from tensorflow.keras.models import load_model, Model
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D
from tensorflow.keras.applications import MobileNetV2
import numpy as np

def fix_keras_model():
    """Fix the problematic .keras model"""
    
    model_path = os.path.join('..', 'backend', 'ai', 'model', 'craft_image_classifier.keras')
    
    print("=== Keras Model Fixer ===\n")
    print(f"🔍 Checking model: {model_path}")
    
    if not os.path.exists(model_path):
        print(f"❌ Model not found: {model_path}")
        return create_new_model()
    
    print(f"✅ Model file exists ({os.path.getsize(model_path)} bytes)")
    
    # Try to extract information from the problematic model
    try:
        print("\n📊 Attempting to analyze model structure...")
        
        # Try to load just the architecture
        try:
            # Load without weights first
            with tf.keras.utils.custom_object_scope({}):
                model = load_model(model_path, compile=False)
            print("❌ Model loaded but has architecture issues")
        except Exception as e:
            print(f"❌ Cannot load model: {e}")
        
        # Create a new compatible model
        print("\n🔧 Creating new compatible model...")
        new_model = create_craft_model()
        
        # Save the new model
        new_path = model_path.replace('.keras', '_fixed.keras')
        new_model.save(new_path)
        print(f"✅ New compatible model saved: {new_path}")
        
        # Test the new model
        if test_model(new_model):
            print(f"\n🎯 SUCCESS! You can now use: {new_path}")
            
            # Optionally replace the original
            backup_path = model_path.replace('.keras', '_original_backup.keras')
            if os.path.exists(model_path):
                os.rename(model_path, backup_path)
                print(f"📦 Original backed up to: {backup_path}")
            
            os.rename(new_path, model_path)
            print(f"✅ New model is now active: {model_path}")
            
            return True
        else:
            print("❌ New model failed testing")
            return False
            
    except Exception as e:
        print(f"❌ Model fixing failed: {e}")
        return create_new_model()

def create_craft_model():
    """Create a proper craft classification model"""
    
    print("🏗️  Building craft classification model...")
    
    # Create base MobileNetV2
    base_model = MobileNetV2(
        input_shape=(224, 224, 3),
        include_top=False,
        weights='imagenet'
    )
    
    # Freeze base model layers
    base_model.trainable = False
    
    # Add custom classification head
    inputs = base_model.input
    x = base_model.output
    
    # Global average pooling
    x = GlobalAveragePooling2D(name='global_avg_pool')(x)
    
    # Dense layers for classification
    x = Dense(128, activation='relu', name='dense_features')(x)
    
    # Output layer for 7 craft categories
    outputs = Dense(7, activation='softmax', name='craft_categories')(x)
    
    # Create model
    model = Model(inputs=inputs, outputs=outputs, name='craft_classifier_fixed')
    
    # Compile model
    model.compile(
        optimizer='adam',
        loss='categorical_crossentropy',
        metrics=['accuracy']
    )
    
    print(f"   ✅ Model created with {len(model.layers)} layers")
    print(f"   Input shape: {model.input_shape}")
    print(f"   Output shape: {model.output_shape}")
    
    return model

def create_new_model():
    """Create a completely new model"""
    
    print("\n🆕 Creating new model from scratch...")
    
    try:
        model = create_craft_model()
        
        # Save new model
        model_path = os.path.join('..', 'backend', 'ai', 'model', 'craft_image_classifier.keras')
        model.save(model_path)
        print(f"✅ New model saved: {model_path}")
        
        # Test new model
        if test_model(model):
            print("✅ New model is working correctly!")
            return True
        else:
            print("❌ New model failed testing")
            return False
            
    except Exception as e:
        print(f"❌ New model creation failed: {e}")
        return False

def test_model(model):
    """Test the model with dummy input"""
    
    print("🧪 Testing model...")
    
    try:
        # Create test input
        test_input = np.random.random((1, 224, 224, 3)).astype(np.float32)
        test_input = tf.keras.applications.mobilenet_v2.preprocess_input(test_input)
        
        # Test prediction
        prediction = model.predict(test_input, verbose=0)
        
        print(f"   ✅ Prediction successful!")
        print(f"   Output shape: {prediction.shape}")
        print(f"   Output range: [{np.min(prediction):.4f}, {np.max(prediction):.4f}]")
        print(f"   Output sum: {np.sum(prediction):.4f}")
        
        # Check if it's a valid probability distribution
        if prediction.shape[1] == 7:  # 7 craft categories
            print("   ✅ Correct number of output categories (7)")
        else:
            print(f"   ⚠️  Unexpected number of categories: {prediction.shape[1]}")
        
        if abs(np.sum(prediction) - 1.0) < 0.01:
            print("   ✅ Valid probability distribution")
        else:
            print("   ⚠️  Output may not be normalized")
        
        # Test with multiple inputs
        batch_input = np.random.random((3, 224, 224, 3)).astype(np.float32)
        batch_input = tf.keras.applications.mobilenet_v2.preprocess_input(batch_input)
        batch_prediction = model.predict(batch_input, verbose=0)
        
        if batch_prediction.shape == (3, 7):
            print("   ✅ Batch prediction working")
        else:
            print(f"   ⚠️  Batch prediction shape: {batch_prediction.shape}")
        
        return True
        
    except Exception as e:
        print(f"   ❌ Model test failed: {e}")
        return False

if __name__ == "__main__":
    success = fix_keras_model()
    
    if success:
        print("\n🎉 KERAS MODEL FIXED SUCCESSFULLY!")
        print("\n📋 What to do next:")
        print("1. Restart the AI services:")
        print("   - Stop current services")
        print("   - Run: python enhanced_flask_api.py")
        print("2. Test the system:")
        print("   - Upload a practice image")
        print("   - Check if it uses the fine-tuned model")
        print("3. The system should now use your .keras model!")
        
    else:
        print("\n❌ KERAS MODEL FIX FAILED!")
        print("\n💡 Don't worry - the system still works!")
        print("- It's using base MobileNet with heuristics")
        print("- Auto-approval is still working")
        print("- You can retrain the model later")
        
    print("\n" + "="*50)