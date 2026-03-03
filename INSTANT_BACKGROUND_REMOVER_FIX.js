// ⚡ INSTANT FIX - Copy this ENTIRE code and paste in browser console (F12)
// This will fix the background remover immediately without needing to refresh

(function() {
    console.log('%c⚡ INSTANT BACKGROUND REMOVER FIX', 'background: #ff6b6b; color: white; padding: 10px; font-size: 16px; font-weight: bold;');
    
    let editor = window.editor || window.designEditor;
    
    if (!editor) {
        alert('❌ Editor not found! Please make sure you are on the design editor page.');
        return;
    }
    
    console.log('✅ Editor found, applying fix...');
    
    // Override the removeBackground function with the corrected version
    editor.removeBackground = async function() {
        console.log('🎨 Remove background started (FIXED VERSION)');
        
        if (!this.selectedObject || this.selectedObject.type !== 'image') {
            alert('⚠️ Please select an image first!\n\n1. Click on an image in the canvas\n2. Then click Remove Background');
            return;
        }
        
        try {
            this.showCanvasLoading(true);
            console.log('📤 Processing image with FIXED encoding...');
            
            // Get the image as base64 (FIXED VERSION)
            const imageElement = this.selectedObject.getElement();
            const canvas = document.createElement('canvas');
            canvas.width = imageElement.naturalWidth || imageElement.width;
            canvas.height = imageElement.naturalHeight || imageElement.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(imageElement, 0, 0);
            
            // Get data URL and remove prefix (THIS IS THE FIX!)
            const imageDataUrl = canvas.toDataURL('image/png');
            const imageBase64 = imageDataUrl.replace(/^data:image\/[a-z]+;base64,/, '');
            
            console.log('✅ Image converted to clean base64 (length: ' + imageBase64.length + ')');
            console.log('📤 Calling remove.bg API...');
            
            // Call background removal API
            const formData = new FormData();
            formData.append('image_base64', imageBase64);
            
            const response = await fetch('../../backend/api/admin/remove-background.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('📥 API response status:', response.status);
            
            const result = await response.json();
            console.log('📋 API result:', result);
            
            if (result.success) {
                console.log('✅ SUCCESS! Professional background removal!');
                
                // Replace image with background-removed version
                const self = this;
                fabric.Image.fromURL(result.image, function(img) {
                    // Preserve position and scale
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
                    
                    console.log('🎉 Background removed successfully!');
                    alert('🎉 SUCCESS! Background removed using professional AI!\n\nYour remove.bg API is working perfectly!');
                }, { crossOrigin: 'anonymous' });
                
            } else if (result.fallback) {
                // API key not configured - use client-side processing
                console.log('⚠️ Falling back to client-side processing');
                this.showCanvasLoading(false);
                this.removeBackgroundClientSide();
                
            } else {
                // API error - check what kind
                console.error('❌ API Error:', result.error);
                this.showCanvasLoading(false);
                
                if (result.error.includes('Could not identify foreground')) {
                    alert('⚠️ Image Processing Issue\n\nThe image you selected might be:\n• Too simple (try a real photo)\n• Too small\n• Doesn\'t have a clear subject\n\nTry with a photo of a person or object with a clear background.');
                } else if (result.error.includes('There was an error reading the image')) {
                    alert('❌ Image Format Error\n\nThere\'s still an issue with image encoding.\nPlease try a different image or contact support.');
                } else {
                    alert('❌ API Error: ' + result.error);
                }
            }
            
        } catch (error) {
            console.error('❌ Network/Processing Error:', error);
            this.showCanvasLoading(false);
            
            // Fallback to client-side processing
            console.log('🔄 Falling back to client-side processing due to error');
            this.removeBackgroundClientSide();
        }
    };
    
    // Ensure client-side fallback exists
    if (!editor.removeBackgroundClientSide) {
        editor.removeBackgroundClientSide = function() {
            console.log('🔧 Using client-side background removal');
            
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
                
                // Remove white/light backgrounds
                const threshold = 240;
                let pixelsChanged = 0;
                for (let i = 0; i < data.length; i += 4) {
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];
                    
                    if (r > threshold && g > threshold && b > threshold) {
                        data[i + 3] = 0; // Make transparent
                        pixelsChanged++;
                    }
                }
                
                ctx.putImageData(imageData, 0, 0);
                const processedImage = canvas.toDataURL('image/png');
                
                console.log(`🔧 Client-side processing: ${pixelsChanged} pixels made transparent`);
                
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
                    
                    console.log('✅ Client-side background removal complete');
                    alert('✅ Background removed (basic processing)\n\nNote: This removes white/light backgrounds only.\nFor better results with any background, the remove.bg API should work after this fix.');
                }, { crossOrigin: 'anonymous' });
                
            } catch (error) {
                console.error('❌ Client-side error:', error);
                this.showCanvasLoading(false);
                alert('❌ Error processing image: ' + error.message);
            }
        };
    }
    
    // Ensure hideLoading function exists
    if (!editor.hideLoading) {
        editor.hideLoading = function() {
            this.showCanvasLoading(false);
        };
    }
    
    // Re-bind the button to ensure it uses the new function
    const btn = document.getElementById('removeBackgroundBtn');
    if (btn) {
        const newBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(newBtn, btn);
        newBtn.onclick = function() {
            console.log('🖱️ Remove Background button clicked (FIXED VERSION)');
            editor.removeBackground();
        };
        console.log('✅ Button re-bound with fixed function');
    } else {
        console.log('⚠️ Button not found - make sure an image is selected first');
    }
    
    // Make editor globally accessible
    window.editor = editor;
    
    console.log('%c✅ FIX APPLIED SUCCESSFULLY!', 'background: #10b981; color: white; padding: 10px; font-size: 14px; font-weight: bold;');
    console.log('\n📝 How to test:');
    console.log('1. Upload a REAL PHOTO (person, object, etc.)');
    console.log('2. Click on the image to select it');
    console.log('3. Click "Remove Background" button');
    console.log('4. Should now work with professional AI!');
    console.log('\n💡 Avoid simple graphics - use real photos for best results');
    
    alert('✅ BACKGROUND REMOVER FIXED!\n\n📝 How to test:\n1. Upload a REAL PHOTO (not simple graphics)\n2. Select the image\n3. Click "Remove Background"\n4. Should now use professional AI!\n\n💡 The fix corrects the image encoding issue.');
    
})();