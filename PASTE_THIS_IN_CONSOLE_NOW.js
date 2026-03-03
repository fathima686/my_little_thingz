// ⚡ COPY THIS ENTIRE FILE AND PASTE IN BROWSER CONSOLE (F12)
// This will fix the background remover INSTANTLY

(function(){
    console.log('%c⚡ FIXING BACKGROUND REMOVER...', 'background: #6366f1; color: white; padding: 10px; font-size: 16px; font-weight: bold;');
    
    let editor = window.editor || window.designEditor;
    
    if (!editor) {
        alert('❌ Editor not found! Please refresh the page and try again.');
        return;
    }
    
    console.log('✅ Editor found');
    
    // Override removeBackground function with working version
    editor.removeBackground = async function() {
        console.log('🎨 Remove background started');
        
        if (!this.selectedObject || this.selectedObject.type !== 'image') {
            alert('⚠️ Please select an image first!\n\n1. Click on an image in the canvas\n2. Then click Remove Background');
            return;
        }
        
        try {
            this.showCanvasLoading(true);
            console.log('📤 Processing image...');
            
            // Get the image as base64
            const imageElement = this.selectedObject.getElement();
            const canvas = document.createElement('canvas');
            canvas.width = imageElement.naturalWidth || imageElement.width;
            canvas.height = imageElement.naturalHeight || imageElement.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(imageElement, 0, 0);
            
            console.log('✅ Image extracted, removing background...');
            
            // Client-side background removal (simple white background removal)
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            
            // Remove white/light backgrounds
            const threshold = 240;
            let pixelsChanged = 0;
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                
                // If pixel is close to white, make it transparent
                if (r > threshold && g > threshold && b > threshold) {
                    data[i + 3] = 0; // Set alpha to 0 (transparent)
                    pixelsChanged++;
                }
            }
            
            ctx.putImageData(imageData, 0, 0);
            const processedImage = canvas.toDataURL('image/png');
            
            console.log(`✅ Background removed! Changed ${pixelsChanged} pixels`);
            
            // Replace the image in canvas
            const self = this;
            fabric.Image.fromURL(processedImage, function(img) {
                // Preserve original position and properties
                const left = self.selectedObject.left;
                const top = self.selectedObject.top;
                const scaleX = self.selectedObject.scaleX;
                const scaleY = self.selectedObject.scaleY;
                const angle = self.selectedObject.angle;
                
                // Remove old image
                self.canvas.remove(self.selectedObject);
                
                // Add new image with same properties
                img.set({
                    left: left,
                    top: top,
                    scaleX: scaleX,
                    scaleY: scaleY,
                    angle: angle
                });
                
                self.canvas.add(img);
                self.canvas.setActiveObject(img);
                self.canvas.renderAll();
                
                self.selectedObject = img;
                self.showCanvasLoading(false);
                
                console.log('✅ Done!');
                alert('✅ Background removed successfully!\n\nNote: This removes white/light backgrounds.\nFor better results, configure remove.bg API key.');
            }, { crossOrigin: 'anonymous' });
            
        } catch (error) {
            console.error('❌ Error:', error);
            this.showCanvasLoading(false);
            alert('❌ Error: ' + error.message);
        }
    };
    
    // Ensure hideLoading exists
    if (!editor.hideLoading) {
        editor.hideLoading = function() {
            this.showCanvasLoading(false);
        };
    }
    
    // Re-bind the button
    const btn = document.getElementById('removeBackgroundBtn');
    if (btn) {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.onclick = function() {
            console.log('🖱️ Button clicked!');
            editor.removeBackground();
        };
        console.log('✅ Button re-bound');
    } else {
        console.log('⚠️ Button not found - make sure an image is selected');
    }
    
    window.editor = editor;
    
    console.log('%c✅ FIX COMPLETE!', 'background: #10b981; color: white; padding: 10px; font-size: 14px; font-weight: bold;');
    console.log('\n📝 How to use:');
    console.log('1. Upload an image (click "Add Image")');
    console.log('2. Click on the image to select it');
    console.log('3. Click "Remove Background" button');
    console.log('4. Wait a few seconds');
    console.log('5. Done! ✨');
    
    alert('✅ Background Remover is now FIXED!\n\n📝 How to use:\n1. Upload an image\n2. Select it (click on it)\n3. Click "Remove Background"\n4. Wait 2-5 seconds\n5. Done! ✨\n\nNote: Removes white/light backgrounds');
})();
