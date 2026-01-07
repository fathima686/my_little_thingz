/**
 * Admin Design Editor - Fabric.js Canvas Implementation
 * Mini Canva-like editor for admin design refinement
 */

class DesignEditor {
    constructor() {
        this.canvas = null;
        this.currentOrderId = null;
        this.currentVersion = 1;
        this.currentStatus = 'submitted';
        this.isLoading = false;
        
        this.init();
    }
    
    init() {
        // Initialize Fabric.js canvas
        this.canvas = new fabric.Canvas('designCanvas', {
            backgroundColor: '#ffffff',
            selection: true,
            preserveObjectStacking: true
        });
        
        // Set canvas size
        this.canvas.setDimensions({
            width: 600,
            height: 400
        });
        
        // Add product mockup background (optional)
        this.addProductMockup();
        
        // Bind events
        this.bindEvents();
        
        // Load design from URL parameters
        this.loadFromURL();
    }
    
    addProductMockup() {
        // Add a subtle background to represent the product
        const rect = new fabric.Rect({
            left: 50,
            top: 50,
            width: 500,
            height: 300,
            fill: 'transparent',
            stroke: '#e0e0e0',
            strokeWidth: 2,
            strokeDashArray: [5, 5],
            selectable: false,
            evented: false,
            excludeFromExport: false
        });
        
        this.canvas.add(rect);
        this.canvas.sendToBack(rect);
        
        // Add mockup label
        const label = new fabric.Text('Product Design Area', {
            left: 300,
            top: 30,
            fontSize: 14,
            fill: '#999',
            fontFamily: 'Arial',
            textAlign: 'center',
            selectable: false,
            evented: false,
            excludeFromExport: false
        });
        
        this.canvas.add(label);
    }
    
    bindEvents() {
        // Tool buttons
        document.getElementById('addTextBtn').addEventListener('click', () => this.addText());
        document.getElementById('uploadImageBtn').addEventListener('click', () => this.uploadImage());
        document.getElementById('deleteObjectBtn').addEventListener('click', () => this.deleteSelected());
        document.getElementById('bringForwardBtn').addEventListener('click', () => this.bringForward());
        document.getElementById('sendBackwardBtn').addEventListener('click', () => this.sendBackward());
        document.getElementById('clearCanvasBtn').addEventListener('click', () => this.clearCanvas());
        document.getElementById('resetZoomBtn').addEventListener('click', () => this.resetZoom());
        
        // Save buttons
        document.getElementById('saveDesignBtn').addEventListener('click', () => this.saveDesign());
        document.getElementById('saveAndNotifyBtn').addEventListener('click', () => this.saveAndNotify());
        document.getElementById('previewBtn').addEventListener('click', () => this.previewDesign());
        
        // Font controls
        document.getElementById('fontFamily').addEventListener('change', () => this.updateTextProperties());
        document.getElementById('fontSize').addEventListener('input', () => this.updateFontSize());
        document.getElementById('textColor').addEventListener('change', () => this.updateTextProperties());
        
        // Image upload
        document.getElementById('imageUpload').addEventListener('change', (e) => this.handleImageUpload(e));
        
        // Canvas events
        this.canvas.on('selection:created', () => this.onObjectSelected());
        this.canvas.on('selection:updated', () => this.onObjectSelected());
        this.canvas.on('selection:cleared', () => this.onSelectionCleared());
        
        // Auto-save every 30 seconds
        setInterval(() => {
            if (!this.isLoading && this.currentOrderId) {
                this.autoSave();
            }
        }, 30000);
    }
    
    loadFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        const version = urlParams.get('version');
        
