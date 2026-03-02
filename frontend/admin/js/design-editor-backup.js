/**
 * Admin Design Editor - Canva Style Implementation
 * Enhanced version with 3-column layout and template support
 */

class DesignEditor {
    constructor() {
        this.canvas = null;
        this.currentOrderId = null;
        this.currentVersion = 1;
        this.currentStatus = 'submitted';
        this.isLoading = false;
        this.history = [];
        this.redoStack = [];
        this.isRestoring = false;
        this.maxHistory = 50;
        this.selectedObject = null;
        this.zoom = 1;
        this.templates = [];
        this.activeCategory = 'all';
        this.searchQuery = '';
        
        this.init();
    }
    
    async init() {
        try {
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
            this.initHistoryTracking();
            
            // Load templates
            await this.loadTemplates();
            
            // Load design from URL parameters
            this.loadFromURL();
            
            // Show default properties panel
            this.showDefaultState();
        } catch (error) {
            console.error('Error initializing editor:', error);
        }
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
        // Template search and filtering
        document.getElementById('templateSearch').addEventListener('input', (e) => {
            this.searchQuery = e.target.value.toLowerCase();
            this.filterTemplates();
        });
        
        // Category filters
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setActiveCategory(e.target.dataset.category);
            });
        });
        
        // Toolbar buttons
        document.getElementById('undoBtn').addEventListener('click', () => this.undo());
        document.getElementById('redoBtn').addEventListener('click', () => this.redo());
        document.getElementById('zoomInBtn').addEventListener('click', () => this.zoomIn());
        document.getElementById('zoomOutBtn').addEventListener('click', () => this.zoomOut());
        document.getElementById('previewBtn').addEventListener('click', () => this.previewDesign());
        document.getElementById('saveDesignBtn').addEventListener('click', () => this.saveDesign());
        document.getElementById('saveAndNotifyBtn').addEventListener('click', () => this.saveAndNotify());
        
        // Canvas background controls
        document.getElementById('canvasBackgroundColor').addEventListener('change', (e) => {
            this.setCanvasBackground(e.target.value);
        });
        document.getElementById('canvasBackgroundHex').addEventListener('change', (e) => {
            this.setCanvasBackground(e.target.value);
        });
        
        // Add element buttons
        document.getElementById('addTextBtn').addEventListener('click', () => this.addText());
        document.getElementById('uploadImageBtn').addEventListener('click', () => this.uploadImage());
        
        // Text properties
        this.bindTextProperties();
        
        // Image properties
        this.bindImageProperties();
        
        // Object controls
        this.bindObjectControls();
        
        // File uploads
        document.getElementById('imageUpload').addEventListener('change', (e) => {
            this.handleImageUpload(e);
        });
        document.getElementById('replaceImageUpload').addEventListener('change', (e) => {
            this.handleReplaceImage(e);
        });
        
        // Canvas events
        this.canvas.on('selection:created', (e) => this.handleObjectSelection(e));
        this.canvas.on('selection:updated', (e) => this.handleObjectSelection(e));
        this.canvas.on('selection:cleared', () => this.handleSelectionCleared());
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => this.handleKeyboardShortcuts(e));
    }
    
    bindTextProperties() {
        const fontFamily = document.getElementById('fontFamily');
        const fontSize = document.getElementById('fontSize');
        const fontSizeValue = document.getElementById('fontSizeValue');
        const textColor = document.getElementById('textColor');
        const textColorHex = document.getElementById('textColorHex');
        const boldBtn = document.getElementById('boldBtn');
        const italicBtn = document.getElementById('italicBtn');
        const underlineBtn = document.getElementById('underlineBtn');
        const deleteTextBtn = document.getElementById('deleteTextBtn');
        
        if (fontFamily) {
            fontFamily.addEventListener('change', (e) => {
                this.updateTextProperty('fontFamily', e.target.value);
            });
        }
        
        if (fontSize) {
            fontSize.addEventListener('input', (e) => {
                const value = parseInt(e.target.value);
                if (fontSizeValue) fontSizeValue.textContent = value + 'px';
                this.updateTextProperty('fontSize', value);
            });
        }
        
        if (textColor) {
            textColor.addEventListener('change', (e) => {
                if (textColorHex) textColorHex.value = e.target.value;
                this.updateTextProperty('fill', e.target.value);
                this.updateColorPreview(textColor.parentElement.querySelector('.color-preview'), e.target.value);
            });
        }
        
        if (textColorHex) {
            textColorHex.addEventListener('change', (e) => {
                if (textColor) textColor.value = e.target.value;
                this.updateTextProperty('fill', e.target.value);
                this.updateColorPreview(textColor.parentElement.querySelector('.color-preview'), e.target.value);
            });
        }
        
        if (boldBtn) {
            boldBtn.addEventListener('click', () => this.toggleTextStyle('fontWeight', 'bold', 'normal', boldBtn));
        }
        
        if (italicBtn) {
            italicBtn.addEventListener('click', () => this.toggleTextStyle('fontStyle', 'italic', 'normal', italicBtn));
        }
        
        if (underlineBtn) {
            underlineBtn.addEventListener('click', () => this.toggleTextDecoration(underlineBtn));
        }
        
        if (deleteTextBtn) {
            deleteTextBtn.addEventListener('click', () => this.deleteSelectedObject());
        }
    }
    
    bindImageProperties() {
        const replaceImageBtn = document.getElementById('replaceImageBtn');
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        
        if (replaceImageBtn) {
            replaceImageBtn.addEventListener('click', () => {
                document.getElementById('replaceImageUpload').click();
            });
        }
        
        if (deleteImageBtn) {
            deleteImageBtn.addEventListener('click', () => this.deleteSelectedObject());
        }
    }
    
    bindObjectControls() {
        const bringForwardBtn = document.getElementById('bringForwardBtn');
        const sendBackwardBtn = document.getElementById('sendBackwardBtn');
        const deleteObjectBtn = document.getElementById('deleteObjectBtn');
        
        if (bringForwardBtn) {
            bringForwardBtn.addEventListener('click', () => this.bringForward());
        }
        
        if (sendBackwardBtn) {
            sendBackwardBtn.addEventListener('click', () => this.sendBackward());
        }
        
        if (deleteObjectBtn) {
            deleteObjectBtn.addEventListener('click', () => this.deleteSelected());
        }
    }
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

        // Keyboard shortcuts for undo/redo
        document.addEventListener('keydown', (e) => {
            const isCtrlOrCmd = e.ctrlKey || e.metaKey;
            if (!isCtrlOrCmd) return;
            if (e.key === 'z' || e.key === 'Z') {
                e.preventDefault();
                if (e.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
            }
            if (e.key === 'y' || e.key === 'Y') {
                e.preventDefault();
                this.redo();
            }
        });
    }

    initHistoryTracking() {
        // initial snapshot (blank canvas with mockup)
        this.saveHistorySnapshot();

        const recordChange = () => {
            if (this.isRestoring) return;
            this.saveHistorySnapshot();
            this.redoStack = [];
        };

        this.canvas.on('object:added', recordChange);
        this.canvas.on('object:modified', recordChange);
        this.canvas.on('object:removed', recordChange);
        this.canvas.on('text:changed', recordChange);
    }

    saveHistorySnapshot() {
        try {
            const json = this.canvas.toJSON();
            const serialized = JSON.stringify(json);
            // Avoid duplicate consecutive states
            if (this.history.length > 0 && this.history[this.history.length - 1] === serialized) {
                return;
            }
            this.history.push(serialized);
            if (this.history.length > this.maxHistory) {
                this.history.shift();
            }
        } catch (e) {
            console.error('History snapshot failed:', e);
        }
    }

    undo() {
        if (this.history.length <= 1) {
            return;
        }
        const current = this.history.pop();
        this.redoStack.push(current);
        const previous = this.history[this.history.length - 1];
        this.restoreFromSerialized(previous);
    }

    redo() {
        if (this.redoStack.length === 0) {
            return;
        }
        const next = this.redoStack.pop();
        this.history.push(next);
        this.restoreFromSerialized(next);
    }

    restoreFromSerialized(serialized) {
        if (!serialized) return;
        this.isRestoring = true;
        try {
            const json = JSON.parse(serialized);
            this.canvas.loadFromJSON(json, () => {
                this.canvas.renderAll();
                this.isRestoring = false;
            });
        } catch (e) {
            console.error('Error restoring history state:', e);
            this.isRestoring = false;
        }
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
            this.saveHistorySnapshot();
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
    
    // ==================== TEMPLATE MANAGEMENT ====================
    
    async loadTemplates() {
        try {
            this.showLoading(true);
            
            // Try to load templates from API
            try {
                const response = await fetch('../../backend/api/admin/template-gallery.php?action=list');
                const data = await response.json();
                
                if (data.status === 'success') {
                    this.templates = data.data.templates || [];
                } else {
                    throw new Error('API returned error');
                }
            } catch (error) {
                console.log('API not available, using sample templates');
                this.templates = this.getSampleTemplates();
            }
            
            this.renderTemplates();
        } catch (error) {
            console.error('Error loading templates:', error);
            this.templates = this.getSampleTemplates();
            this.renderTemplates();
        } finally {
            this.showLoading(false);
        }
    }
    
    getSampleTemplates() {
        return [
            {
                id: 'sample-1',
                name: 'Birthday Card',
                category: 'birthday',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZlNmNjIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZmlsbD0iIzMzMzMzMyIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkhhcHB5IEJpcnRoZGF5ITwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffe6cc' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy Birthday!',
                            x: 200,
                            y: 150,
                            fontSize: 36,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff6b6b'
                        }
                    ]
                }
            },
            {
                id: 'sample-2',
                name: 'Name Frame',
                category: 'name-frame',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTNmMmZkIi8+PHJlY3QgeD0iMjAiIHk9IjIwIiB3aWR0aD0iMTYwIiBoZWlnaHQ9IjgwIiBmaWxsPSJub25lIiBzdHJva2U9IiM2MzY2ZjEiIHN0cm9rZS13aWR0aD0iMyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM2MzY2ZjEiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Zb3VyIE5hbWU8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#e3f2fd' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 100,
                            y: 100,
                            width: 400,
                            height: 200,
                            fill: 'transparent',
                            stroke: '#6366f1',
                            strokeWidth: 6
                        },
                        {
                            type: 'text',
                            content: 'Your Name',
                            x: 250,
                            y: 180,
                            fontSize: 28,
                            fontFamily: 'Arial',
                            fill: '#6366f1'
                        }
                    ]
                }
            },
            {
                id: 'sample-3',
                name: 'Inspirational Quote',
                category: 'quotes',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImdyYWQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiM4YjVjZjYiLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM2MzY2ZjEiLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyYWQpIi8+PHRleHQgeD0iNTAlIiB5PSI0MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj4iQmUgdGhlIGNoYW5nZSI8L3RleHQ+PHRleHQgeD0iNTAlIiB5PSI2NSUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj55b3Ugd2lzaCB0byBzZWU8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#8b5cf6' },
                    elements: [
                        {
                            type: 'text',
                            content: '"Be the change you wish to see"',
                            x: 300,
                            y: 180,
                            fontSize: 24,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#ffffff',
                            textAlign: 'center'
                        }
                    ]
                }
            }
        ];
    }
    
    renderTemplates() {
        const grid = document.getElementById('templatesGrid');
        const emptyState = document.getElementById('templatesEmptyState');
        
        const filteredTemplates = this.getFilteredTemplates();
        
        if (filteredTemplates.length === 0) {
            grid.innerHTML = '';
            grid.appendChild(emptyState);
            return;
        }
        
        emptyState.style.display = 'none';
        
        const templatesHTML = filteredTemplates.map(template => `
            <div class="template-card" data-template-id="${template.id}">
                <div class="template-thumbnail">
                    ${template.thumbnail ? 
                        `<img src="${template.thumbnail}" alt="${template.name}">` : 
                        `<i class="fas fa-image"></i><br>No Preview`
                    }
                </div>
                <div class="template-info">
                    <h4 class="template-name">${template.name}</h4>
                    <p class="template-category">${this.getCategoryDisplayName(template.category)}</p>
                </div>
            </div>
        `).join('');
        
        grid.innerHTML = templatesHTML;
        
        // Bind template click events
        grid.querySelectorAll('.template-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const templateId = e.currentTarget.dataset.templateId;
                this.loadTemplate(templateId);
            });
        });
    }
    
    getFilteredTemplates() {
        return this.templates.filter(template => {
            const matchesCategory = this.activeCategory === 'all' || template.category === this.activeCategory;
            const matchesSearch = !this.searchQuery || 
                template.name.toLowerCase().includes(this.searchQuery) ||
                template.category.toLowerCase().includes(this.searchQuery);
            
            return matchesCategory && matchesSearch;
        });
    }
    
    getCategoryDisplayName(category) {
        const categoryNames = {
            'birthday': 'Birthday',
            'name-frame': 'Name Frame',
            'quotes': 'Quotes',
            'anniversary': 'Anniversary'
        };
        return categoryNames[category] || category;
    }
    
    setActiveCategory(category) {
        this.activeCategory = category;
        
        // Update UI
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.category === category);
        });
        
        this.filterTemplates();
    }
    
    filterTemplates() {
        this.renderTemplates();
    }
    
    async loadTemplate(templateId) {
        try {
            this.showCanvasLoading(true);
            
            const template = this.templates.find(t => t.id === templateId);
            if (!template) {
                throw new Error('Template not found');
            }
            
            // Clear canvas
            this.canvas.clear();
            
            // Load template data
            await this.loadTemplateData(template.template_data);
            
            // Update properties panel
            this.showDefaultState();
            
            this.canvas.renderAll();
            this.saveHistorySnapshot();
            
        } catch (error) {
            console.error('Error loading template:', error);
            alert('Failed to load template: ' + error.message);
        } finally {
            this.showCanvasLoading(false);
        }
    }
    
    async loadTemplateData(templateData) {
        if (!templateData) return;
        
        // Set canvas background
        if (templateData.canvas && templateData.canvas.backgroundColor) {
            this.canvas.setBackgroundColor(templateData.canvas.backgroundColor, this.canvas.renderAll.bind(this.canvas));
            this.updateBackgroundColorUI(templateData.canvas.backgroundColor);
        }
        
        // Add elements
        if (templateData.elements) {
            for (const element of templateData.elements) {
                await this.addElementToCanvas(element);
            }
        }
    }
    
    async addElementToCanvas(element) {
        let fabricObject;
        
        switch (element.type) {
            case 'text':
                fabricObject = new fabric.Textbox(element.content || 'Text', {
                    left: element.x || 100,
                    top: element.y || 100,
                    fontSize: element.fontSize || 24,
                    fontFamily: element.fontFamily || 'Arial',
                    fontWeight: element.fontWeight || 'normal',
                    fontStyle: element.fontStyle || 'normal',
                    fill: element.fill || '#000000',
                    textAlign: element.textAlign || 'left',
                    width: element.width || 200
                });
                break;
                
            case 'shape':
                if (element.shape === 'rectangle') {
                    fabricObject = new fabric.Rect({
                        left: element.x || 100,
                        top: element.y || 100,
                        width: element.width || 100,
                        height: element.height || 100,
                        fill: element.fill || '#cccccc',
                        stroke: element.stroke || '',
                        strokeWidth: element.strokeWidth || 0,
                        rx: element.rx || 0,
                        ry: element.ry || 0
                    });
                } else if (element.shape === 'circle') {
                    fabricObject = new fabric.Circle({
                        left: element.x || 100,
                        top: element.y || 100,
                        radius: (element.width || 100) / 2,
                        fill: element.fill || '#cccccc',
                        stroke: element.stroke || '',
                        strokeWidth: element.strokeWidth || 0
                    });
                }
                break;
                
            case 'image':
                if (element.src) {
                    try {
                        fabricObject = await new Promise((resolve, reject) => {
                            fabric.Image.fromURL(element.src, (img) => {
                                if (img) {
                                    img.set({
                                        left: element.x || 100,
                                        top: element.y || 100,
                                        scaleX: element.scaleX || 1,
                                        scaleY: element.scaleY || 1
                                    });
                                    resolve(img);
                                } else {
                                    reject(new Error('Failed to load image'));
                                }
                            }, { crossOrigin: 'anonymous' });
                        });
                    } catch (error) {
                        console.error('Error loading template image:', error);
                    }
                }
                break;
        }
        
        if (fabricObject) {
            this.canvas.add(fabricObject);
        }
    }
    
    // ==================== OBJECT SELECTION & PROPERTIES ====================
    
    handleObjectSelection(e) {
        const activeObject = e.selected?.[0] || e.target;
        this.selectedObject = activeObject;
        
        if (activeObject) {
            this.showPropertiesForObject(activeObject);
        }
    }
    
    handleSelectionCleared() {
        this.selectedObject = null;
        this.showDefaultState();
    }
    
    showPropertiesForObject(obj) {
        // Hide all property panels
        this.hideAllPropertyPanels();
        
        // Update properties title
        const title = document.getElementById('propertiesTitle');
        
        if (obj.type === 'textbox' || obj.type === 'i-text') {
            title.textContent = 'Text Properties';
            this.showTextProperties(obj);
        } else if (obj.type === 'image') {
            title.textContent = 'Image Properties';
            this.showImageProperties(obj);
        } else {
            title.textContent = 'Object Properties';
            this.showObjectControls();
        }
    }
    
    hideAllPropertyPanels() {
        const panels = ['noSelectionPanel', 'textPropertiesPanel', 'imagePropertiesPanel'];
        panels.forEach(panelId => {
            const panel = document.getElementById(panelId);
            if (panel) panel.style.display = 'none';
        });
        
        const objectControls = document.getElementById('objectControlsPanel');
        if (objectControls) objectControls.style.display = 'none';
    }
    
    showDefaultState() {
        this.hideAllPropertyPanels();
        document.getElementById('propertiesTitle').textContent = 'Properties';
        const defaultPanel = document.getElementById('noSelectionPanel');
        if (defaultPanel) defaultPanel.style.display = 'block';
    }
    
    showTextProperties(textObj) {
        const panel = document.getElementById('textPropertiesPanel');
        if (!panel) return;
        
        panel.style.display = 'block';
        
        // Update controls with current values
        const fontFamily = document.getElementById('fontFamily');
        const fontSize = document.getElementById('fontSize');
        const fontSizeValue = document.getElementById('fontSizeValue');
        const textColor = document.getElementById('textColor');
        const textColorHex = document.getElementById('textColorHex');
        const boldBtn = document.getElementById('boldBtn');
        const italicBtn = document.getElementById('italicBtn');
        const underlineBtn = document.getElementById('underlineBtn');
        
        if (fontFamily) fontFamily.value = textObj.fontFamily || 'Arial';
        if (fontSize) fontSize.value = textObj.fontSize || 24;
        if (fontSizeValue) fontSizeValue.textContent = (textObj.fontSize || 24) + 'px';
        if (textColor) textColor.value = textObj.fill || '#000000';
        if (textColorHex) textColorHex.value = textObj.fill || '#000000';
        
        // Update style buttons
        if (boldBtn) boldBtn.classList.toggle('active', textObj.fontWeight === 'bold');
        if (italicBtn) italicBtn.classList.toggle('active', textObj.fontStyle === 'italic');
        if (underlineBtn) underlineBtn.classList.toggle('active', textObj.textDecoration === 'underline');
        
        // Update color preview
        this.updateColorPreview(document.querySelector('#textPropertiesPanel .color-preview'), textObj.fill || '#000000');
        
        // Show object controls too
        this.showObjectControls();
    }
    
    showImageProperties(imageObj) {
        const panel = document.getElementById('imagePropertiesPanel');
        if (!panel) return;
        
        panel.style.display = 'block';
        
        // Show object controls too
        this.showObjectControls();
    }
    
    showObjectControls() {
        const panel = document.getElementById('objectControlsPanel');
        if (panel) panel.style.display = 'block';
    }
    
    updateColorPreview(previewElement, color) {
        if (previewElement) {
            previewElement.style.background = color;
        }
    }
    
    // ==================== PROPERTY UPDATES ====================
    
    updateTextProperty(property, value) {
        if (!this.selectedObject || (this.selectedObject.type !== 'textbox' && this.selectedObject.type !== 'i-text')) return;
        
        this.selectedObject.set(property, value);
        this.canvas.renderAll();
    }
    
    toggleTextStyle(property, activeValue, inactiveValue, button) {
        if (!this.selectedObject) return;
        
        const currentValue = this.selectedObject.get(property);
        const newValue = currentValue === activeValue ? inactiveValue : activeValue;
        
        this.updateTextProperty(property, newValue);
        button.classList.toggle('active', newValue === activeValue);
    }
    
    toggleTextDecoration(button) {
        if (!this.selectedObject) return;
        
        const currentDecoration = this.selectedObject.get('textDecoration') || '';
        const newDecoration = currentDecoration === 'underline' ? '' : 'underline';
        
        this.updateTextProperty('textDecoration', newDecoration);
        button.classList.toggle('active', newDecoration === 'underline');
    }
    
    // ==================== CANVAS BACKGROUND ====================
    
    setCanvasBackground(color) {
        this.canvas.setBackgroundColor(color, this.canvas.renderAll.bind(this.canvas));
        this.updateBackgroundColorUI(color);
    }
    
    updateBackgroundColorUI(color) {
        const colorInput = document.getElementById('canvasBackgroundColor');
        const hexInput = document.getElementById('canvasBackgroundHex');
        const preview = document.querySelector('#canvasBackgroundColor').parentElement.querySelector('.color-preview');
        
        if (colorInput) colorInput.value = color;
        if (hexInput) hexInput.value = color;
        this.updateColorPreview(preview, color);
    }
    
    // ==================== ZOOM CONTROLS ====================
    
    zoomIn() {
        const newZoom = Math.min(this.zoom * 1.2, 3);
        this.setZoom(newZoom);
    }
    
    zoomOut() {
        const newZoom = Math.max(this.zoom / 1.2, 0.1);
        this.setZoom(newZoom);
    }
    
    setZoom(zoom) {
        this.zoom = zoom;
        this.canvas.setZoom(zoom);
        this.canvas.renderAll();
        
        // Update zoom display
        const zoomDisplay = document.getElementById('zoomDisplay');
        if (zoomDisplay) {
            zoomDisplay.textContent = Math.round(zoom * 100) + '%';
        }
    }
    
    // ==================== ADD ELEMENTS ====================
    
    addText() {
        const text = new fabric.Textbox('New Text', {
            left: 100,
            top: 100,
            fontSize: 24,
            fontFamily: 'Arial',
            fill: '#000000',
            width: 200
        });
        
        this.canvas.add(text);
        this.canvas.setActiveObject(text);
        text.enterEditing();
        this.canvas.renderAll();
    }
    
    uploadImage() {
        document.getElementById('imageUpload').click();
    }
    
    handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            alert('Image size must be less than 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                // Scale image to reasonable size
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
                this.canvas.renderAll();
            }, { crossOrigin: 'anonymous' });
        };
        reader.readAsDataURL(file);
    }
    
    handleReplaceImage(event) {
        if (!this.selectedObject || this.selectedObject.type !== 'image') return;
        
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                // Preserve position and scale
                const left = this.selectedObject.left;
                const top = this.selectedObject.top;
                const scaleX = this.selectedObject.scaleX;
                const scaleY = this.selectedObject.scaleY;
                
                // Remove old image
                this.canvas.remove(this.selectedObject);
                
                // Add new image with same properties
                img.set({
                    left: left,
                    top: top,
                    scaleX: scaleX,
                    scaleY: scaleY
                });
                
                this.canvas.add(img);
                this.canvas.setActiveObject(img);
                this.canvas.renderAll();
                
                this.selectedObject = img;
                this.showImageProperties(img);
            }, { crossOrigin: 'anonymous' });
        };
        reader.readAsDataURL(file);
    }
    
    deleteSelectedObject() {
        if (!this.selectedObject) return;
        
        this.canvas.remove(this.selectedObject);
        this.canvas.renderAll();
        this.selectedObject = null;
        this.showDefaultState();
    }
    
    // ==================== KEYBOARD SHORTCUTS ====================
    
    handleKeyboardShortcuts(e) {
        const isCtrlOrCmd = e.ctrlKey || e.metaKey;
        
        if (!isCtrlOrCmd) {
            // Delete key
            if (e.key === 'Delete' && this.selectedObject) {
                e.preventDefault();
                this.deleteSelectedObject();
            }
            return;
        }
        
        // Ctrl/Cmd shortcuts
        switch (e.key.toLowerCase()) {
            case 'z':
                e.preventDefault();
                if (e.shiftKey) {
                    this.redo();
                } else {
                    this.undo();
                }
                break;
            case 'y':
                e.preventDefault();
                this.redo();
                break;
        }
    }
    
    // ==================== UTILITY FUNCTIONS ====================
    
    showLoading(show) {
        this.isLoading = show;
        // Could add loading indicator to templates grid
    }
    
    showCanvasLoading(show) {
        const overlay = document.getElementById('canvasLoading');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }
    
    // ==================== ORIGINAL FUNCTIONS PRESERVED ====================
    
    loadFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        const version = urlParams.get('version');
        
        if (orderId) {
            this.loadDesign(orderId, version);
        }
    }
    
    async loadDesig);
}ditor(); new DesignEignEditor = window.des => {
    ()d',ntentLoadeener('DOMCoListaddEventd
document.M is loadewhen DOitor ze the ediali/ Init
/    }
}
essage);
le.log(m      conso;
  e)ag alert(mess{
       ss(message)    showSucce
    
     };
message)rror(console.e        sage);
r: ' + mesrt('Erro        ale
(message) {howError   
    s==
 ================ONS ==TY FUNCTIILI======= UT=========== 
    // ==  }
    === 0;
  lengthtack.his.redoSed = ttn.disabledoB rn)edoBt  if (r    
  <= 1;gth ry.len= this.histosabled Btn.dindodoBtn) u if (un
             ;
  oBtn')Id('redBytElementument.ge= doc redoBtn st     contn');
   Id('undoBElementBy.getnt= documetn oBconst und      () {
  nsyButtoeHistor
    updat }
      }
   
      ng = false;Restori  this.is         e);
 :', atestory st hingor restorirror('Errle.eso        con (e) {
         } catch    });
           te();
