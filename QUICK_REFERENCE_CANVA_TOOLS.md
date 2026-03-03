# 🎨 Quick Reference: Canva Tools

## Shapes

```javascript
editor.addShape('rectangle')  // Add rectangle
editor.addShape('circle')     // Add circle
editor.addShape('triangle')   // Add triangle
editor.addShape('line')       // Add line
editor.addShape('star')       // Add 5-point star
editor.addShape('arrow')      // Add arrow
```

## Image Filters

```javascript
// Preset Filters
editor.applyFilter('grayscale')  // Black & white
editor.applyFilter('sepia')      // Brown vintage tone
editor.applyFilter('vintage')    // Old photo effect
editor.applyFilter('sharpen')    // Enhance details

// Adjustable Filters (value: -100 to 100)
editor.applyFilter('brightness', 50)   // Lighter/darker
editor.applyFilter('contrast', 30)     // More/less contrast
editor.applyFilter('saturation', 20)   // Color intensity
editor.applyFilter('blur', 10)         // Blur effect (0-100)

// Reset
editor.resetFilters()  // Remove all filters
```

## Object Manipulation

```javascript
editor.flipObject('horizontal')  // Mirror left-right
editor.flipObject('vertical')    // Mirror top-bottom
editor.duplicateObject()         // Clone selected object
editor.bringToFront()           // Move to top layer
editor.sendToBack()             // Move to bottom layer
editor.bringForward()           // Move up one layer
editor.sendBackward()           // Move down one layer
```

## Gradients

```javascript
// Linear gradient (left to right)
editor.applyGradient('linear', '#667eea', '#764ba2')

// Radial gradient (center outward)
editor.applyGradient('radial', '#ff6b6b', '#feca57')
```

## Layer Management

```javascript
const layers = editor.getLayers()        // Get all layers
editor.selectLayer(0)                    // Select layer by index
editor.toggleLayerVisibility(0)          // Show/hide layer
editor.toggleLayerLock(0)                // Lock/unlock layer
editor.moveLayer(0, 2)                   // Move layer 0 to position 2
editor.deleteLayer(0)                    // Delete layer
```

## Object Properties

```javascript
// Direct property changes (when object is selected)
editor.selectedObject.set('opacity', 0.5)      // 0-1 (0-100%)
editor.selectedObject.set('angle', 45)         // 0-360 degrees
editor.selectedObject.set('fill', '#667eea')   // Color
editor.selectedObject.set('stroke', '#000')    // Border color
editor.selectedObject.set('strokeWidth', 3)    // Border width
editor.canvas.renderAll()                      // Apply changes
```

## HTML Integration Examples

### Shape Buttons
```html
<button onclick="editor.addShape('rectangle')">
    <i class="fas fa-square"></i> Rectangle
</button>
<button onclick="editor.addShape('circle')">
    <i class="fas fa-circle"></i> Circle
</button>
<button onclick="editor.addShape('star')">
    <i class="fas fa-star"></i> Star
</button>
```

### Filter Buttons
```html
<button onclick="editor.applyFilter('grayscale')">Grayscale</button>
<button onclick="editor.applyFilter('sepia')">Sepia</button>
<button onclick="editor.resetFilters()">Reset</button>
```

### Adjustment Sliders
```html
<label>Brightness</label>
<input type="range" min="-100" max="100" value="0" 
       oninput="editor.applyFilter('brightness', this.value)">

<label>Opacity</label>
<input type="range" min="0" max="100" value="100" 
       oninput="editor.selectedObject.set('opacity', this.value/100); 
                editor.canvas.renderAll()">
```

### Manipulation Buttons
```html
<button onclick="editor.flipObject('horizontal')">
    <i class="fas fa-arrows-alt-h"></i> Flip H
</button>
<button onclick="editor.flipObject('vertical')">
    <i class="fas fa-arrows-alt-v"></i> Flip V
</button>
<button onclick="editor.duplicateObject()">
    <i class="fas fa-copy"></i> Duplicate
</button>
```

### Gradient Controls
```html
<input type="color" id="color1" value="#667eea">
<input type="color" id="color2" value="#764ba2">
<button onclick="editor.applyGradient('linear', 
                 document.getElementById('color1').value,
                 document.getElementById('color2').value)">
    Apply Gradient
</button>
```

## Common Workflows

### Add and Style a Shape
```javascript
editor.addShape('rectangle');
editor.selectedObject.set('fill', '#667eea');
editor.selectedObject.set('opacity', 0.8);
editor.canvas.renderAll();
```

### Apply Multiple Filters
```javascript
editor.applyFilter('brightness', 20);
editor.applyFilter('contrast', 30);
editor.applyFilter('saturation', 10);
```

### Create a Gradient Shape
```javascript
editor.addShape('circle');
editor.applyGradient('radial', '#ff6b6b', '#feca57');
```

### Duplicate and Position
```javascript
editor.duplicateObject();
editor.selectedObject.set('left', 300);
editor.selectedObject.set('top', 200);
editor.canvas.renderAll();
```

## Keyboard Shortcuts (Optional - Add These)

```javascript
document.addEventListener('keydown', (e) => {
    if (!editor.selectedObject) return;
    
    // Delete
    if (e.key === 'Delete' || e.key === 'Backspace') {
        editor.canvas.remove(editor.selectedObject);
    }
    
    // Duplicate (Ctrl+D)
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        editor.duplicateObject();
    }
    
    // Bring to front (Ctrl+])
    if (e.ctrlKey && e.key === ']') {
        editor.bringToFront();
    }
    
    // Send to back (Ctrl+[)
    if (e.ctrlKey && e.key === '[') {
        editor.sendToBack();
    }
});
```

## Tips & Tricks

### Performance
- Reset filters when not needed (they can slow rendering)
- Use lower blur values for better performance
- Limit number of objects on canvas

### Design
- Use gradients for modern, professional look
- Apply subtle filters (20-30%) for best results
- Layer shapes to create complex designs
- Use opacity for depth and layering effects

### Workflow
- Duplicate objects to maintain consistency
- Lock layers you don't want to edit
- Use layers panel to organize complex designs
- Save frequently (use existing save function)

## Files to Reference

- **canva-tools-demo.html** - See everything in action
- **CANVA_TOOLS_USAGE_GUIDE.md** - Detailed integration guide
- **BEFORE_AND_AFTER_FEATURES.md** - Complete feature list
- **test-new-canva-features.html** - Feature checklist

## Quick Test

```javascript
// Test shapes
editor.addShape('star');

// Test filters
editor.applyFilter('vintage');

// Test manipulation
editor.flipObject('horizontal');
editor.duplicateObject();

// Test gradients
editor.applyGradient('linear', '#667eea', '#764ba2');

// Test layers
console.log(editor.getLayers());
```

---

**Need Help?** Open `canva-tools-demo.html` for an interactive demo!
