/**
 * Quick Fix for Object Control Buttons
 * Paste this in your browser console if buttons aren't working
 */

console.log('🔧 Fixing object control buttons...');

// Get the panel
const panel = document.getElementById('objectControlsPanel');

if (!panel) {
    console.error('❌ objectControlsPanel not found!');
} else {
    console.log('✓ Panel found');
    
    // Recreate the buttons with proper HTML
    panel.innerHTML = `
        <label class="property-label">Object Controls</label>
        <button class="action-button secondary" id="bringForwardBtn">
            <i class="fas fa-arrow-up"></i> Bring Forward
        </button>
        <button class="action-button secondary" id="sendBackwardBtn">
            <i class="fas fa-arrow-down"></i> Send Backward
        </button>
        <button class="action-button danger" id="deleteObjectBtn">
            <i class="fas fa-trash"></i> Delete Object
        </button>
    `;
    
    console.log('✓ Buttons recreated');
    
    // Rebind the event listeners
    const bringForwardBtn = document.getElementById('bringForwardBtn');
    const sendBackwardBtn = document.getElementById('sendBackwardBtn');
    const deleteObjectBtn = document.getElementById('deleteObjectBtn');
    
    if (bringForwardBtn) {
        bringForwardBtn.addEventListener('click', () => {
            console.log('Bring forward clicked');
            if (editor && editor.bringForward) {
                editor.bringForward();
            } else if (window.designEditor && window.designEditor.bringForward) {
                window.designEditor.bringForward();
            }
        });
        console.log('✓ Bring Forward button bound');
    }
    
    if (sendBackwardBtn) {
        sendBackwardBtn.addEventListener('click', () => {
            console.log('Send backward clicked');
            if (editor && editor.sendBackward) {
                editor.sendBackward();
            } else if (window.designEditor && window.designEditor.sendBackward) {
                window.designEditor.sendBackward();
            }
        });
        console.log('✓ Send Backward button bound');
    }
    
    if (deleteObjectBtn) {
        deleteObjectBtn.addEventListener('click', () => {
            console.log('Delete clicked');
            if (editor && editor.deleteSelected) {
                editor.deleteSelected();
            } else if (window.designEditor && window.designEditor.deleteSelected) {
                window.designEditor.deleteSelected();
            } else if (editor && editor.deleteSelectedObject) {
                editor.deleteSelectedObject();
            } else if (window.designEditor && window.designEditor.deleteSelectedObject) {
                window.designEditor.deleteSelectedObject();
            }
        });
        console.log('✓ Delete button bound');
    }
    
    console.log('✅ All buttons fixed and working!');
}