faultStashowDe    this.      ll;
      dObject = nuelectethis.s            ore
    estter rafr selection     // Clea           
          
       s();oryButtonsthis.updateHi        t     se;
   toring = falis.isRes  th         ll();
     .renderAvas  this.can             {
  () =>(json, SONdFromJcanvas.loa  this.       );
   izedparse(serialjson = JSON.      const y {
       true;
       ring = trsto this.isRe             
 
 urn;ed) retrializf (!se i     {
  erialized) zed(somSeriali restoreFr  
    
     }t);
alized(nexromSeristoreFhis.re        txt);
push(nes.history.   thi     k.pop();
oStac= this.redt const nex  
              urn;
 === 0) retck.length.redoSta   if (this{
     
    redo()  }
    ious);
   ized(preveFromSerialtorhis.res;
        tgth - 1]y.lens.historistory[thi this.hious =ev    const pr
    (current);pushk.Stac  this.redo   op();
   s.history.p= thinst current  co
              ;
 turngth <= 1) rehistory.lenhis.     if (to() {
   nd
    u  }
    }
    );
       e',t failed:y snapsho('Historrorole.er    cons   {
      tch (e)       } ca
 ns();ryButto.updateHisto     thises
       n statbutto/redo e undo/ Updat /             
          }
            
