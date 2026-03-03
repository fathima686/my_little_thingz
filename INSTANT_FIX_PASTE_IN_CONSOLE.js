/**
 * ⚡ INSTANT FIX - Copy and paste this ENTIRE file into browser console (F12)
 * This will make the Remove Background button work immediately
 */

console.log('%c⚡ INSTANT FIX: Background Remover', 'background: #6366f1; color: white; padding: 10px; font-size: 16px; font-weight: bold;');

// Find or create editor instance
let editor = window.editor || window.designEditor;

if (!editor) {
    console.error('❌ Editor not found! Make sure you are on the design editor page.');
    alert('Please refresh the page and try again.');
} else {
    console.log('✅ Editor found');
    
    // Add hideLoading function if missing
    if (!editor.hideLoading) {
        editor.hideLoading = function() {
            this.showCanvasLoading(false);
        };
        console.log('✅ Added hideLoading function');
    }
    
    // Ensure removeBackground function exists
    if (!editor.removeBackground) {
        console.log('⚠️ removeBackground function not found, creating it...');
        
        editor.removeBackground = async function() {
            console.log('🎨 Remove background started');
            
            if (!this.selectedObject || this.selectedObject.type !== 'image') {
                alert('⚠️ Please select an image first!\n\n1. Click on an image in the canvas\n2. Then click Remove Background');
                return;
            }
            
            try {
                this.showCanvasLoading(true);
                
                // Get the image as base64
                const imageElement = this.selectedObject.getElement();
                const canvas = document.createElement('canvas');
                canvas.width = imageElement.naturalWidth || imageElement.width;
                canvas.height = imageElement.naturalHeight || imageElement.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imageElement, 0, 0);
                const imageBase64 = canvas.toDataURL('image/png');
                
                console.log('📤 Calling API...');
                
                // Call background removal API
                const formData = new FormData();
                formData.append('image_base64', imageBase64);
                
                const response = await fetch('../../backend/api/admin/remove-background.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                console.log('📥 API response:', result);
                
                if (result.success) {
                    // Replace image with background-removed version
                    const self = this;
                    fabric.Image.fromURL(result.image, function(img) {
                        const left = self.selectedObject.left;
                        const top = self.selectedObject.top;
                        const scaleX = self.selectedObject.scaleX;
                        const scaleY = self.selectedObject.scaleY;
                        const angle = self.selectedObject.angle;
                        
                        self.canvas.remove(self.selectedObject);
                        
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
                        alert('✅ Background removed successfully!');
                        console.log('✅ Background removed successfully!');
                    }, { crossOrigin: 'anonymous' });
                } else if (result.fallback) {
                    // Use client-side processing
                    this.showCanvasLoading(false);
                    console.log('⚠️ API not configured, using client-side processing');
                    this.removeBackgroundClientSide();
                } else {
                    throw new Error(result.error || 'Failed to remove background');
                }
                
            } catch (error) {
                console.error('❌ Error:', error);
                this.showCanvasLoading(false);
                alert('❌ Error: ' + error.message);
            }
        };
        
        // Add client-side fallback
        editor.removeBackgroundClientSide = function() {
            if (!this.selectedObject || this.selectedObject.type !== 'image') return;
            
            try {
                this.showCanvasLoading(true);
                
                const imageElement = this.selectedObject.getElement();
                const canvas = document.createElement('canvas');
                canvas.width = imageElement.naturalWidth || imageElement.width;
                canvas.height = imageElement.naturalHeight || imageElement.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(imageElement, 0, 0);
                
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                
                const threshold = 240;
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];
                    
                    if (r > threshold && g > threshold && b > threshold) {
                        data[i + 3] = 0;
                    }
                }
                
                ctx.putImageData(imageData, 0, 0);
                const processedImage = canvas.toDataURL('image/png');
                
                const self = this;
                fabric.Image.fromURL(processedImage, function(img) {
                    const left = self.selectedObject.left;
                    const top = self.selectedObject.top;
                    const scaleX = self.selectedObject.scaleX;
                    const scaleY = self.selectedObject.scaleY;
                    const angle = self.selectedObject.angle;
                    
                    self.canvas.remove(self.selectedObject);
                    
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
                    alert('✅ Background removed (basic processing)');
                    console.log('✅ Background removed (client-side)');
                }, { crossOrigin: 'anonymous' });
                
            } catch (error) {
                console.error('❌ Client-side error:', error);
                this.showCanvasLoading(false);
                alert('❌ Error: ' + error.message);
            }
        };
        
        console.log('✅ Created removeBackground functions');
    }
    
    // Attach button click handler
    const btn = document.getElementById('removeBackgroundBtn');
    
    if (!btn) {
        console.error('❌ Button not found!');
        alert('Button not found. Make sure you have selected an image.');
    } else {
        console.log('✅ Button found');
        
        // Remove old listeners and attach new one
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        
        newBtn.addEventListener('click', function() {
            console.log('🖱️ Button clicked!');
            editor.removeBackground();
        });
        
        console.log('✅ Button click handler attached');
    }
    
    // Store editor globally
    window.editor = editor;
    
    console.log('\n%c✅ FIX COMPLETE!', 'background: #10b981; color: white; padding: 10px; font-size: 14px; font-weight: bold;');
    console.log('\n📝 How to use:');
    console.log('1. Upload an image (click "Add Image")');
    console.log('2. Click on the image to select it');
    console.log('3. Click "Remove Background" button');
    console.log('4. Wait a few seconds');
    console.log('5. Done! ✨');
    
    alert('✅ Background Remover is now fixed and ready to use!\n\n1. Upload an image\n2. Select it\n3. Click "Remove Background"');
}
