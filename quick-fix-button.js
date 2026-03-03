/**
 * Quick Fix Script for Remove Background Button
 * 
 * Copy and paste this entire script into your browser console (F12)
 * when you're on the design editor page
 */

(function() {
    console.log('%c🔧 QUICK FIX: Remove Background Button', 'color: #6366f1; font-size: 16px; font-weight: bold;');
    console.log('Starting diagnostic and fix...\n');

    // Step 1: Find the button
    const btn = document.getElementById('removeBackgroundBtn');
    
    if (!btn) {
        console.error('❌ Button not found! Make sure you are on the design editor page.');
        return;
    }
    
    console.log('✓ Button found');

    // Step 2: Find the editor instance
    let editor = window.editor;
    
    if (!editor) {
        // Try to find it in the global scope
        for (let key in window) {
            if (window[key] && typeof window[key] === 'object' && window[key].canvas && window[key].removeBackground) {
                editor = window[key];
                window.editor = editor; // Store for future use
                break;
            }
        }
    }

    if (!editor) {
        console.error('❌ Editor instance not found!');
        console.log('The editor might not be initialized yet. Try refreshing the page.');
        return;
    }

    console.log('✓ Editor instance found');

    // Step 3: Check if removeBackground function exists
    if (typeof editor.removeBackground !== 'function') {
        console.error('❌ removeBackground function not found in editor!');
        console.log('The JavaScript file might not be loaded correctly.');
        return;
    }

    console.log('✓ removeBackground function exists');

    // Step 4: Attach the event listener
    btn.onclick = async function() {
        console.log('🎨 Remove Background button clicked!');
        
        // Check if image is selected
        if (!editor.selectedObject || editor.selectedObject.type !== 'image') {
            alert('⚠️ Please select an image first!\n\n1. Click on an image in the canvas\n2. Then click Remove Background');
            console.warn('No image selected');
            return;
        }

        console.log('✓ Image selected, starting background removal...');

        try {
            await editor.removeBackground();
            console.log('✓ Background removal completed!');
        } catch (error) {
            console.error('❌ Error during background removal:', error);
            alert('Error: ' + error.message);
        }
    };

    console.log('✓ Event listener attached successfully!');
    console.log('\n%c✅ FIX COMPLETE!', 'color: #10b981; font-size: 14px; font-weight: bold;');
    console.log('The button should now work. Try clicking it!');
    console.log('\nSteps to use:');
    console.log('1. Upload an image (Add Image button)');
    console.log('2. Click on the image to select it');
    console.log('3. Click "Remove Background" button');
    
    // Test click
    console.log('\n🧪 Testing button click...');
    setTimeout(() => {
        if (editor.selectedObject && editor.selectedObject.type === 'image') {
            console.log('✓ Image is selected, button is ready to use!');
        } else {
            console.log('ℹ️ No image selected yet. Select an image to test the button.');
        }
    }, 100);

})();