();shiftistory.   this.h          ) {
   .maxHistoryisth > thlengory.hist (this.   if   
          ;
        erialized)(sshs.history.pu        thi   
    
                  }eturn;
           r        ed) {
== serializength - 1] =story.l.hithis.history[this0 && h > engts.history.l (thi       if     ive states
e consecutatAvoid duplic      //  
              n);
   y(jsotringifed = JSON.srializ   const se     );
    oJSON(s.t this.canvat json =        cons try {
          ) {
 pshot(istorySna    saveH   }
    
ange);
  recordChhanged',s.on('text:chis.canva  t    hange);
  dCved', recor:remobjectnvas.on('o.ca       this
 ange);d', recordChodifieon('object:ms.canva    this.   ge);
 hanordCadded', rect:('objec.canvas.on       this
        
       };ck = [];
  s.redoSta  thi   ;
       rySnapshot()Histo.savethis           eturn;
 toring) ris.isRes     if (th> {
       e = () =dChangcoronst re   c
     storationring rek changes du trac   // Don't      
   hot();
    napsstorySeHithis.sav
        l stateiae init   // Sav
     acking() {storyTr  initHi
    
  ============) ========T (PRESERVEDY MANAGEMEN HISTOR================= // ===
   }
    l();
    renderAls.is.canva
        th    ;
          })  ;