        if (orderId) {
            this.loadDesign(orderId, version);
        }
    }
    
    async loadDesign(orderId, version = null) {
        this.showLoading(true);
        this.currentOrderId = orderId;
        
        try {
            const params = new URLSearchParams({ order_id: orderId });
            if (version) params.append('version', version);
            
            const response = await fetch(`../../backend/api/admin/design-editor.php?${params}`);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to load design');
            }
            
            // Update UI with design info
            this.updateDesignInfo(data);
            
            // Load canvas data if available
            if (data.design && data.design.canvas_data) {
                const canvasData = JSON.parse(data.design.canvas_data);
                this.canvas.loadFromJSON(canvasData, () => {
                    this.canvas.renderAll();
                    this.showLoading(false);
                });
            } else {
                this.showLoading(false);
            }
            
        } catch (error) {
            console.error('Error loading design:', error);
            this.showError('Failed to load design: ' + error.message);
            this.showLoading(false);
        }
    }
    
    updateDesignInfo(data) {
        const { status, design, versions } = data;
        
        // Update order info
        document.getElementById('currentOrderId').textContent = status.order_id;
        document.getElementById('currentStatus').textContent = status.current_status;
        document.getElementById('currentStatus').className = `badge status-badge bg-${this.getStatusColor(status.current_status)}`;
        document.getElementById('currentVersion').textContent = status.current_version;
        document.getElementById('lastUpdated').textContent = new Date(status.updated_at).toLocaleString();
        
        this.currentStatus = status.current_status;
        this.currentVersion = status.current_version;
        
        // Show customer request
        if (status.customer_text || status.special_notes) {
            const requestDiv = document.getElementById('customerRequest');
            const contentDiv = document.getElementById('customerRequestContent');
            
            let content = '';
            if (status.customer_text) {
                content += `<div class="mb-2"><strong>Text:</strong> ${status.customer_text}</div>`;
            }
            if (status.preferred_color) {
                content += `<div class="mb-2"><strong>Color:</strong> <span style="background: ${status.preferred_color}; padding: 2px 8px; border-radius: 3px; color: white;">${status.preferred_color}</span></div>`;
            }
            if (status.special_notes) {
                content += `<div class="mb-2"><strong>Notes:</strong> ${status.special_notes}</div>`;
            }
            
            contentDiv.innerHTML = content;
            requestDiv.style.display = 'block';
        }
        
        // Update version history
        this.updateVersionHistory(versions);
        
        // Disable editing if locked
        if (status.current_status === 'locked_for_production') {
            this.disableEditing();
        }
    }
    
    updateVersionHistory(versions) {
        const historyDiv = document.getElementById('designHistory');
        
        if (!versions || versions.length === 0) {
            historyDiv.innerHTML = '<div class="text-center text-muted">No versions yet</div>';
            return;
        }
        
        let html = '';
        versions.forEach(version => {
            html += `
                <div class="history-item">
                    <div class="d-flex justify-content-between">
                        <strong>Version ${version.version_number}</strong>
                        <small class="text-muted">${new Date(version.created_at).toLocaleDateString()}</small>
                    </div>
                    ${version.notes ? `<div class="small text-muted mt-1">${version.notes}</div>` : ''}
                    ${version.preview_image_path ? `<div class="mt-2"><img src="../../backend/${version.preview_image_path}" class="img-thumbnail" style="max-width: 100px;"></div>` : ''}
                </div>
            `;
        });
        
        historyDiv.innerHTML = html;
    }
    
    getStatusColor(status) {
        const colors = {
            'submitted': 'secondary',
            'drafted_by_admin': 'primary',
            'changes_requested': 'warning',
            'approved_by_customer': 'success',
            'locked_for_production': 'dark'
        };
        return colors[status] || 'secondary';
    }
    
    addText() {
        const text = new fabric.IText('Click to edit text', {
            left: 200,
            top: 200,
            fontSize: parseInt(document.getElementById('fontSize').value),
            fontFamily: document.getElementById('fontFamily').value,
            fill: document.getElementById('textColor').value
        });
        
        this.canvas.add(text);
        this.canvas.setActiveObject(text);
        text.enterEditing();
    }
    
    handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        // Validate file
        if (!file.type.startsWith('image/')) {
            this.showError('Please select a valid image file');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            this.showError('Image size must be less than 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                // Scale image to fit canvas
                const maxWidth = 300;
                const maxHeight = 200;
                
                if (img.width > maxWidth || img.height > maxHeight) {
                    const scale = Math.min(maxWidth / img.width, maxHeight / img.height);
                    img.scale(scale);
                }
                
                img.set({
                    left: 150,
                    top: 150
                });
                
                this.canvas.add(img);
                this.canvas.setActiveObject(img);
            });
        };
        reader.readAsDataURL(file);
    }
    
    uploadImage() {
        document.getElementById('imageUpload').click();
    }
    
    deleteSelected() {
        const activeObjects = this.canvas.getActiveObjects();
        if (activeObjects.length > 0) {
            activeObjects.forEach(obj => {
                this.canvas.remove(obj);
            });
            this.canvas.discardActiveObject();
        }
    }
    
    bringForward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringForward(activeObject);
        }
    }
    
    sendBackward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendBackwards(activeObject);
        }
    }
    
    clearCanvas() {
        if (confirm('Are you sure you want to clear the entire canvas? This action cannot be undone.')) {
            this.canvas.clear();
            this.addProductMockup();
        }
    }
    
    resetZoom() {
        this.canvas.setZoom(1);
        this.canvas.absolutePan({ x: 0, y: 0 });
    }
    
    updateFontSize() {
        const fontSize = document.getElementById('fontSize').value;
        document.getElementById('fontSizeDisplay').textContent = fontSize + 'px';
        this.updateTextProperties();
    }
    
    updateTextProperties() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'i-text') {
            activeObject.set({
                fontSize: parseInt(document.getElementById('fontSize').value),
                fontFamily: document.getElementById('fontFamily').value,
                fill: document.getElementById('textColor').value
            });
            this.canvas.renderAll();
        }
    }
    
    onObjectSelected() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject && activeObject.type === 'i-text') {
            // Update font controls with selected text properties
            document.getElementById('fontSize').value = activeObject.fontSize;
            document.getElementById('fontSizeDisplay').textContent = activeObject.fontSize + 'px';
            document.getElementById('fontFamily').value = activeObject.fontFamily;
            document.getElementById('textColor').value = activeObject.fill;
        }
    }
    
    onSelectionCleared() {
        // Reset font controls to defaults
        document.getElementById('fontSize').value = 24;
        document.getElementById('fontSizeDisplay').textContent = '24px';
        document.getElementById('fontFamily').value = 'Arial';
        document.getElementById('textColor').value = '#000000';
    }
    
    previewDesign() {
        // Generate preview in a new window
        const dataURL = this.canvas.toDataURL({
            format: 'png',
            quality: 1.0
        });
        
        const previewWindow = window.open('', '_blank');
        previewWindow.document.write(`
            <html>
                <head><title>Design Preview</title></head>
                <body style="margin: 0; padding: 20px; background: #f5f5f5; text-align: center;">
                    <h3>Design Preview - Order #${this.currentOrderId}</h3>
                    <img src="${dataURL}" style="max-width: 100%; border: 1px solid #ddd; background: white;">
                </body>
            </html>
        `);
    }
    
    async saveDesign(notify = false) {
        if (!this.currentOrderId) {
            this.showError('No order selected');
            return;
        }
        
        if (this.currentStatus === 'locked_for_production') {
            this.showError('Cannot edit locked design');
            return;
        }
        
        this.showLoading(true);
        
        try {
            // Get canvas data
            const canvasData = this.canvas.toJSON();
            
            // Generate preview image
            const previewImage = this.canvas.toDataURL({
                format: 'png',
                quality: 0.8
            });
            
            const notes = document.getElementById('designNotes').value;
            
            const response = await fetch('../../backend/api/admin/design-editor.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: this.currentOrderId,
                    canvas_data: canvasData,
                    preview_image: previewImage,
                    notes: notes
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to save design');
            }
            
            this.currentVersion = data.version;
            document.getElementById('currentVersion').textContent = data.version;
            document.getElementById('designNotes').value = '';
            
            this.showSuccess('Design saved successfully as version ' + data.version);
            
            // Reload design info to update history
            this.loadDesign(this.currentOrderId);
            
            if (notify) {
                // Update status to notify customer
                await this.updateStatus('drafted_by_admin');
            }
            
        } catch (error) {
            console.error('Error saving design:', error);
            this.showError('Failed to save design: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    async saveAndNotify() {
        await this.saveDesign(true);
    }
    
    async autoSave() {
        if (this.currentStatus === 'locked_for_production') return;
        
        try {
            const canvasData = this.canvas.toJSON();
            
            // Save to localStorage as backup
            localStorage.setItem(`design_backup_${this.currentOrderId}`, JSON.stringify({
                canvasData: canvasData,
                timestamp: Date.now()
            }));
            
            console.log('Auto-saved design backup');
            
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }
    
    async updateStatus(newStatus) {
        try {
            const response = await fetch('../../backend/api/admin/design-editor.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: this.currentOrderId,
                    status: newStatus,
                    admin_notes: 'Design updated by admin'
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to update status');
            }
            
            this.currentStatus = newStatus;
            document.getElementById('currentStatus').textContent = newStatus;
            document.getElementById('currentStatus').className = `badge status-badge bg-${this.getStatusColor(newStatus)}`;
            
        } catch (error) {
            console.error('Error updating status:', error);
            this.showError('Failed to update status: ' + error.message);
        }
    }
    
    disableEditing() {
        // Disable all editing tools
        const tools = document.querySelectorAll('.tool-button, #saveDesignBtn, #saveAndNotifyBtn');
        tools.forEach(tool => {
            tool.disabled = true;
            tool.classList.add('disabled');
        });
        
        // Make canvas non-interactive
        this.canvas.selection = false;
        this.canvas.forEachObject(obj => {
            obj.selectable = false;
            obj.evented = false;
        });
        
        this.showError('Design is locked for production and cannot be edited');
    }
    
    showLoading(show) {
        this.isLoading = show;
        document.getElementById('canvasLoading').style.display = show ? 'flex' : 'none';
    }
    
    showError(message) {
        // Simple alert for now - could be replaced with a toast notification
        alert('Error: ' + message);
    }
    
    showSuccess(message) {
        // Simple alert for now - could be replaced with a toast notification
        alert('Success: ' + message);
    }
}

// Initialize the design editor when page loads
document.addEventListener('DOMContentLoaded', () => {
    new DesignEditor();
});