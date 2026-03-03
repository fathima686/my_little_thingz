# 🎨 Canva-Like Tools Usage Guide

## ✅ What's Been Added

I've enhanced your design editor with professional Canva-like tools. Here's what's now available:

### 1. **Shapes** 🔷
Add professional shapes to your designs:

```javascript
// In your HTML, add these buttons:
editor.addShape('rectangle');
editor.addShape('circle');
editor.addShape('triangle');
editor.addShape('line');
editor.addShape('star');
editor.addShape('arrow');
```

### 2. **Image Filters** 🎨
Apply Instagram-style filters to images:

```javascript
// Apply preset filters
editor.applyFilter('grayscale');
editor.applyFilter('sepia');
editor.applyFilter('vintage');
editor.applyFilter('sharpen');

// Adjust image properties
editor.applyFilter('brightness', 50);  // -100 to 100
editor.applyFilter('contrast', 30);    // -100 to 100
editor.applyFilter('saturation', 20);  // -100 to 100
editor.applyFilter('blur', 10);        // 0 to 100

// Reset all filters
editor.resetFilters();
```

### 3. **Object Manipulation** 🔄
Flip, duplicate, and arrange objects:

```javascript
// Flip objects
editor.flipObject('horizontal');
editor.flipObject('vertical');

// Duplicate selected object
editor.duplicateObject();

// Layer management
editor.bringToFront();
editor.sendToBack();
editor.bringForward();
editor.sendBackward();
```

### 4. **Gradients** 🌈
Create beautiful gradient fills:

```javascript
// Linear gradient
editor.applyGradient('linear', '#667eea', '#764ba2');

// Radial gradient
editor.applyGradient('radial', '#ff6b6b', '#feca57');
```

### 5. **Layers System** 📚
Manage design layers like a pro:

```javascript
// Get all layers
const layers = editor.getLayers();

// Select a layer
editor.selectLayer(0);

// Toggle visibility
editor.toggleLayerVisibility(0);

// Lock/unlock layer
editor.toggleLayerLock(0);

// Move layer
editor.moveLayer(0, 2);

// Delete layer
editor.deleteLayer(0);
```

## 🚀 Quick Integration

### Step 1: Add Shape Buttons to Your HTML

Add this to your left sidebar in `design-editor.html`:

```html
<div class="tool-section">
    <h3>Shapes</h3>
    <div class="tool-grid">
        <button class="tool-btn" onclick="editor.addShape('rectangle')">
            <i class="fas fa-square"></i> Rectangle
        </button>
        <button class="tool-btn" onclick="editor.addShape('circle')">
            <i class="fas fa-circle"></i> Circle
        </button>
        <button class="tool-btn" onclick="editor.addShape('triangle')">
            <i class="fas fa-play"></i> Triangle
        </button>
        <button class="tool-btn" onclick="editor.addShape('star')">
            <i class="fas fa-star"></i> Star
        </button>
    </div>
</div>
```

### Step 2: Add Filter Controls for Images

Add this to your properties panel when an image is selected:

```html
<div class="property-group">
    <label class="property-label">Filters</label>
    <button onclick="editor.applyFilter('grayscale')">Grayscale</button>
    <button onclick="editor.applyFilter('sepia')">Sepia</button>
    <button onclick="editor.applyFilter('vintage')">Vintage</button>
    <button onclick="editor.resetFilters()">Reset</button>
</div>

<div class="property-group">
    <label class="property-label">Brightness</label>
    <input type="range" min="-100" max="100" value="0" 
           oninput="editor.applyFilter('brightness', this.value)">
</div>

<div class="property-group">
    <label class="property-label">Contrast</label>
    <input type="range" min="-100" max="100" value="0" 
           oninput="editor.applyFilter('contrast', this.value)">
</div>
```

### Step 3: Add Manipulation Tools

Add these buttons to your toolbar or properties panel:

```html
<div class="property-group">
    <button onclick="editor.flipObject('horizontal')">
        <i class="fas fa-arrows-alt-h"></i> Flip Horizontal
    </button>
    <button onclick="editor.flipObject('vertical')">
        <i class="fas fa-arrows-alt-v"></i> Flip Vertical
    </button>
    <button onclick="editor.duplicateObject()">
        <i class="fas fa-copy"></i> Duplicate
    </button>
</div>
```

### Step 4: Add Gradient Controls

```html
<div class="property-group">
    <label class="property-label">Gradient</label>
    <input type="color" id="gradColor1" value="#667eea">
    <input type="color" id="gradColor2" value="#764ba2">
    <button onclick="editor.applyGradient('linear', 
                     document.getElementById('gradColor1').value,
                     document.getElementById('gradColor2').value)">
        Apply Linear Gradient
    </button>
    <button onclick="editor.applyGradient('radial',
                     document.getElementById('gradColor1').value,
                     document.getElementById('gradColor2').value)">
        Apply Radial Gradient
    </button>
</div>
```

## 🎯 Demo File

Open `canva-tools-demo.html` in your browser to see all the new tools in action!

This standalone demo shows:
- ✅ All 6 shape types
- ✅ Image filters (grayscale, sepia, vintage, sharpen)
- ✅ Brightness & contrast adjustments
- ✅ Flip horizontal/vertical
- ✅ Opacity control
- ✅ Rotation control
- ✅ Linear & radial gradients
- ✅ Duplicate & delete
- ✅ Text & emoji support

## 📋 Complete Feature List

### Shapes
- Rectangle
- Circle
- Triangle
- Line
- Star (5-pointed)
- Arrow

### Image Filters
- Grayscale
- Sepia
- Vintage
- Sharpen
- Brightness (-100 to 100)
- Contrast (-100 to 100)
- Saturation (-100 to 100)
- Blur (0 to 100)

### Object Controls
- Opacity (0-100%)
- Rotation (0-360°)
- Flip horizontal
- Flip vertical
- Duplicate
- Delete

### Gradients
- Linear gradient (2 colors)
- Radial gradient (2 colors)

### Layer Management
- Get all layers
- Select layer
- Toggle visibility
- Lock/unlock
- Reorder layers
- Delete layer

## 🎨 Styling Tips

All new tools follow your existing design system:
- Primary color: `#6366f1`
- Secondary color: `#8b5cf6`
- Accent color: `#06b6d4`

The tools integrate seamlessly with your current Canva-style layout!

## 🔧 Customization

Want to add more shapes? Just extend the `addShape()` method:

```javascript
case 'heart':
    // Add custom heart shape points
    const heartPoints = [...]; // Define your points
    shape = new fabric.Polygon(heartPoints, defaultOptions);
    break;
```

Want more filters? Add to `applyFilter()`:

```javascript
case 'invert':
    this.selectedObject.filters.push(new fabric.Image.filters.Invert());
    break;
```

## 🎉 You Now Have

A professional design editor with:
- ✅ Background removal (remove.bg API)
- ✅ 6 shape types
- ✅ 8+ image filters
- ✅ Gradient support
- ✅ Layer management
- ✅ Object manipulation
- ✅ Text editing
- ✅ Image upload
- ✅ Undo/redo
- ✅ Zoom controls
- ✅ Templates

Your editor is now comparable to Canva's core features! 🚀