ted = falsej.even      ob;
      ble = falsectaelebj.s       o
     bj => {Object(orEach.foashis.canv
        talse;lection = fvas.se.can        thison
s interactible canva  // Disa 
           });
   5';
       0.ty = 'e.opaci   tool.styl        ue;
 d = trisableool.d          t => {
  h(tool.forEactools        );
on-button'actibtn, .toolbar-ctorAll('.uerySeleument.qs = doct tool     constools
   all editing  // Disable      
  ting() {  disableEdi 
  
    }
   1);etZoom(   this.s     ) {
esetZoom(  
    r
    }
  ;
        }()State.showDefault      thisl();
      enderAl.canvas.r   this
         Mockup();ddProduct.a   this      lear();
   s.cthis.canva         {
    ne.'))undoe is cannot bvas? Thcanre tiar the en cleou want to sure ym('Are you if (confir
       () { clearCanvas  
    
   }          }
nderAll();
canvas.re this.         ject);
  ctiveObkward(aBacanvas.sendthis.c    
         {ect)veObj(acti if 
       ct();bjeActiveO.getcanvashis. = tactiveObjectnst    co  ) {
   kward(ndBac
    se}
    
    ;
        }nderAll()nvas.rehis.ca  t
          ject);(activeObForwardanvas.bring      this.c     ct) {
 veObje   if (actit();
     ctiveObjec.canvas.getAect = thisst activeObj    con
    ard() {bringForw   
       }
      }
    ate();
aultSthis.showDef           t
 );.renderAll(s.canvas      thi
      ect();eObjardActivdiscvas.an     this.c     });
            obj);
  .remove(nvasca  this.            obj => {
  ts.forEach(ecctiveObj      a{
      gth > 0) ts.lenbjecif (activeO    s();
    bjectActiveOnvas.gets = this.cactbje activeOnst     co   ted() {
eleteSelec 
    d    }
   ign(true);
eDessavhis.t t   awai   ) {
  AndNotify( save  async 
  
      }
         }false);
howLoading(      this.s     ly {
 inal} f    ;
    ssage)+ error.mesign: ' ave de to siledror('FaowEr     this.sh);
       ', erroresign:ing dror saverror('Ersole.    con    r) {
    ch (erro } cat  
               }
       e
       essagsuccess mdditional w ashoredirect or  Could          //     y) {
  if   if (not
                 
    Id);ertOrdcurrenthis.gn(siadDeis.lo    th   y
     pdate historinfo to uesign eload d   // R           
   
       lue = '';sEl.val) noteif (notesE   
         ignNotes');ById('desementt.getElocumen dl = notesE      constotes
      ear n    // Cl
                    ly!');
ulessf succved: 'Design satified!'  nocustomered and sign savify ? 'Dess(nots.showSuccethi           
             }
           
 ign');ave des'Failed to sor || rra.er(datnew Erro      throw        
   ) {nse.ok!respo        if (   
     
        onse.json();t respaiawta =    const da
                
            });
     payload)ify(stringy: JSON.      bod             },
         
    tion/json'e': 'applicantent-Typ     'Co         s: {
      der      hea         
 OST',d: 'P      metho    {
       ditor.php',sign-eapi/admin/deckend/./bafetch('../.it onse = awat respns         co  
                 };

        tifystomer: no_cu    notify           es,
   notes: not             age,
 iewImrevimage: pview_      pre
          canvasData),fy(ingitrON.sdata: JSs_ canva          Id,
     rentOrderthis.currder_id:            o
     ave',  action: 's          
    payload = {t cons          
        '';
      || .value )?Notes'yId('designementBgetElment. docuonst notes =           c
                 });
    : 0.5
    plier    multi       ,
     ty: 0.8quali           g',
     : 'pn      format          aURL({
