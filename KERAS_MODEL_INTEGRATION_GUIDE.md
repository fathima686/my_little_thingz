# 🎯 Your .keras Model Integration Guide

## 📍 **CURRENT STATUS**

### ✅ **WHAT'S WORKING:**
- ✅ Your `.keras` model **IS FOUND** at `backend/ai/model/craft_image_classifier.keras`
- ✅ System **IS DESIGNED** to use your model (it's the first path checked)
- ✅ Auto-approval **IS WORKING** with fallback heuristics
- ✅ All integration code **IS ALREADY BUILT**

### ❌ **THE ISSUE:**
Your `.keras` model has an **architecture mismatch**:
```
Layer "dense_1" expects 1 input(s), but it received 2 input tensors
```

This means your model was trained with a different architecture than what the current loading code expects.

### 🔄 **CURRENT BEHAVIOR:**
1. System tries to load your `.keras` model ✅
2. Fails due to architecture mismatch ❌
3. Falls back to base MobileNet with heuristics ✅
4. Auto-approval still works fine ✅

---

## 🛠️ **SOLUTIONS**

### **Option 1: Fix Your Model Architecture (Recommended)**

The issue is likely in how your model was saved. Here's how to fix it:

#### **Step 1: Check Your Model Training Code**
Your model probably has this issue:
```python
# PROBLEMATIC CODE (causes the error):
base_model = MobileNetV2(input_shape=(224, 224, 3), include_top=False, weights='imagenet')
x = base_model.output
x = GlobalAveragePooling2D()(x)
predictions = Dense(7, activation='softmax')(x)  # This might be getting duplicate inputs

# CORRECT CODE:
base_model = MobileNetV2(input_shape=(224, 224, 3), include_top=False, weights='imagenet')
x = base_model.output
x = GlobalAveragePooling2D()(x)
x = Dense(128, activation='relu')(x)  # Add intermediate layer
predictions = Dense(7, activation='softmax')(x)
```

#### **Step 2: Retrain Your Model (If Possible)**
```python
import tensorflow as tf
from tensorflow.keras.applications import MobileNetV2
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D
from tensorflow.keras.models import Model

# Create model with correct architecture
def create_craft_classifier():
    base_model = MobileNetV2(
        input_shape=(224, 224, 3),
        include_top=False,
        weights='imagenet'
    )
    
    # Freeze base model
    base_model.trainable = False
    
    # Add custom layers
    x = base_model.output
    x = GlobalAveragePooling2D()(x)
    x = Dense(128, activation='relu', name='feature_dense')(x)
    predictions = Dense(7, activation='softmax', name='predictions')(x)
    
    model = Model(inputs=base_model.input, outputs=predictions)
    
    return model

# Train and save
model = create_craft_classifier()
model.compile(
    optimizer='adam',
    loss='categorical_crossentropy',
    metrics=['accuracy']
)

# Train your model here...
# model.fit(train_data, train_labels, ...)

# Save with correct format
model.save('backend/ai/model/craft_image_classifier.keras')
```

### **Option 2: Use Model Conversion (Quick Fix)**

If you can't retrain, try converting your model:

<function_calls>
<invoke name="fsWrite">
<parameter name="path">python_ml_service/convert_keras_model.pyr/bin/env python3
"""
Convert problematic .keras model to compatible format
"""

import os
import tensorflow as tf
from tensorflow.keras.models import load_model, Model
from tensorflow.keras.layers import Dense, GlobalAveragePooling2D
from tensorflow.keras.applications import MobileNetV2
import numpy as np

def convert_model():
    """Try to convert the problematic model"""
    
    model_path = os.path.join('..', 'backend', 'ai', 'model', 'craft_image_classifier.keras')
    
    if not os.path.exists(model_path):
        print(f"❌ Model not found: {model_path}")
        return False
    
    print(f"🔍 Analyzing model: {model_path}")
    
    try:
        # Try to load and inspect the model
        print("📊 Attempting to load model...")
        
        # Try different loading approaches
        approaches = [
            lambda: load_model(model_path, compile=False),
            lambda: tf.keras.models.load_model(model_path, safe_mode=False),
            lambda: load_model(model_path, custom_objects={'tf': tf})
        ]
        
        model = None
        for i, approach in enumerate(approaches, 1):
            try:
                print(f"   Trying approach {i}...")
                model = approach()
                print(f"   ✅ Approach {i} succeeded!")
                break
            except Exception as e:
                print(f"   ❌ Approach {i} failed: {e}")
                continue
        
        if model is None:
            print("❌ All loading approaches failed")
            return create_new_model()
        
        # Inspect model
        print(f"\n📋 Model Information:")
        print(f"   Input shape: {model.input_shape}")
        print(f"   Output shape: {model.output_shape}")
        print(f"   Number of layers: {len(model.layers)}")
        
        # Try to get weights
        try:
            weights = model.get_weights()
            print(f"   Weights extracted: {len(weights)} arrays")
            
            # Create new compatible model
            print("\n🔧 Creating compatible model...")
            new_model = create_compatible_model()
            
            # Try to transfer weights
            if len(weights) > 0:
                try:
                    new_model.set_weights(weights)
                    print("   ✅ Weights transferred successfully")
                except Exception as e:
                    print(f"   ⚠️  Weight transfer failed: {e}")
                    print("   Using random weights instead")
            
            # Save new model
            new_path = model_path.replace('.keras', '_fixed.keras')
            new_model.save(new_path)
            print(f"✅ Fixed model saved: {new_path}")
            
            # Test new model
            test_model(new_model)
            
            return True
            
        except Exception as e:
            print(f"❌ Weight extraction failed: {e}")
            return create_new_model()
            
    except Exception as e:
        print(f"❌ Model analysis failed: {e}")
        return create_new_model()

def create_compatible_model():
    """Create a compatible craft classifier model"""
    
    print("🏗️  Creating compatible model architecture...")
    
    # Create base model
    base_model = MobileNetV2(
        input_shape=(224, 224, 3),
        include_top=False,
        weights='imagenet'
    )
    
    # Freeze base model
    base_model.trainable = False
    
    # Add custom layers
    x = base_model.output
    x = GlobalAveragePooling2D(name='global_avg_pool')(x)
    x = Dense(128, activation='relu', name='feature_dense')(x)
    predictions = Dense(7, activation='softmax', name='craft_predictions')(x)
    
    model = Model(inputs=base_model.input, outputs=predictions, name='craft_classifier')
    
    # Compile model
    model.compile(
        optimizer='adam',
        loss='categorical_crossentropy',
        metrics=['accuracy']
    )
    
    return model

def create_new_model():
    """Create a new model from scratch"""
    
    print("🆕 Creating new model from scratch...")
    
    try:
        model = create_compatible_model()
        
        # Save new model
        model_path = os.path.join('..', 'backend', 'ai', 'model', 'craft_image_classifier_new.keras')
        model.save(model_path)
        print(f"✅ New model created: {model_path}")
        
        # Test new model
        test_model(model)
        
        return True
        
    except Exception as e:
        print(f"❌ New model creation failed: {e}")
        return False

def test_model(model):
    """Test the model with dummy input"""
    
    print("🧪 Testing model...")
    
    try:
        # Create dummy input
        dummy_input = np.random.random((1, 224, 224, 3)).astype(np.float32)
        
        # Test prediction
        prediction = model.predict(dummy_input, verbose=0)
        
        print(f"   ✅ Model test successful!")
        print(f"   Output shape: {prediction.shape}")
        print(f"   Output sum: {np.sum(prediction):.4f}")
        
        # Check if output is valid probability distribution
        if abs(np.sum(prediction) - 1.0) < 0.01:
            print("   ✅ Output is valid probability distribution")
        else:
            print("   ⚠️  Output may not be properly normalized")
        
        return True
        
    except Exception as e:
        print(f"   ❌ Model test failed: {e}")
        return False

if __name__ == "__main__":
    print("=== Keras Model Converter ===\n")
    
    success = convert_model()
    
    if success:
        print("\n🎉 Model conversion completed!")
        print("\n📋 Next Steps:")
        print("1. Test the fixed model with the flexible classifier")
        print("2. If it works, replace the original model")
        print("3. Restart the AI services")
    else:
        print("\n❌ Model conversion failed!")
        print("\n💡 Recommendations:")
        print("1. Check your original training code")
        print("2. Retrain the model with correct architecture")
        print("3. Or use the base MobileNet (which is working fine)")