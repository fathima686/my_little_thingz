/**
 * Admin Design Editor - Canva Style Implementation
 * Enhanced version with 3-column layout and template support
 * Preserves all original functionality while adding modern UI
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
        const templateSearch = document.getElementById('templateSearch');
        if (templateSearch) {
            templateSearch.addEventListener('input', (e) => {
                this.searchQuery = e.target.value.toLowerCase();
                this.filterTemplates();
            });
        }
        
        // Category filters
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setActiveCategory(e.target.dataset.category);
            });
        });
        
        // Toolbar buttons
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');
        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const previewBtn = document.getElementById('previewBtn');
        const saveDesignBtn = document.getElementById('saveDesignBtn');
        const completeDesignBtn = document.getElementById('completeDesignBtn');
        const orderDetailsBtn = document.getElementById('orderDetailsBtn');
        const backBtn = document.getElementById('backBtn');
        
        if (undoBtn) undoBtn.addEventListener('click', () => this.undo());
        if (redoBtn) redoBtn.addEventListener('click', () => this.redo());
        if (zoomInBtn) zoomInBtn.addEventListener('click', () => this.zoomIn());
        if (zoomOutBtn) zoomOutBtn.addEventListener('click', () => this.zoomOut());
        if (previewBtn) previewBtn.addEventListener('click', () => this.previewDesign());
        if (saveDesignBtn) saveDesignBtn.addEventListener('click', () => this.downloadDesign());
        if (completeDesignBtn) completeDesignBtn.addEventListener('click', () => this.completeDesign());
        if (orderDetailsBtn) orderDetailsBtn.addEventListener('click', () => this.showOrderDetailsModal());
        if (backBtn) backBtn.addEventListener('click', () => this.goBackToAdmin());
        
        // Order Details Modal
        const closeOrderDetailsBtn = document.getElementById('closeOrderDetailsBtn');
        const orderDetailsModal = document.getElementById('orderDetailsModal');
        if (closeOrderDetailsBtn) {
            closeOrderDetailsBtn.addEventListener('click', () => this.hideOrderDetailsModal());
        }
        if (orderDetailsModal) {
            orderDetailsModal.addEventListener('click', (e) => {
                if (e.target === orderDetailsModal) {
                    this.hideOrderDetailsModal();
                }
            });
        }
        
        // Canvas background controls
        const canvasBackgroundColor = document.getElementById('canvasBackgroundColor');
        const canvasBackgroundHex = document.getElementById('canvasBackgroundHex');
        
        if (canvasBackgroundColor) {
            canvasBackgroundColor.addEventListener('change', (e) => {
                this.setCanvasBackground(e.target.value);
            });
        }
        
        if (canvasBackgroundHex) {
            canvasBackgroundHex.addEventListener('change', (e) => {
                this.setCanvasBackground(e.target.value);
            });
        }
        
        // Add element buttons
        const addTextBtn = document.getElementById('addTextBtn');
        const uploadImageBtn = document.getElementById('uploadImageBtn');
        
        if (addTextBtn) addTextBtn.addEventListener('click', () => this.addText());
        if (uploadImageBtn) uploadImageBtn.addEventListener('click', () => this.uploadImage());
        
        // Text properties
        this.bindTextProperties();
        
        // Image properties
        this.bindImageProperties();
        
        // Object controls
        this.bindObjectControls();
        
        // File uploads
        const imageUpload = document.getElementById('imageUpload');
        const replaceImageUpload = document.getElementById('replaceImageUpload');
        
        if (imageUpload) {
            imageUpload.addEventListener('change', (e) => {
                this.handleImageUpload(e);
            });
        }
        
        if (replaceImageUpload) {
            replaceImageUpload.addEventListener('change', (e) => {
                this.handleReplaceImage(e);
            });
        }
        
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
        const removeBackgroundBtn = document.getElementById('removeBackgroundBtn');
        const replaceImageBtn = document.getElementById('replaceImageBtn');
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        
        if (removeBackgroundBtn) {
            removeBackgroundBtn.addEventListener('click', () => this.removeBackground());
        }
        
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
    
    // ==================== TEMPLATE MANAGEMENT ====================
    
    async loadTemplates() {
        try {
            this.showLoading(true);
            
            // Try to load templates from API
            try {
                const response = await fetch('../../backend/api/admin/template-gallery.php?action=list', {
                    headers: {
                        'X-Admin-User-Id': '1'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.status === 'success' && data.data && data.data.templates) {
                    this.templates = data.data.templates;
                    console.log('✅ Templates loaded from API:', this.templates.length);
                } else {
                    throw new Error(data.message || 'API returned no templates');
                }
            } catch (error) {
                console.log('⚠️ Template API not available, using sample templates:', error.message);
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
            // Birthday Templates
            {
                id: 'birthday-1',
                name: 'Birthday Card - Classic',
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
                id: 'birthday-2',
                name: 'Birthday - Pastel Pink',
                category: 'birthday',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZkNmU3Ii8+PGNpcmNsZSBjeD0iMTAwIiBjeT0iNjAiIHI9IjMwIiBmaWxsPSIjZmZhZGMzIiBvcGFjaXR5PSIwLjYiLz48Y2lyY2xlIGN4PSI1MCIgY3k9IjQwIiByPSIyMCIgZmlsbD0iI2ZmYWRjMyIgb3BhY2l0eT0iMC40Ii8+PHRleHQgeD0iNTAlIiB5PSI1NSUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxOCIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNmZjY2YjIiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkhhcHB5PC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNzAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSIjZmY2NmIyIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5CaXJ0aGRheSE8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffd6e7' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'circle',
                            x: 250,
                            y: 150,
                            radius: 80,
                            fill: '#ffadc3',
                            opacity: 0.6
                        },
                        {
                            type: 'shape',
                            shape: 'circle',
                            x: 150,
                            y: 100,
                            radius: 50,
                            fill: '#ffadc3',
                            opacity: 0.4
                        },
                        {
                            type: 'text',
                            content: 'Happy',
                            x: 250,
                            y: 160,
                            fontSize: 42,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff66b2'
                        },
                        {
                            type: 'text',
                            content: 'Birthday!',
                            x: 230,
                            y: 210,
                            fontSize: 42,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff66b2'
                        }
                    ]
                }
            },
            {
                id: 'birthday-3',
                name: 'Birthday - Pastel Blue',
                category: 'birthday',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTBmMmZlIi8+PHJlY3QgeD0iMzAiIHk9IjMwIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjYmVlM2Y4IiBvcGFjaXR5PSIwLjciLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE2IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0iIzAwNzdjYyIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkNlbGVicmF0ZSE8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#e0f2fe' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 100,
                            y: 100,
                            width: 400,
                            height: 200,
                            fill: '#bee3f8',
                            opacity: 0.7
                        },
                        {
                            type: 'text',
                            content: 'Celebrate!',
                            x: 220,
                            y: 180,
                            fontSize: 40,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#0077cc'
                        }
                    ]
                }
            },
            {
                id: 'birthday-4',
                name: 'Birthday - Colorful',
                category: 'birthday',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImJkYXkiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPjxzdG9wIG9mZnNldD0iMCUiIHN0b3AtY29sb3I9IiNmZmRkZTciLz48c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmZmNjZTciLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2JkYXkpIi8+PHRleHQgeD0iNTAlIiB5PSI0NSUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIyMCIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNmZjY2MDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkhBUFBZPC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNjUlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjAiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSIjZmY2NjAwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5CSVJUSERBWSE8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffdde7' },
                    elements: [
                        {
                            type: 'text',
                            content: 'HAPPY',
                            x: 220,
                            y: 140,
                            fontSize: 48,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff6600'
                        },
                        {
                            type: 'text',
                            content: 'BIRTHDAY!',
                            x: 180,
                            y: 200,
                            fontSize: 48,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff6600'
                        }
                    ]
                }
            },
            {
                id: 'birthday-5',
                name: 'Birthday - Elegant',
                category: 'birthday',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmNWY3Ii8+PHJlY3QgeD0iNDAiIHk9IjQwIiB3aWR0aD0iMTIwIiBoZWlnaHQ9IjQwIiBmaWxsPSJub25lIiBzdHJva2U9IiNmZjg1YTEiIHN0cm9rZS13aWR0aD0iMiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iR2VvcmdpYSIgZm9udC1zaXplPSIxNCIgZm9udC1zdHlsZT0iaXRhbGljIiBmaWxsPSIjZmY4NWExIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SGFwcHkgQmlydGhkYXk8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#fff5f7' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 150,
                            y: 150,
                            width: 300,
                            height: 100,
                            fill: 'transparent',
                            stroke: '#ff85a1',
                            strokeWidth: 4
                        },
                        {
                            type: 'text',
                            content: 'Happy Birthday',
                            x: 220,
                            y: 185,
                            fontSize: 32,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#ff85a1'
                        }
                    ]
                }
            },
            // Anniversary Templates
            {
                id: 'anniversary-1',
                name: 'Anniversary - Classic',
                category: 'anniversary',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZlYmVlIi8+PHRleHQgeD0iNTAlIiB5PSI0MCUiIGZvbnQtZmFtaWx5PSJHZW9yZ2lhIiBmb250LXNpemU9IjE2IiBmaWxsPSIjZGMyNjI2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5IYXBweTwvdGV4dD48dGV4dCB4PSI1MCUiIHk9IjYwJSIgZm9udC1mYW1pbHk9Ikdlb3JnaWEiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IiNkYzI2MjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkFubml2ZXJzYXJ5PC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffebee' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy',
                            x: 240,
                            y: 150,
                            fontSize: 38,
                            fontFamily: 'Georgia',
                            fill: '#dc2626'
                        },
                        {
                            type: 'text',
                            content: 'Anniversary',
                            x: 200,
                            y: 200,
                            fontSize: 38,
                            fontFamily: 'Georgia',
                            fill: '#dc2626'
                        }
                    ]
                }
            },
            {
                id: 'anniversary-2',
                name: 'Anniversary - Romantic',
                category: 'anniversary',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImFubiIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+PHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmZDZlNyIvPjxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iI2ZmYWRjMyIvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjYW5uKSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iR2VvcmdpYSIgZm9udC1zaXplPSIxOCIgZm9udC1zdHlsZT0iaXRhbGljIiBmaWxsPSIjZGMxNjU4IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+VG9nZXRoZXIgRm9yZXZlcjwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffd6e7' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Together Forever',
                            x: 180,
                            y: 180,
                            fontSize: 40,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#dc1658'
                        }
                    ]
                }
            },
            {
                id: 'anniversary-3',
                name: 'Anniversary - Gold',
                category: 'anniversary',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmOGRjIi8+PHJlY3QgeD0iMzAiIHk9IjMwIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjYwIiBmaWxsPSJub25lIiBzdHJva2U9IiNmZmMxMDciIHN0cm9rZS13aWR0aD0iMyIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSIjZmY5ODAwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+QW5uaXZlcnNhcnk8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#fff8dc' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 100,
                            y: 100,
                            width: 400,
                            height: 200,
                            fill: 'transparent',
                            stroke: '#ffc107',
                            strokeWidth: 6
                        },
                        {
                            type: 'text',
                            content: 'Anniversary',
                            x: 210,
                            y: 185,
                            fontSize: 36,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff9800'
                        }
                    ]
                }
            },
            {
                id: 'anniversary-4',
                name: 'Anniversary - Elegant',
                category: 'anniversary',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjNlNWY1Ii8+PHRleHQgeD0iNTAlIiB5PSI0MCUiIGZvbnQtZmFtaWx5PSJHZW9yZ2lhIiBmb250LXNpemU9IjE0IiBmb250LXN0eWxlPSJpdGFsaWMiIGZpbGw9IiM4ODM5OGEiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkNlbGVicmF0aW5nPC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNjAlIiBmb250LWZhbWlseT0iR2VvcmdpYSIgZm9udC1zaXplPSIxNCIgZm9udC1zdHlsZT0iaXRhbGljIiBmaWxsPSIjODgzOThhIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5PdXIgTG92ZTwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#f3e5f5' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Celebrating',
                            x: 220,
                            y: 150,
                            fontSize: 34,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#88398a'
                        },
                        {
                            type: 'text',
                            content: 'Our Love',
                            x: 230,
                            y: 200,
                            fontSize: 34,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#88398a'
                        }
                    ]
                }
            },
            {
                id: 'anniversary-5',
                name: 'Anniversary - Modern',
                category: 'anniversary',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZlYmVlIi8+PGNpcmNsZSBjeD0iMTAwIiBjeT0iNjAiIHI9IjI1IiBmaWxsPSIjZWY0NDQ0IiBvcGFjaXR5PSIwLjMiLz48Y2lyY2xlIGN4PSI1MCIgY3k9IjQwIiByPSIxNSIgZmlsbD0iI2VmNDQ0NCIgb3BhY2l0eT0iMC4yIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNlZjQ0NDQiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Bbm5pdmVyc2FyeTwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffebee' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'circle',
                            x: 250,
                            y: 150,
                            radius: 70,
                            fill: '#ef4444',
                            opacity: 0.3
                        },
                        {
                            type: 'shape',
                            shape: 'circle',
                            x: 150,
                            y: 100,
                            radius: 40,
                            fill: '#ef4444',
                            opacity: 0.2
                        },
                        {
                            type: 'text',
                            content: 'Anniversary',
                            x: 210,
                            y: 185,
                            fontSize: 38,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ef4444'
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
            },
            // Wedding Templates
            {
                id: 'wedding-1',
                name: 'Wedding - Elegant',
                category: 'wedding',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+PHJlY3QgeD0iMzAiIHk9IjMwIiB3aWR0aD0iMTQwIiBoZWlnaHQ9IjYwIiBmaWxsPSJub25lIiBzdHJva2U9IiNkNGFmMzciIHN0cm9rZS13aWR0aD0iMiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iR2VvcmdpYSIgZm9udC1zaXplPSIxNCIgZm9udC1zdHlsZT0iaXRhbGljIiBmaWxsPSIjZDRhZjM3IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+V2VkZGluZzwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffffff' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 150,
                            y: 120,
                            width: 300,
                            height: 160,
                            fill: 'transparent',
                            stroke: '#d4af37',
                            strokeWidth: 4
                        },
                        {
                            type: 'text',
                            content: 'Wedding',
                            x: 250,
                            y: 185,
                            fontSize: 36,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#d4af37'
                        }
                    ]
                }
            },
            {
                id: 'wedding-2',
                name: 'Wedding - Romantic',
                category: 'wedding',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmNWY3Ii8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJHZW9yZ2lhIiBmb250LXNpemU9IjE2IiBmb250LXN0eWxlPSJpdGFsaWMiIGZpbGw9IiNlOTFlNjMiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Kb2luIFVzPC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#fff5f7' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Join Us',
                            x: 240,
                            y: 185,
                            fontSize: 40,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#e91e63'
                        }
                    ]
                }
            },
            // Christmas Templates
            {
                id: 'christmas-1',
                name: 'Christmas - Classic',
                category: 'christmas',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZlYmVlIi8+PHRleHQgeD0iNTAlIiB5PSI0NSUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxOCIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNjNjI4MjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk1lcnJ5PC90ZXh0Pjx0ZXh0IHg9IjUwJSIgeT0iNjUlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSIjMWI1ZTIwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIj5DaHJpc3RtYXMhPC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ffebee' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Merry',
                            x: 240,
                            y: 150,
                            fontSize: 44,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#c62828'
                        },
                        {
                            type: 'text',
                            content: 'Christmas!',
                            x: 200,
                            y: 210,
                            fontSize: 44,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#1b5e20'
                        }
                    ]
                }
            },
            {
                id: 'christmas-2',
                name: 'Christmas - Festive',
                category: 'christmas',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMWI1ZTIwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNmZmZmZmYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5IYXBweSBIb2xpZGF5czwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#1b5e20' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy Holidays',
                            x: 180,
                            y: 185,
                            fontSize: 40,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ffffff'
                        }
                    ]
                }
            },
            // Diwali Templates
            {
                id: 'diwali-1',
                name: 'Diwali - Traditional',
                category: 'diwali',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImRpdyIgeDE9IjAlIiB5MT0iMCUiIHgyPSIxMDAlIiB5Mj0iMTAwJSI+PHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmOTgwMCIvPjxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iI2ZmNTcyMiIvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZGl3KSIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSIjZmZmZmZmIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SGFwcHkgRGl3YWxpPC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#ff9800' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy Diwali',
                            x: 200,
                            y: 185,
                            fontSize: 44,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ffffff'
                        }
                    ]
                }
            },
            {
                id: 'diwali-2',
                name: 'Diwali - Festive',
                category: 'diwali',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmOGUxIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiNmZjk4MDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5GZXN0aXZhbCBvZiBMaWdodHM8L3RleHQ+PC9zdmc+',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#fff8e1' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Festival of Lights',
                            x: 160,
                            y: 185,
                            fontSize: 38,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ff9800'
                        }
                    ]
                }
            },
            // New Year Templates
            {
                id: 'newyear-1',
                name: 'New Year - Celebration',
                category: 'new-year',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9Im55IiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjNjY2NmZmIi8+PHN0b3Agb2Zmc2V0PSIxMDAlIiBzdG9wLWNvbG9yPSIjZmY2NmZmIi8+PC9saW5lYXJHcmFkaWVudD48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNueSkiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE4IiBmb250LXdlaWdodD0iYm9sZCIgZmlsbD0iI2ZmZmZmZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkhhcHB5IE5ldyBZZWFyPC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#6666ff' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy New Year',
                            x: 170,
                            y: 185,
                            fontSize: 42,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#ffffff'
                        }
                    ]
                }
            },
            {
                id: 'newyear-2',
                name: 'New Year - Elegant',
                category: 'new-year',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMDAwMDAwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJHZW9yZ2lhIiBmb250LXNpemU9IjE2IiBmb250LXN0eWxlPSJpdGFsaWMiIGZpbGw9IiNmZmQ3MDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj4yMDI2PC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#000000' },
                    elements: [
                        {
                            type: 'text',
                            content: '2026',
                            x: 250,
                            y: 185,
                            fontSize: 60,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#ffd700'
                        }
                    ]
                }
            },
            // Graduation Templates
            {
                id: 'graduation-1',
                name: 'Graduation - Classic',
                category: 'graduation',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZTNmMmZkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNiIgZm9udC13ZWlnaHQ9ImJvbGQiIGZpbGw9IiMxOTc2ZDIiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Db25ncmF0dWxhdGlvbnMhPC90ZXh0Pjwvc3ZnPg==',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#e3f2fd' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Congratulations!',
                            x: 160,
                            y: 185,
                            fontSize: 40,
                            fontFamily: 'Arial',
                            fontWeight: 'bold',
                            fill: '#1976d2'
                        }
                    ]
                }
            },
            {
                id: 'graduation-2',
                name: 'Graduation - Achievement',
                category: 'graduation',
                thumbnail: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjEyMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmOGUxIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJHZW9yZ2lhIiBmb250LXNpemU9IjE0IiBmb250LXN0eWxlPSJpdGFsaWMiIGZpbGw9IiNmNTc5MDAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Qcm91ZCBHcmFkdWF0ZTwvdGV4dD48L3N2Zz4=',
                template_data: {
                    canvas: { width: 600, height: 400, backgroundColor: '#fff8e1' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Proud Graduate',
                            x: 190,
                            y: 185,
                            fontSize: 36,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#f57900'
                        }
                    ]
                }
            }
        ];
    }
    
    renderTemplates() {
        const grid = document.getElementById('templatesGrid');
        const emptyState = document.getElementById('templatesEmptyState');
        
        if (!grid) return;
        
        const filteredTemplates = this.getFilteredTemplates();
        
        if (filteredTemplates.length === 0) {
            grid.innerHTML = '';
            if (emptyState) grid.appendChild(emptyState);
            return;
        }
        
        if (emptyState) emptyState.style.display = 'none';
        
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
            this.showError('Failed to load template: ' + error.message);
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
                // Ensure valid textBaseline value (fix for Canvas API warning)
                const validBaselines = ['top', 'hanging', 'middle', 'alphabetic', 'ideographic', 'bottom'];
                const textBaseline = validBaselines.includes(element.textBaseline) ? element.textBaseline : 'alphabetic';
                
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
                    // Note: Don't set textBaseline on Fabric.js objects as it's handled internally
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
            if (title) title.textContent = 'Text Properties';
            this.showTextProperties(obj);
        } else if (obj.type === 'image') {
            if (title) title.textContent = 'Image Properties';
            this.showImageProperties(obj);
        } else {
            if (title) title.textContent = 'Object Properties';
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
        const title = document.getElementById('propertiesTitle');
        if (title) title.textContent = 'Properties';
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
        
        // Re-bind image property buttons to ensure they work
        this.bindImagePropertiesButtons();
        
        // Show object controls too
        this.showObjectControls();
    }
    
    bindImagePropertiesButtons() {
        // Bind or re-bind image property buttons
        const removeBackgroundBtn = document.getElementById('removeBackgroundBtn');
        const replaceImageBtn = document.getElementById('replaceImageBtn');
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        
        if (removeBackgroundBtn) {
            // Remove old listener and add new one
            removeBackgroundBtn.onclick = () => this.removeBackground();
        }
        
        if (replaceImageBtn) {
            replaceImageBtn.onclick = () => {
                document.getElementById('replaceImageUpload').click();
            };
        }
        
        if (deleteImageBtn) {
            deleteImageBtn.onclick = () => this.deleteSelectedObject();
        }
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
        const preview = document.querySelector('#canvasBackgroundColor')?.parentElement?.querySelector('.color-preview');
        
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
        const imageUpload = document.getElementById('imageUpload');
        if (imageUpload) imageUpload.click();
    }
    
    handleImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
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
            this.showError('Please select a valid image file');
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
    
    // ==================== BACKGROUND REMOVAL ====================
    
    async removeBackground() {
        console.log('Remove background clicked');
        
        if (!this.selectedObject || this.selectedObject.type !== 'image') {
            console.error('No image selected');
            this.showError('Please select an image first');
            return;
        }
        
        console.log('Starting background removal...');
        
        try {
            // Show loading state
            this.showLoading('Removing background...');
            
            // Get the image as base64
            const imageElement = this.selectedObject.getElement();
            const canvas = document.createElement('canvas');
            
            // Limit image size to prevent API issues (max 12 megapixels for remove.bg)
            const maxDimension = 3000;
            let width = imageElement.naturalWidth || imageElement.width;
            let height = imageElement.naturalHeight || imageElement.height;
            
            if (width > maxDimension || height > maxDimension) {
                const ratio = Math.min(maxDimension / width, maxDimension / height);
                width = Math.floor(width * ratio);
                height = Math.floor(height * ratio);
                console.log(`Resizing image to ${width}x${height} for API compatibility`);
            }
            
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            
            // Use high-quality image rendering
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(imageElement, 0, 0, width, height);
            
            // Convert to blob first for better quality and smaller size
            const blob = await new Promise((resolve) => {
                canvas.toBlob(resolve, 'image/jpeg', 0.95);
            });
            
            console.log('Image converted to blob, size:', (blob.size / 1024).toFixed(2), 'KB');
            
            // Call background removal API with file upload instead of base64
            const formData = new FormData();
            formData.append('image', blob, 'image.jpg');
            
            const response = await fetch('../../backend/api/admin/remove-background.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('API response received:', response.status);
            
            const result = await response.json();
            console.log('API result:', result);
            
            if (result.success) {
                console.log('Background removal successful, updating image...');
                // Replace image with background-removed version
                fabric.Image.fromURL(result.image, (img) => {
                    // Preserve position and scale
                    const left = this.selectedObject.left;
                    const top = this.selectedObject.top;
                    const scaleX = this.selectedObject.scaleX;
                    const scaleY = this.selectedObject.scaleY;
                    const angle = this.selectedObject.angle;
                    
                    // Remove old image
                    this.canvas.remove(this.selectedObject);
                    
                    // Add new image with same properties
                    img.set({
                        left: left,
                        top: top,
                        scaleX: scaleX,
                        scaleY: scaleY,
                        angle: angle
                    });
                    
                    this.canvas.add(img);
                    this.canvas.setActiveObject(img);
                    this.canvas.renderAll();
                    
                    this.selectedObject = img;
                    this.showImageProperties(img);
                    this.hideLoading();
                    this.showSuccess('Background removed successfully!');
                }, { crossOrigin: 'anonymous' });
            } else if (result.fallback) {
                // API key not configured - use client-side processing
                this.hideLoading();
                this.removeBackgroundClientSide();
            } else {
                throw new Error(result.error || 'Failed to remove background');
            }
            
        } catch (error) {
            console.error('Background removal error:', error);
            console.log('Falling back to client-side processing...');
            this.hideLoading();
            
            // Fallback to client-side processing
            this.removeBackgroundClientSide();
        }
    }
    
    removeBackgroundClientSide() {
        // Client-side background removal using canvas manipulation
        // This is a simple implementation - for better results, use remove.bg API
        
        if (!this.selectedObject || this.selectedObject.type !== 'image') return;
        
        try {
            this.showLoading('Processing image...');
            
            const imageElement = this.selectedObject.getElement();
            const canvas = document.createElement('canvas');
            canvas.width = imageElement.naturalWidth || imageElement.width;
            canvas.height = imageElement.naturalHeight || imageElement.height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(imageElement, 0, 0);
            
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;
            
            // Simple background removal: make white/light pixels transparent
            // This is a basic implementation - adjust threshold as needed
            const threshold = 240; // Adjust this value (0-255)
            
            for (let i = 0; i < data.length; i += 4) {
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                
                // If pixel is close to white, make it transparent
                if (r > threshold && g > threshold && b > threshold) {
                    data[i + 3] = 0; // Set alpha to 0 (transparent)
                }
            }
            
            ctx.putImageData(imageData, 0, 0);
            const processedImage = canvas.toDataURL('image/png');
            
            // Replace image with processed version
            fabric.Image.fromURL(processedImage, (img) => {
                const left = this.selectedObject.left;
                const top = this.selectedObject.top;
                const scaleX = this.selectedObject.scaleX;
                const scaleY = this.selectedObject.scaleY;
                const angle = this.selectedObject.angle;
                
                this.canvas.remove(this.selectedObject);
                
                img.set({
                    left: left,
                    top: top,
                    scaleX: scaleX,
                    scaleY: scaleY,
                    angle: angle
                });
                
                this.canvas.add(img);
                this.canvas.setActiveObject(img);
                this.canvas.renderAll();
                
                this.selectedObject = img;
                this.showImageProperties(img);
                this.hideLoading();
                this.showSuccess('Background removed (basic processing). For better results, configure remove.bg API key.');
            }, { crossOrigin: 'anonymous' });
            
        } catch (error) {
            console.error('Client-side background removal error:', error);
            this.hideLoading();
            this.showError('Failed to process image: ' + error.message);
        }
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
    
    // ==================== ORIGINAL FUNCTIONS PRESERVED ====================
    
    loadFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');
        const requestId = urlParams.get('request_id');
        const version = urlParams.get('version');
        
        if (orderId) {
            this.loadDesign(orderId, version);
        } else if (requestId) {
            this.loadDesignFromRequest(requestId, version);
        }
    }
    
    async loadDesign(orderId, version = null) {
        this.showCanvasLoading(true);
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
                    this.showCanvasLoading(false);
                });
            } else {
                this.showCanvasLoading(false);
            }
            
        } catch (error) {
            console.error('Error loading design:', error);
            this.showError('Failed to load design: ' + error.message);
            this.showCanvasLoading(false);
        }
    }
    
    // Helper method to get admin headers from URL parameters
    getAdminHeaders() {
        const urlParams = new URLSearchParams(window.location.search);
        const adminEmail = urlParams.get('admin_email') || 'admin@mylittlethingz.com';
        const adminUserId = urlParams.get('admin_user_id') || '1';
        
        return {
            'X-Admin-Email': adminEmail,
            'X-Admin-User-Id': adminUserId
        };
    }
    
    async loadDesignFromRequest(requestId, version = null) {
        this.showCanvasLoading(true);
        this.currentRequestId = requestId;
        
        try {
            // First, get request details and check if it has an associated order
            const requestResponse = await fetch(`../../backend/api/admin/custom-requests.php?id=${requestId}`, {
                headers: this.getAdminHeaders()
            });
            const requestData = await requestResponse.json();
            
            if (!requestResponse.ok) {
                throw new Error(requestData.message || 'Failed to load request');
            }
            
            // Check if the response is an error (has status field)
            if (requestData.status === 'error') {
                throw new Error(requestData.message || 'Failed to load request');
            }
            
            // Update UI with request info
            this.updateRequestInfo(requestData);
            
            // Check if there's existing design data for this request
            if (requestData.design_data) {
                const canvasData = JSON.parse(requestData.design_data);
                this.canvas.loadFromJSON(canvasData, () => {
                    this.canvas.renderAll();
                    this.showCanvasLoading(false);
                });
            } else {
                // No existing design, start with a blank canvas
                this.showCanvasLoading(false);
            }
            
        } catch (error) {
            console.error('Error loading request:', error);
            this.showError('Failed to load request: ' + error.message);
            this.showCanvasLoading(false);
        }
    }
    
    updateRequestInfo(requestData) {
        // Update order info with request details
        const orderIdEl = document.getElementById('currentOrderId');
        const statusEl = document.getElementById('currentStatus');
        const versionEl = document.getElementById('currentVersion');
        const updatedEl = document.getElementById('lastUpdated');
        
        if (orderIdEl) orderIdEl.textContent = `Request #${requestData.id}`;
        if (statusEl) {
            statusEl.textContent = requestData.status || 'in_progress';
            statusEl.className = `badge status-badge bg-${this.getStatusColor(requestData.status || 'in_progress')}`;
        }
        if (versionEl) versionEl.textContent = '1';
        if (updatedEl) updatedEl.textContent = new Date(requestData.updated_at || Date.now()).toLocaleString();
        
        this.currentStatus = requestData.status || 'in_progress';
        this.currentVersion = 1;
        
        // Show customer request details
        if (requestData.description || requestData.special_notes) {
            const requestDiv = document.getElementById('customerRequest');
            const contentDiv = document.getElementById('customerRequestContent');
            
            if (requestDiv && contentDiv) {
                let content = '';
                if (requestData.description) {
                    content += `<div class="mb-2"><strong>Description:</strong> ${requestData.description}</div>`;
                }
                if (requestData.occasion) {
                    content += `<div class="mb-2"><strong>Occasion:</strong> ${requestData.occasion}</div>`;
                }
                if (requestData.category) {
                    content += `<div class="mb-2"><strong>Category:</strong> ${requestData.category}</div>`;
                }
                if (requestData.budget) {
                    content += `<div class="mb-2"><strong>Budget:</strong> ${requestData.budget}</div>`;
                }
                if (requestData.deadline) {
                    content += `<div class="mb-2"><strong>Deadline:</strong> ${new Date(requestData.deadline).toLocaleDateString()}</div>`;
                }
                
                contentDiv.innerHTML = content;
                requestDiv.style.display = 'block';
            }
        }
        
        // Clear version history for requests (they don't have versions like orders)
        const historyDiv = document.getElementById('designHistory');
        if (historyDiv) {
            historyDiv.innerHTML = '<div class="text-center text-muted">New design request</div>';
        }
    }
    
    updateDesignInfo(data) {
        const { status, design, versions } = data;
        
        // Update order info
        const orderIdEl = document.getElementById('currentOrderId');
        const statusEl = document.getElementById('currentStatus');
        const versionEl = document.getElementById('currentVersion');
        const updatedEl = document.getElementById('lastUpdated');
        
        if (orderIdEl) orderIdEl.textContent = status.order_id;
        if (statusEl) {
            statusEl.textContent = status.current_status;
            statusEl.className = `badge status-badge bg-${this.getStatusColor(status.current_status)}`;
        }
        if (versionEl) versionEl.textContent = status.current_version;
        if (updatedEl) updatedEl.textContent = new Date(status.updated_at).toLocaleString();
        
        this.currentStatus = status.current_status;
        this.currentVersion = status.current_version;
        
        // Show customer request
        if (status.customer_text || status.special_notes) {
            const requestDiv = document.getElementById('customerRequest');
            const contentDiv = document.getElementById('customerRequestContent');
            
            if (requestDiv && contentDiv) {
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
        if (!historyDiv) return;
        
        if (!versions || versions.length === 0) {
            historyDiv.innerHTML = '<div class="text-center text-muted">No versions yet</div>';
            return;
        }
        
        let html = '';
        versions.forEach(version => {
            html += `
                <div style="padding: 8px; border-bottom: 1px solid #eee; font-size: 0.9em;">
                    <div class="d-flex justify-content-between">
                        <strong>Version ${version.version_number}</strong>
                        <small class="text-muted">${new Date(version.created_at).toLocaleDateString()}</small>
                    </div>
                    ${version.notes ? `<div class="small text-muted mt-1">${version.notes}</div>` : ''}
                    ${version.preview_image_path ? `<div class="mt-2"><img src="../../backend/${version.preview_image_path}" class="img-thumbnail" style="max-width: 80px;"></div>` : ''}
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
    
    previewDesign() {
        // Generate preview in a new window
        const dataURL = this.canvas.toDataURL({
            format: 'png',
            quality: 1.0,
            multiplier: 1
        });
        
        const previewWindow = window.open('', '_blank');
        previewWindow.document.write(`
            <html>
                <head><title>Design Preview</title></head>
                <body style="margin: 0; padding: 20px; text-align: center; background: #f5f5f5;">
                    <h2>Design Preview</h2>
                    <img src="${dataURL}" style="max-width: 100%; border: 1px solid #ddd; background: white;">
                </body>
            </html>
        `);
    }
    
    async saveDesign(notify = false) {
        if (!this.currentOrderId && !this.currentRequestId) {
            this.showError('No order or request selected');
            return;
        }
        
        try {
            this.showCanvasLoading(true);
            
            const canvasData = this.canvas.toJSON();
            const previewImage = this.canvas.toDataURL({
                format: 'png',
                quality: 0.8,
                multiplier: 0.5
            });
            
            const notes = document.getElementById('designNotes')?.value || '';
            
            let payload, apiEndpoint;
            
            if (this.currentOrderId) {
                // Saving for an existing order
                payload = {
                    action: 'save',
                    order_id: this.currentOrderId,
                    canvas_data: JSON.stringify(canvasData),
                    preview_image: previewImage,
                    notes: notes,
                    notify_customer: notify
                };
                apiEndpoint = '../../backend/api/admin/design-editor.php';
            } else if (this.currentRequestId) {
                // Saving for a custom request
                payload = {
                    action: 'save_design',
                    request_id: this.currentRequestId,
                    design_data: JSON.stringify(canvasData),
                    preview_image: previewImage,
                    notes: notes,
                    notify_customer: notify
                };
                apiEndpoint = '../../backend/api/admin/custom-requests.php';
            }
            
            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAdminHeaders()
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to save design');
            }
            
            this.showSuccess(notify ? 'Design saved and customer notified!' : 'Design saved successfully!');
            
            // After successful save, mark the request as completed and go back
            if (this.currentRequestId) {
                await this.completeRequestAndGoBack();
            } else {
                // For orders, just go back after a short delay
                setTimeout(() => {
                    this.goBackToAdmin();
                }, 1500);
            }
            
        } catch (error) {
            console.error('Error saving design:', error);
            this.showError('Failed to save design: ' + error.message);
        } finally {
            this.showCanvasLoading(false);
        }
    }
    
    downloadDesign() {
        try {
            // Get the canvas as a data URL (PNG format)
            const dataURL = this.canvas.toDataURL({
                format: 'png',
                quality: 1.0,
                multiplier: 2 // Higher resolution (2x)
            });
            
            // Create a temporary link element
            const link = document.createElement('a');
            
            // Generate filename with timestamp
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, -5);
            const requestId = this.currentRequestId || this.currentOrderId || 'design';
            link.download = `design-${requestId}-${timestamp}.png`;
            
            // Set the data URL as the href
            link.href = dataURL;
            
            // Trigger the download
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            this.showSuccess('Design downloaded successfully!');
            console.log('✅ Design downloaded:', link.download);
            
        } catch (error) {
            console.error('Error downloading design:', error);
            this.showError('Failed to download design: ' + error.message);
        }
    }
    
    async completeDesign() {
        // Save the design first, then complete and go back
        await this.saveDesign(false);
    }
    
    showOrderDetailsModal() {
        // Copy data from sidebar to modal
        const modalOrderId = document.getElementById('modalOrderId');
        const modalStatus = document.getElementById('modalStatus');
        const modalVersion = document.getElementById('modalVersion');
        const modalLastUpdated = document.getElementById('modalLastUpdated');
        const modalCustomerRequest = document.getElementById('modalCustomerRequest');
        const modalCustomerRequestContent = document.getElementById('modalCustomerRequestContent');
        
        // Get data from the class properties
        if (modalOrderId) modalOrderId.textContent = this.requestId ? `Request #${this.requestId}` : '-';
        if (modalStatus) {
            modalStatus.textContent = this.currentStatus || 'Loading...';
            modalStatus.className = 'badge status-badge bg-' + (this.currentStatus === 'completed' ? 'success' : this.currentStatus === 'in_progress' ? 'secondary' : 'warning');
        }
        if (modalVersion) modalVersion.textContent = this.currentVersion || '-';
        if (modalLastUpdated) modalLastUpdated.textContent = this.lastUpdated || '-';
        
        // Show customer request if available
        if (this.customerRequestData) {
            if (modalCustomerRequest) modalCustomerRequest.style.display = 'block';
            if (modalCustomerRequestContent) {
                let html = '';
                if (this.customerRequestData.description) {
                    html += `<div style="margin-bottom: 8px;"><strong>Description:</strong> ${this.customerRequestData.description}</div>`;
                }
                if (this.customerRequestData.occasion) {
                    html += `<div style="margin-bottom: 8px;"><strong>Occasion:</strong> ${this.customerRequestData.occasion}</div>`;
                }
                if (this.customerRequestData.deadline) {
                    html += `<div style="margin-bottom: 8px;"><strong>Deadline:</strong> ${this.customerRequestData.deadline}</div>`;
                }
                modalCustomerRequestContent.innerHTML = html;
            }
        } else {
            if (modalCustomerRequest) modalCustomerRequest.style.display = 'none';
        }
        
        // Show modal
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }
    
    hideOrderDetailsModal() {
        const modal = document.getElementById('orderDetailsModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    goBackToAdmin() {
        // Navigate back to the React admin dashboard - Custom Requests section
        // Check if we're in development (localhost:5173) or production
        const isDevelopment = window.location.hostname === 'localhost' && window.location.port !== '5173';
        
        console.log('🔄 Redirecting to admin dashboard - Custom Requests...');
        console.log('Current location:', window.location.href);
        console.log('Is development:', isDevelopment);
        
        if (isDevelopment) {
            // Development: Go to React dev server with custom-requests section
            const redirectUrl = 'http://localhost:5173/admin#custom-requests';
            console.log('Redirecting to:', redirectUrl);
            window.location.href = redirectUrl;
        } else {
            // Production or already on React server: Use relative path with anchor
            const redirectUrl = '/admin#custom-requests';
            console.log('Redirecting to:', redirectUrl);
            window.location.href = redirectUrl;
        }
    }
    
    async completeRequestAndGoBack() {
        try {
            // Mark the request as completed
            const completeResponse = await fetch('../../backend/api/admin/custom-requests-database-only.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    ...this.getAdminHeaders()
                },
                body: JSON.stringify({
                    request_id: this.currentRequestId,
                    status: 'completed'
                })
            });
            
            const completeData = await completeResponse.json();
            
            if (completeResponse.ok && completeData.status === 'success') {
                this.showSuccess('Design completed successfully! Returning to dashboard...');
                
                // Wait a moment for user to see the success message, then navigate back
                setTimeout(() => {
                    this.goBackToAdmin();
                }, 2000);
            } else {
                console.error('Failed to mark request as completed:', completeData);
                this.showError('Design saved but failed to mark as completed. Please update status manually.');
                
                // Still go back after showing error
                setTimeout(() => {
                    this.goBackToAdmin();
                }, 3000);
            }
            
        } catch (error) {
            console.error('Error completing request:', error);
            this.showError('Design saved but failed to mark as completed. Please update status manually.');
            
            // Still go back after showing error
            setTimeout(() => {
                this.goBackToAdmin();
            }, 3000);
        }
    }
    
    deleteSelected() {
        const activeObjects = this.canvas.getActiveObjects();
        if (activeObjects.length > 0) {
            activeObjects.forEach(obj => {
                this.canvas.remove(obj);
            });
            this.canvas.discardActiveObject();
            this.canvas.renderAll();
            this.showDefaultState();
        }
    }
    
    bringForward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.bringForward(activeObject);
            this.canvas.renderAll();
            this.addToHistory();
        }
    }
    
    sendBackward() {
        const activeObject = this.canvas.getActiveObject();
        if (activeObject) {
            this.canvas.sendBackward(activeObject);
            this.canvas.renderAll();
            this.addToHistory();
        }
    }
    
    clearCanvas() {
        if (confirm('Are you sure you want to clear the entire canvas? This cannot be undone.')) {
            this.canvas.clear();
            this.addProductMockup();
            this.canvas.renderAll();
            this.showDefaultState();
        }
    }
    
    resetZoom() {
        this.setZoom(1);
    }
    
    disableEditing() {
        // Disable all editing tools
        const tools = document.querySelectorAll('.toolbar-btn, .action-button');
        tools.forEach(tool => {
            tool.disabled = true;
            tool.style.opacity = '0.5';
        });
        
        // Disable canvas interaction
        this.canvas.selection = false;
        this.canvas.forEachObject(obj => {
            obj.selectable = false;
            obj.evented = false;
        });
        
        this.canvas.renderAll();
    }
    
    // ==================== HISTORY MANAGEMENT (PRESERVED) ====================
    
    initHistoryTracking() {
        // Save initial state
        this.saveHistorySnapshot();
        
        // Don't track changes during restoration
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
            
            // Update undo/redo button states
            this.updateHistoryButtons();
        } catch (e) {
            console.error('History snapshot failed:', e);
        }
    }
    
    undo() {
        if (this.history.length <= 1) return;
        
        const current = this.history.pop();
        this.redoStack.push(current);
        const previous = this.history[this.history.length - 1];
        this.restoreFromSerialized(previous);
    }
    
    redo() {
        if (this.redoStack.length === 0) return;
        
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
                this.updateHistoryButtons();
                
                // Clear selection after restore
                this.selectedObject = null;
                this.showDefaultState();
            });
        } catch (e) {
            console.error('Error restoring history state:', e);
            this.isRestoring = false;
        }
    }
    
    updateHistoryButtons() {
        const undoBtn = document.getElementById('undoBtn');
        const redoBtn = document.getElementById('redoBtn');
        
        if (undoBtn) undoBtn.disabled = this.history.length <= 1;
        if (redoBtn) redoBtn.disabled = this.redoStack.length === 0;
    }
    
    // ==================== UTILITY FUNCTIONS ====================
    
    showLoading(show) {
        this.isLoading = show;
        this.showCanvasLoading(show);
    }
    
    hideLoading() {
        this.showLoading(false);
    }
    
    showCanvasLoading(show) {
        const overlay = document.getElementById('canvasLoading');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }
    
    showError(message) {
        alert('Error: ' + message);
        console.error(message);
    }
    
    showSuccess(message) {
        alert(message);
        console.log(message);
    }
    
    // ==================== SHAPES ====================
    
    addShape(shapeType) {
        let shape;
        const defaultOptions = {
            left: 200,
            top: 200,
            fill: '#6366f1',
            stroke: '#4f46e5',
            strokeWidth: 2
        };
        
        switch(shapeType) {
            case 'rectangle':
                shape = new fabric.Rect({
                    ...defaultOptions,
                    width: 150,
                    height: 100
                });
                break;
                
            case 'circle':
                shape = new fabric.Circle({
                    ...defaultOptions,
                    radius: 60
                });
                break;
                
            case 'triangle':
                shape = new fabric.Triangle({
                    ...defaultOptions,
                    width: 120,
                    height: 120
                });
                break;
                
            case 'line':
                shape = new fabric.Line([50, 50, 200, 50], {
                    stroke: '#4f46e5',
                    strokeWidth: 3
                });
                break;
                
            case 'star':
                const points = this.createStarPoints(5, 60, 30);
                shape = new fabric.Polygon(points, {
                    ...defaultOptions
                });
                break;
                
            case 'arrow':
                const arrowPoints = [
                    {x: 0, y: 15},
                    {x: 100, y: 15},
                    {x: 100, y: 0},
                    {x: 130, y: 20},
                    {x: 100, y: 40},
                    {x: 100, y: 25},
                    {x: 0, y: 25}
                ];
                shape = new fabric.Polygon(arrowPoints, {
                    ...defaultOptions
                });
                break;
        }
        
        if (shape) {
            this.canvas.add(shape);
            this.canvas.setActiveObject(shape);
            this.canvas.renderAll();
            this.showShapeProperties(shape);
        }
    }
    
    createStarPoints(numPoints, outerRadius, innerRadius) {
        const points = [];
        const step = Math.PI / numPoints;
        
        for (let i = 0; i < 2 * numPoints; i++) {
            const radius = i % 2 === 0 ? outerRadius : innerRadius;
            const angle = i * step - Math.PI / 2;
            points.push({
                x: radius * Math.cos(angle),
                y: radius * Math.sin(angle)
            });
        }
        
        return points;
    }
    
    showShapeProperties(shape) {
        // Show shape-specific properties panel
        const propertiesContent = document.querySelector('.properties-content');
        if (!propertiesContent) return;
        
        propertiesContent.innerHTML = `
            <div class="property-group">
                <label class="property-label">Shape Color</label>
                <div class="color-picker-wrapper">
                    <div class="color-preview">
                        <input type="color" id="shapeFillColor" class="color-input" value="${shape.fill || '#6366f1'}">
                    </div>
                    <input type="text" id="shapeFillHex" class="property-control" value="${shape.fill || '#6366f1'}">
                </div>
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Color</label>
                <div class="color-picker-wrapper">
                    <div class="color-preview">
                        <input type="color" id="shapeStrokeColor" class="color-input" value="${shape.stroke || '#4f46e5'}">
                    </div>
                    <input type="text" id="shapeStrokeHex" class="property-control" value="${shape.stroke || '#4f46e5'}">
                </div>
            </div>
            
            <div class="property-group">
                <label class="property-label">Border Width: <span id="strokeWidthValue">${shape.strokeWidth || 2}</span>px</label>
                <input type="range" id="shapeStrokeWidth" class="range-control" min="0" max="20" value="${shape.strokeWidth || 2}">
            </div>
            
            <div class="property-group">
                <label class="property-label">Opacity: <span id="shapeOpacityValue">${Math.round((shape.opacity || 1) * 100)}</span>%</label>
                <input type="range" id="shapeOpacity" class="range-control" min="0" max="100" value="${Math.round((shape.opacity || 1) * 100)}">
            </div>
            
            <div class="property-group">
                <label class="property-label">Rotation: <span id="shapeRotationValue">${Math.round(shape.angle || 0)}</span>°</label>
                <input type="range" id="shapeRotation" class="range-control" min="0" max="360" value="${Math.round(shape.angle || 0)}">
            </div>
            
            <div class="property-group">
                <button class="action-button" onclick="editor.flipObject('horizontal')">
                    <i class="fas fa-arrows-alt-h"></i> Flip Horizontal
                </button>
                <button class="action-button secondary" onclick="editor.flipObject('vertical')">
                    <i class="fas fa-arrows-alt-v"></i> Flip Vertical
                </button>
            </div>
            
            <div class="property-group">
                <button class="action-button" onclick="editor.duplicateObject()">
                    <i class="fas fa-copy"></i> Duplicate
                </button>
                <button class="action-button secondary" onclick="editor.deleteObject()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        `;
        
        // Bind shape property events
        this.bindShapePropertyEvents(shape);
    }
    
    bindShapePropertyEvents(shape) {
        const fillColor = document.getElementById('shapeFillColor');
        const fillHex = document.getElementById('shapeFillHex');
        const strokeColor = document.getElementById('shapeStrokeColor');
        const strokeHex = document.getElementById('shapeStrokeHex');
        const strokeWidth = document.getElementById('shapeStrokeWidth');
        const opacity = document.getElementById('shapeOpacity');
        const rotation = document.getElementById('shapeRotation');
        
        if (fillColor) {
            fillColor.addEventListener('change', (e) => {
                shape.set('fill', e.target.value);
                if (fillHex) fillHex.value = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (fillHex) {
            fillHex.addEventListener('change', (e) => {
                shape.set('fill', e.target.value);
                if (fillColor) fillColor.value = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (strokeColor) {
            strokeColor.addEventListener('change', (e) => {
                shape.set('stroke', e.target.value);
                if (strokeHex) strokeHex.value = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (strokeHex) {
            strokeHex.addEventListener('change', (e) => {
                shape.set('stroke', e.target.value);
                if (strokeColor) strokeColor.value = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (strokeWidth) {
            strokeWidth.addEventListener('input', (e) => {
                shape.set('strokeWidth', parseInt(e.target.value));
                document.getElementById('strokeWidthValue').textContent = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (opacity) {
            opacity.addEventListener('input', (e) => {
                shape.set('opacity', parseInt(e.target.value) / 100);
                document.getElementById('shapeOpacityValue').textContent = e.target.value;
                this.canvas.renderAll();
            });
        }
        
        if (rotation) {
            rotation.addEventListener('input', (e) => {
                shape.set('angle', parseInt(e.target.value));
                document.getElementById('shapeRotationValue').textContent = e.target.value;
                this.canvas.renderAll();
            });
        }
    }
    
    // ==================== IMAGE FILTERS ====================
    
    applyFilter(filterType, value) {
        if (!this.selectedObject || this.selectedObject.type !== 'image') {
            this.showError('Please select an image first');
            return;
        }
        
        // Remove existing filters of the same type
        this.selectedObject.filters = this.selectedObject.filters.filter(f => 
            f.type !== filterType
        );
        
        // Add new filter
        switch(filterType) {
            case 'brightness':
                this.selectedObject.filters.push(new fabric.Image.filters.Brightness({
                    brightness: value / 100
                }));
                break;
                
            case 'contrast':
                this.selectedObject.filters.push(new fabric.Image.filters.Contrast({
                    contrast: value / 100
                }));
                break;
                
            case 'saturation':
                this.selectedObject.filters.push(new fabric.Image.filters.Saturation({
                    saturation: value / 100
                }));
                break;
                
            case 'blur':
                this.selectedObject.filters.push(new fabric.Image.filters.Blur({
                    blur: value / 100
                }));
                break;
                
            case 'grayscale':
                this.selectedObject.filters.push(new fabric.Image.filters.Grayscale());
                break;
                
            case 'sepia':
                this.selectedObject.filters.push(new fabric.Image.filters.Sepia());
                break;
                
            case 'vintage':
                this.selectedObject.filters.push(new fabric.Image.filters.Vintage());
                break;
                
            case 'sharpen':
                this.selectedObject.filters.push(new fabric.Image.filters.Convolute({
                    matrix: [0, -1, 0, -1, 5, -1, 0, -1, 0]
                }));
                break;
        }
        
        this.selectedObject.applyFilters();
        this.canvas.renderAll();
    }
    
    resetFilters() {
        if (!this.selectedObject || this.selectedObject.type !== 'image') return;
        
        this.selectedObject.filters = [];
        this.selectedObject.applyFilters();
        this.canvas.renderAll();
        
        // Reset filter sliders
        const sliders = ['brightness', 'contrast', 'saturation', 'blur'];
        sliders.forEach(id => {
            const slider = document.getElementById(id + 'Slider');
            if (slider) slider.value = 0;
        });
    }
    
    // ==================== OBJECT MANIPULATION ====================
    
    flipObject(direction) {
        if (!this.selectedObject) return;
        
        if (direction === 'horizontal') {
            this.selectedObject.set('flipX', !this.selectedObject.flipX);
        } else if (direction === 'vertical') {
            this.selectedObject.set('flipY', !this.selectedObject.flipY);
        }
        
        this.canvas.renderAll();
    }
    
    duplicateObject() {
        if (!this.selectedObject) return;
        
        this.selectedObject.clone((cloned) => {
            cloned.set({
                left: this.selectedObject.left + 20,
                top: this.selectedObject.top + 20
            });
            this.canvas.add(cloned);
            this.canvas.setActiveObject(cloned);
            this.canvas.renderAll();
        });
    }
    
    bringToFront() {
        if (!this.selectedObject) return;
        this.canvas.bringToFront(this.selectedObject);
        this.canvas.renderAll();
    }
    
    sendToBack() {
        if (!this.selectedObject) return;
        this.canvas.sendToBack(this.selectedObject);
        this.canvas.renderAll();
    }
    
    // ==================== GRADIENTS ====================
    
    applyGradient(type, color1, color2) {
        if (!this.selectedObject) return;
        
        let gradient;
        
        if (type === 'linear') {
            gradient = new fabric.Gradient({
                type: 'linear',
                coords: {
                    x1: 0,
                    y1: 0,
                    x2: this.selectedObject.width || 100,
                    y2: 0
                },
                colorStops: [
                    { offset: 0, color: color1 },
                    { offset: 1, color: color2 }
                ]
            });
        } else if (type === 'radial') {
            gradient = new fabric.Gradient({
                type: 'radial',
                coords: {
                    x1: (this.selectedObject.width || 100) / 2,
                    y1: (this.selectedObject.height || 100) / 2,
                    r1: 0,
                    x2: (this.selectedObject.width || 100) / 2,
                    y2: (this.selectedObject.height || 100) / 2,
                    r2: (this.selectedObject.width || 100) / 2
                },
                colorStops: [
                    { offset: 0, color: color1 },
                    { offset: 1, color: color2 }
                ]
            });
        }
        
        this.selectedObject.set('fill', gradient);
        this.canvas.renderAll();
    }
    
    // ==================== LAYERS ====================
    
    getLayers() {
        return this.canvas.getObjects().map((obj, index) => ({
            index: index,
            type: obj.type,
            name: obj.name || `${obj.type} ${index + 1}`,
            visible: obj.visible !== false,
            locked: obj.selectable === false
        }));
    }
    
    selectLayer(index) {
        const obj = this.canvas.item(index);
        if (obj) {
            this.canvas.setActiveObject(obj);
            this.canvas.renderAll();
        }
    }
    
    toggleLayerVisibility(index) {
        const obj = this.canvas.item(index);
        if (obj) {
            obj.set('visible', !obj.visible);
            this.canvas.renderAll();
        }
    }
    
    toggleLayerLock(index) {
        const obj = this.canvas.item(index);
        if (obj) {
            obj.set('selectable', !obj.selectable);
            obj.set('evented', !obj.evented);
            this.canvas.renderAll();
        }
    }
    
    moveLayer(fromIndex, toIndex) {
        const obj = this.canvas.item(fromIndex);
        if (obj) {
            this.canvas.moveTo(obj, toIndex);
            this.canvas.renderAll();
        }
    }
    
    deleteLayer(index) {
        const obj = this.canvas.item(index);
        if (obj) {
            this.canvas.remove(obj);
            this.canvas.renderAll();
        }
    }
}

// Initialize the editor when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.editor = new DesignEditor();
    window.designEditor = window.editor; // Keep both for compatibility
});