canvas.toDatthis.age = wImvierenst pco          
  .toJSON();.canvassData = thisonst canva          c   
          
 ading(true);wLo   this.sho      ry {
      t
         
      };
           return       ');
tedselecder ror('No oris.showEr      th     d) {
 OrderIntis.curre if (!th{
       )  = falsegn(notifyync saveDesi as    
 }
     `);
   >
      /html    <
         </body>         
      ite;">: whnd; backgrouolid #dddrder: 1px sth: 100%; bo"max-wide=" stylaURL}${dat="<img src                 h2>
    Preview</signDeh2>      <           5f5;">
   5fd: #fr; backgrounn: cente-alig20px; textng: ; paddiargin: 0"mle=  <body sty            
  le></head>titw</n Previee>Desig><titlhead        <         <html>
   
        rite(`.wow.documentiewWind    prevk');
    n('', '_blan.opeow = windWindownst previewco  
        
              });iplier: 1
ltmu           
 1.0,ty: quali        png',
    mat: '       for    
 L({s.toDataUR this.canvaURL =onst data  cow
       new windw in aevie prnerateGe//         
) {ign(  previewDes 
     }
   ry';
 condaatus] || 'seors[stcolrn tu      re
  };     '
   ion': 'darkductr_pro  'locked_fo   ,
       success'customer': 'by_ed_rov    'app
        ning',: 'war_requested''changes      
      y', 'primary_admin':  'drafted_b
          'secondary',': 'submitted       s = {
     lorst co       contus) {
 staColor(Status
    get
    }
    tml;= hML .innerHTDivhistory        
  );
            }       `;
>
            </div   
         ''}></div>` :"th: 80px;-wid style="maxhumbnail"s="img-t}" clasth_image_pasion.previewckend/${ver="../../ba<img src-2">ss="mtcla`<div ? _image_path .previewrsionve  ${                   ''}
` :notes}</div>{version.-1">$ mtxt-mutedsmall tediv class="es ? `<otersion.n    ${v                </div>
            >
        }</smalltring()teSLocaleDa).toeated_atrsion.cr(ve${new Dated">"text-muteclass=   <small            
          rong>er}</stversion_numbn ${version.ong>Versio <str                  ">
     t-betweenstify-contenflex julass="d-     <div c           ">
    .9em;t-size: 0one; f #ee solid1pxbottom: r-x; bordedding: 8p"patyle=  <div s            tml += `
    h        {
  (version => ons.forEachsi  ver     tml = '';
  h   let
     
          }
      turn;    re       ;
 </div>'ns yetioo versted">Next-munter t"text-ceass=<div clnerHTML = 'iv.intoryDis     h       == 0) {
s.length = || versionersions     if (!v
          n;
 returiv) ryDhistof (!;
        iory')stId('designHientBy.getElemdocumentistoryDiv = st h        conersions) {
(voryonHisteVersi updat
          }
 }
   ng();
     ditieEis.disabl         th  on') {
 or_productiocked_ftus === 'l_stantcurref (status.       icked
  if lotingdiisable e      // D        
  ions);
y(versrsionHistoris.updateVe  th      history
version pdate  U
        //}
                     }
;
       ock'play = 'bliv.style.distDreques       
         = content;L erHTMntentDiv.inn          co
                          }
          ;
  }</div>`_notess.special ${statues:</strong>ng>Not"><stroass="mb-2v cldi= `<ontent +    c              s) {
  cial_notetus.spe(sta if           }
                    ></div>`;
 spanred_color}</.preferstatushite;">${: w 3px; colordius: border-ra: 2px 8px;r}; paddingeferred_colos.prstatu${nd: oukgrstyle="bacn spa</strong> <lor:"><strong>Colass="mb-2iv c<dt += `      conten         or) {
     ferred_col.pretatusif (s                   }
           div>`;
  text}</r_us.customeng> ${statext:</strostrong>T2"><="mb- class `<divcontent +=            
        omer_text) {cust(status.     if           ent = '';
  let cont               {
 ontentDiv)v && c (requestDi     if           
   ');
     estContentequomerRId('custmentByetEleent.gumtDiv = doconst conten        cest');
    customerRequd('tByIenlement.getEocumDiv = donst request     c
       otes) {l_ntatus.speciaer_text || sstom (status.cu       ifrequest
 ow customer    // Sh    
 n;
        _versiorrent = status.cuntVersion  this.curre     s;
 urrent_statutatus.cus = sStatrrent   this.cu 
       );
     aleString(.toLocdated_at)us.upDate(statew t = nntenl.textCo updatedEatedEl) (upd  if;
      versioncurrent_status.t = xtContenversionEl.teersionEl)  (v      if }
    
     s)}`;_status.currentsColor(statutatuhis.getSbg-${tus-badge statadge = `bssName laEl.cstatus    ;
        tus.current_statatusnt = sConte.text  statusEl        El) {
  tatus if (sd;
       rder_iatus.o= stt extContenIdEl.tIdEl) orderer   if (ord     
     ed');
   astUpdatmentById('lleetEnt.gdEl = documet update     cons
   ;ntVersion')tById('curre.getElemenentcumonEl = do const versi
       tus');entSta'currentById(ment.getElemEl = docutatusconst s
        OrderId');d('currentlementByIt.getEen = docum orderIdEl   const
     order infoate  Upd  //       
   ;
    } = dataons n, versisig, denst { status  coa) {
      nInfo(dateDesig    updat }
    

      }e);
     falsding(oaasLs.showCanv         thi;
   sage)es.m: ' + errorigno load desr('Failed tshowErro      this.r);
      ign:', erroloading desror 'Error(sole.er        conr) {
    erroh (} catc                 
     }
   
       );falseng(adiLoshowCanvasthis.                {
e        } els
     });              alse);
  (fLoadingCanvas this.show             l();
      vas.renderAl    this.can         {
       ) => nvasData, (SON(cas.loadFromJ  this.canva        ta);
      canvas_dasign.(data.deON.parse= JSta sDacanva    const           a) {
  as_datanvsign.cdata.dea.design && dat       if (lable
      if avaicanvas data/ Load          /
             );
  (datateDesignInfos.updahi       t
     sign infowith de UI ate     // Upd          
  }
                gn');
   si de to load || 'Failed(data.errorew Error     throw n  {
         esponse.ok) f (!r        i   
             n();
esponse.jsoa = await r const dat         ;
  ms}`)p?${paraitor.phign-edpi/admin/deskend/abac../ fetch(`../ = awaitresponse     const             
  
      version);ersion',ms.append('version) paraf (v      i);
       }rId: ordeorder_idhParams({ SearcRLnew Uparams = onst       c          try {
       
     rId;
orded = ntOrderI.curre    this    g(true);
LoadinvashowCanis.s     th   l) {
 = nuld, versionrIn(orde