/**
 * Canva-Style Template Editor
 * Enhanced version of the existing Admin Design Editor with 3-column layout
 * Preserves all existing functionality while adding template-based workflow
 */

class CanvaStyleEditor {
    constructor() {
        // Core properties
        this.canvas = null;
        this.selectedObject = null;
        this.zoom = 1;
        this.templates = [];
        this.currentTemplate = null;
        
        // History management (preserved from original)
        this.history = [];
        this.redoStack = [];
        this.isRestoring = false;
        this.maxHistory = 50;
        
        // UI state
        this.activeCategory = 'all';
        this.searchQuery = '';
        this.isLoading = false;
        
        // API configuration
        this.API_BASE = "http://localhost/my_little_thingz/backend/api";
        
        this.init();
    }
    
    async init() {
        try {
            this.initCanvas();
            this.bindEvents();
            this.initHistoryTracking();
            await this.loadTemplates();
            this.showDefaultState();
        } catch (error) {
            console.error('Error initializing editor:', error);
            this.showError('Failed to initialize editor');
        }
    }
    
    // ==================== CANVAS INITIALIZATION ====================
    
    initCanvas() {
        this.canvas = new fabric.Canvas('designCanvas', {
            width: 800,
            height: 600,
            backgroundColor: '#ffffff',
            selection: true,
            preserveObjectStacking: true
        });
        
        // Add safe area guide
        this.addSafeAreaGuide();
        
        // Canvas event listeners
        this.canvas.on('selection:created', (e) => this.handleObjectSelection(e));
        this.canvas.on('selection:updated', (e) => this.handleObjectSelection(e));
        this.canvas.on('selection:cleared', () => this.handleSelectionCleared());
        this.canvas.on('object:modified', () => this.saveHistorySnapshot());
        this.canvas.on('object:added', () => this.saveHistorySnapshot());
        this.canvas.on('object:removed', () => this.saveHistorySnapshot());
        this.canvas.on('text:changed', () => this.saveHistorySnapshot());
        
        // Initial history snapshot
        this.saveHistorySnapshot();
    }
    
    addSafeAreaGuide() {
        const guide = document.getElementById('safeAreaGuide');
        if (guide) {
            guide.style.display = 'block';
        }
    }
    
    // ==================== EVENT BINDING ====================
    
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
        document.getElementById('downloadBtn').addEventListener('click', () => this.downloadDesign());
        document.getElementById('saveTemplateBtn').addEventListener('click', () => this.saveAsTemplate());
        document.getElementById('completeDesignBtn').addEventListener('click', () => this.completeDesign());
        
        // Canvas background controls
        document.getElementById('canvasBackgroundColor').addEventListener('change', (e) => {
            this.setCanvasBackground(e.target.value);
        });
        document.getElementById('canvasBackgroundHex').addEventListener('change', (e) => {
            this.setCanvasBackground(e.target.value);
        });
        
        // Add element buttons
        document.getElementById('addTextBtn').addEventListener('click', () => this.addText());
        document.getElementById('addImageBtn').addEventListener('click', () => this.addImage());
        document.getElementById('addShapeBtn').addEventListener('click', () => this.showShapeMenu());
        
        // Background image controls
        document.getElementById('uploadBackgroundBtn').addEventListener('click', () => {
            document.getElementById('backgroundUpload').click();
        });
        document.getElementById('backgroundUpload').addEventListener('change', (e) => {
            this.handleBackgroundUpload(e);
        });
        document.getElementById('removeBackgroundBtn').addEventListener('click', () => {
            this.removeCanvasBackground();
        });
        
        // Text properties
        this.bindTextProperties();
        
        // Image properties
        this.bindImageProperties();
        
        // Shape properties
        this.bindShapeProperties();
        
        // File uploads
        document.getElementById('imageUpload').addEventListener('change', (e) => {
            this.handleImageUpload(e);
        });
        document.getElementById('replaceImageUpload').addEventListener('change', (e) => {
            this.handleReplaceImage(e);
        });
        
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
        const alignButtons = document.querySelectorAll('.alignment-btn');
        const deleteTextBtn = document.getElementById('deleteTextBtn');
        
        fontFamily.addEventListener('change', (e) => {
            this.updateTextProperty('fontFamily', e.target.value);
        });
        
        fontSize.addEventListener('input', (e) => {
            const value = parseInt(e.target.value);
            fontSizeValue.textContent = value + 'px';
            this.updateTextProperty('fontSize', value);
        });
        
        textColor.addEventListener('change', (e) => {
            textColorHex.value = e.target.value;
            this.updateTextProperty('fill', e.target.value);
            this.updateColorPreview(textColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        textColorHex.addEventListener('change', (e) => {
            textColor.value = e.target.value;
            this.updateTextProperty('fill', e.target.value);
            this.updateColorPreview(textColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        boldBtn.addEventListener('click', () => this.toggleTextStyle('fontWeight', 'bold', 'normal', boldBtn));
        italicBtn.addEventListener('click', () => this.toggleTextStyle('fontStyle', 'italic', 'normal', italicBtn));
        underlineBtn.addEventListener('click', () => this.toggleTextDecoration(underlineBtn));
        
        alignButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const alignment = e.currentTarget.dataset.align;
                this.setTextAlignment(alignment);
                this.setActiveAlignmentButton(e.currentTarget);
            });
        });
        
        deleteTextBtn.addEventListener('click', () => this.deleteSelectedObject());
    }
    
    bindImageProperties() {
        const replaceImageBtn = document.getElementById('replaceImageBtn');
        const imageOpacity = document.getElementById('imageOpacity');
        const imageOpacityValue = document.getElementById('imageOpacityValue');
        const removeBackgroundImageBtn = document.getElementById('removeBackgroundImageBtn');
        const deleteImageBtn = document.getElementById('deleteImageBtn');
        
        replaceImageBtn.addEventListener('click', () => {
            document.getElementById('replaceImageUpload').click();
        });
        
        imageOpacity.addEventListener('input', (e) => {
            const value = parseInt(e.target.value);
            imageOpacityValue.textContent = value + '%';
            this.updateImageProperty('opacity', value / 100);
        });
        
        removeBackgroundImageBtn.addEventListener('click', () => {
            this.removeImageBackground();
        });
        
        deleteImageBtn.addEventListener('click', () => this.deleteSelectedObject());
    }
    
    bindShapeProperties() {
        const shapeFillColor = document.getElementById('shapeFillColor');
        const shapeFillHex = document.getElementById('shapeFillHex');
        const shapeStrokeColor = document.getElementById('shapeStrokeColor');
        const shapeStrokeHex = document.getElementById('shapeStrokeHex');
        const shapeStrokeWidth = document.getElementById('shapeStrokeWidth');
        const shapeStrokeWidthValue = document.getElementById('shapeStrokeWidthValue');
        const deleteShapeBtn = document.getElementById('deleteShapeBtn');
        
        shapeFillColor.addEventListener('change', (e) => {
            shapeFillHex.value = e.target.value;
            this.updateShapeProperty('fill', e.target.value);
            this.updateColorPreview(shapeFillColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        shapeFillHex.addEventListener('change', (e) => {
            shapeFillColor.value = e.target.value;
            this.updateShapeProperty('fill', e.target.value);
            this.updateColorPreview(shapeFillColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        shapeStrokeColor.addEventListener('change', (e) => {
            shapeStrokeHex.value = e.target.value;
            this.updateShapeProperty('stroke', e.target.value);
            this.updateColorPreview(shapeStrokeColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        shapeStrokeHex.addEventListener('change', (e) => {
            shapeStrokeColor.value = e.target.value;
            this.updateShapeProperty('stroke', e.target.value);
            this.updateColorPreview(shapeStrokeColor.parentElement.querySelector('.color-preview'), e.target.value);
        });
        
        shapeStrokeWidth.addEventListener('input', (e) => {
            const value = parseInt(e.target.value);
            shapeStrokeWidthValue.textContent = value + 'px';
            this.updateShapeProperty('strokeWidth', value);
        });
        
        deleteShapeBtn.addEventListener('click', () => this.deleteSelectedObject());
    }
    
    // ==================== TEMPLATE MANAGEMENT ====================
    
    async loadTemplates() {
        try {
            this.showLoading(true);
            
            // Load templates from API
            const response = await fetch(`${this.API_BASE}/admin/template-gallery.php?action=list`);
            const data = await response.json();
            
            if (data.status === 'success') {
                this.templates = data.templates || [];
            } else {
                // Fallback to sample templates if API fails
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
                    canvas: { width: 800, height: 600, backgroundColor: '#ffe6cc' },
                    elements: [
                        {
                            type: 'text',
                            content: 'Happy Birthday!',
                            x: 300,
                            y: 250,
                            fontSize: 48,
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
                    canvas: { width: 800, height: 600, backgroundColor: '#e3f2fd' },
                    elements: [
                        {
                            type: 'shape',
                            shape: 'rectangle',
                            x: 150,
                            y: 200,
                            width: 500,
                            height: 200,
                            fill: 'transparent',
                            stroke: '#6366f1',
                            strokeWidth: 8
                        },
                        {
                            type: 'text',
                            content: 'Your Name',
                            x: 350,
                            y: 280,
                            fontSize: 36,
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
                    canvas: { 
                        width: 800, 
                        height: 600, 
                        backgroundColor: { 
                            type: 'gradient',
                            direction: 'linear',
                            colors: ['#8b5cf6', '#6366f1']
                        }
                    },
                    elements: [
                        {
                            type: 'text',
                            content: '"Be the change',
                            x: 400,
                            y: 250,
                            fontSize: 32,
                            fontFamily: 'Georgia',
                            fontStyle: 'italic',
                            fill: '#ffffff',
                            textAlign: 'center'
                        },
                        {
                            type: 'text',
                            content: 'you wish to see"',
                            x: 400,
                            y: 320,
                            fontSize: 32,
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
            
            this.currentTemplate = template;
            
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
        if (templateData.canvas) {
            if (templateData.canvas.backgroundColor) {
                if (typeof templateData.canvas.backgroundColor === 'string') {
                    this.canvas.setBackgroundColor(templateData.canvas.backgroundColor, this.canvas.renderAll.bind(this.canvas));
                    this.updateBackgroundColorUI(templateData.canvas.backgroundColor);
                } else if (templateData.canvas.backgroundColor.type === 'gradient') {
                    // Handle gradient backgrounds
                    const gradientData = templateData.canvas.backgroundColor;
                    const gradient = new fabric.Gradient({
                        type: gradientData.direction === 'radial' ? 'radial' : 'linear',
                        coords: gradientData.direction === 'radial' 
                            ? { x1: this.canvas.width/2, y1: this.canvas.height/2, r1: 0, r2: Math.max(this.canvas.width, this.canvas.height)/2 }
                            : { x1: 0, y1: 0, x2: this.canvas.width, y2: this.canvas.height },
                        colorStops: gradientData.colors.map((color, i) => ({
                            offset: i / (gradientData.colors.length - 1),
                            color: color
                        }))
                    });
                    this.canvas.setBackgroundColor(gradient, this.canvas.renderAll.bind(this.canvas));
                }
            }
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
                        // Create placeholder
                        fabricObject = new fabric.Rect({
                            left: element.x || 100,
                            top: element.y || 100,
                            width: element.width || 200,
                            height: element.height || 150,
                            fill: '#f0f0f0',
                            stroke: '#cccccc',
                            strokeWidth: 2,
                            strokeDashArray: [5, 5]
                        });
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
        } else if (obj.type === 'rect' || obj.type === 'circle') {
            title.textContent = 'Shape Properties';
            this.showShapeProperties(obj);
        } else {
            title.textContent = 'Object Properties';
            this.showDefaultState();
        }
    }
    
    hideAllPropertyPanels() {
        document.getElementById('noSelectionPanel').style.display = 'none';
        document.getElementById('textPropertiesPanel').style.display = 'none';
        document.getElementById('imagePropertiesPanel').style.display = 'none';
        document.getElementById('shapePropertiesPanel').style.display = 'none';
    }
    
    showDefaultState() {
        this.hideAllPropertyPanels();
        document.getElementById('propertiesTitle').textContent = 'Properties';
        document.getElementById('noSelectionPanel').style.display = 'block';
    }
    
    showTextProperties(textObj) {
        document.getElementById('textPropertiesPanel').style.display = 'block';
        
        // Update controls with current values
        document.getElementById('fontFamily').value = textObj.fontFamily || 'Arial';
        document.getElementById('fontSize').value = textObj.fontSize || 24;
        document.getElementById('fontSizeValue').textContent = (textObj.fontSize || 24) + 'px';
        document.getElementById('textColor').value = textObj.fill || '#000000';
        document.getElementById('textColorHex').value = textObj.fill || '#000000';
        
        // Update style buttons
        document.getElementById('boldBtn').classList.toggle('active', textObj.fontWeight === 'bold');
        document.getElementById('italicBtn').classList.toggle('active', textObj.fontStyle === 'italic');
        document.getElementById('underlineBtn').classList.toggle('active', textObj.textDecoration === 'underline');
        
        // Update alignment buttons
        const alignment = textObj.textAlign || 'left';
        document.querySelectorAll('.alignment-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.align === alignment);
        });
        
        // Update color preview
        this.updateColorPreview(document.querySelector('#textPropertiesPanel .color-preview'), textObj.fill || '#000000');
    }
    
    showImageProperties(imageObj) {
        document.getElementById('imagePropertiesPanel').style.display = 'block';
        
        // Update opacity control
        const opacity = Math.round((imageObj.opacity || 1) * 100);
        document.getElementById('imageOpacity').value = opacity;
        document.getElementById('imageOpacityValue').textContent = opacity + '%';
    }
    
    showShapeProperties(shapeObj) {
        document.getElementById('shapePropertiesPanel').style.display = 'block';
        
        // Update fill color
        const fillColor = shapeObj.fill || '#cccccc';
        document.getElementById('shapeFillColor').value = fillColor;
        document.getElementById('shapeFillHex').value = fillColor;
        this.updateColorPreview(document.querySelector('#shapeFillColor').parentElement.querySelector('.color-preview'), fillColor);
        
        // Update stroke color
        const strokeColor = shapeObj.stroke || '#000000';
        document.getElementById('shapeStrokeColor').value = strokeColor;
        document.getElementById('shapeStrokeHex').value = strokeColor;
        this.updateColorPreview(document.querySelector('#shapeStrokeColor').parentElement.querySelector('.color-preview'), strokeColor);
        
        // Update stroke width
        const strokeWidth = shapeObj.strokeWidth || 1;
        document.getElementById('shapeStrokeWidth').value = strokeWidth;
        document.getElementById('shapeStrokeWidthValue').textContent = strokeWidth + 'px';
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
    
    updateImageProperty(property, value) {
        if (!this.selectedObject || this.selectedObject.type !== 'image') return;
        
        this.selectedObject.set(property, value);
        this.canvas.renderAll();
    }
    
    updateShapeProperty(property, value) {
        if (!this.selectedObject || (this.selectedObject.type !== 'rect' && this.selectedObject.type !== 'circle')) return;
        
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
    
    setTextAlignment(alignment) {
        this.updateTextProperty('textAlign', alignment);
    }
    
    setActiveAlignmentButton(activeButton) {
        document.querySelectorAll('.alignment-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        activeButton.classList.add('active');
    }
    
    // ==================== CANVAS BACKGROUND ====================
    
    setCanvasBackground(color) {
        this.canvas.setBackgroundColor(color, this.canvas.renderAll.bind(this.canvas));
        this.updateBackgroundColorUI(color);
    }
    
    updateBackgroundColorUI(color) {
        document.getElementById('canvasBackgroundColor').value = color;
        document.getElementById('canvasBackgroundHex').value = color;
        this.updateColorPreview(document.querySelector('#canvasBackgroundColor').parentElement.querySelector('.color-preview'), color);
    }
    
    handleBackgroundUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        if (!file.type.startsWith('image/')) {
            this.showError('Please select a valid image file');
            return;
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            this.showError('Image size must be less than 10MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = (e) => {
            fabric.Image.fromURL(e.target.result, (img) => {
                // Scale image to fit canvas
                const scaleX = this.canvas.width / img.width;
                const scaleY = this.canvas.height / img.height;
                const scale = Math.max(scaleX, scaleY);
                
                img.set({
                    scaleX: scale,
                    scaleY: scale,
                    originX: 'center',
                    originY: 'center',
                    left: this.canvas.width / 2,
                    top: this.canvas.height / 2,
                    selectable: false,
                    evented: false
                });
                
                this.canvas.setBackgroundImage(img, this.canvas.renderAll.bind(this.canvas));
                
                // Show remove background button
                document.getElementById('removeBackgroundBtn').style.display = 'block';
            }, { crossOrigin: 'anonymous' });
        };
        reader.readAsDataURL(file);
    }
    
    removeCanvasBackground() {
        this.canvas.setBackgroundImage(null, this.canvas.renderAll.bind(this.canvas));
        document.getElementById('removeBackgroundBtn').style.display = 'none';
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
    
    addImage() {
        document.getElementById('imageUpload').click();
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
    
    showShapeMenu() {
        // Simple implementation - add rectangle by default
        // Could be enhanced with a dropdown menu
        this.addShape('rectangle');
    }
    
    addShape(shapeType) {
        let shape;
        
        if (shapeType === 'rectangle') {
            shape = new fabric.Rect({
                left: 100,
                top: 100,
                width: 100,
                height: 100,
                fill: '#cccccc',
                stroke: '#000000',
                strokeWidth: 1
            });
        } else if (shapeType === 'circle') {
            shape = new fabric.Circle({
                left: 100,
                top: 100,
                radius: 50,
                fill: '#cccccc',
                stroke: '#000000',
                strokeWidth: 1
            });
        }
        
        if (shape) {
            this.canvas.add(shape);
            this.canvas.setActiveObject(shape);
            this.canvas.renderAll();
        }
    }
    
    deleteSelectedObject() {
        if (!this.selectedObject) return;
        
        this.canvas.remove(this.selectedObject);
        this.canvas.renderAll();
        this.selectedObject = null;
        this.showDefaultState();
    }
    
    // ==================== BACKGROUND REMOVAL ====================
    
    async removeImageBackground() {
        if (!this.selectedObject || this.selectedObject.type !== 'image') return;
        
        try {
            // This is a placeholder for background removal functionality
            // You would integrate with an API like remove.bg here
            this.showError('Background removal feature coming soon!');
            
            // Example implementation:
            /*
            const imageDataURL = this.selectedObject.toDataURL();
            const response = await fetch('https://api.remove.bg/v1.0/removebg', {
                method: 'POST',
                headers: {
                    'X-Api-Key': 'YOUR_API_KEY',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    image_url: imageDataURL,
                    size: 'auto'
                })
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                
                fabric.Image.fromURL(url, (img) => {
                    // Replace current image
                    const left = this.selectedObject.left;
                    const top = this.selectedObject.top;
                    const scaleX = this.selectedObject.scaleX;
                    const scaleY = this.selectedObject.scaleY;
                    
                    this.canvas.remove(this.selectedObject);
                    
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
                });
            }
            */
        } catch (error) {
            console.error('Error removing background:', error);
            this.showError('Failed to remove background');
        }
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
        document.getElementById('zoomDisplay').textContent = Math.round(zoom * 100) + '%';
    }
    
    // ==================== HISTORY MANAGEMENT ====================
    
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
        
        undoBtn.disabled = this.history.length <= 1;
        redoBtn.disabled = this.redoStack.length === 0;
    }
    
    // ==================== SAVE & EXPORT ====================
    
    downloadDesign() {
        const dataURL = this.canvas.toDataURL({
            format: 'png',
            quality: 1.0,
            multiplier: 2
        });
        
        const link = document.createElement('a');
        link.download = `design_${Date.now()}.png`;
        link.href = dataURL;
        link.click();
    }
    
    async saveAsTemplate() {
        try {
            const templateName = prompt('Enter template name:');
            if (!templateName) return;
            
            const category = prompt('Enter category (birthday, name-frame, quotes, anniversary):') || 'other';
            
            const designData = {
                canvas: {
                    width: this.canvas.width,
                    height: this.canvas.height,
                    backgroundColor: this.canvas.backgroundColor
                },
                elements: this.canvas.toJSON().objects
            };
            
            const previewUrl = this.canvas.toDataURL({
                format: 'png',
                quality: 0.8,
                multiplier: 0.5
            });
            
            const payload = {
                action: 'save_template',
                name: templateName,
                category: category,
                template_data: designData,
                thumbnail: previewUrl
            };
            
            const response = await fetch(`${this.API_BASE}/admin/template-gallery.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                alert('Template saved successfully!');
                await this.loadTemplates(); // Refresh template list
            } else {
                throw new Error(data.message || 'Failed to save template');
            }
        } catch (error) {
            console.error('Error saving template:', error);
            this.showError('Failed to save template: ' + error.message);
        }
    }
    
    async completeDesign() {
        try {
            const finalImageUrl = this.canvas.toDataURL({
                format: 'png',
                quality: 1.0,
                multiplier: 2
            });
            
            // This would integrate with your existing design completion workflow
            alert('Design completed! (Integration with existing workflow needed)');
            
            // Example integration:
            /*
            const payload = {
                action: 'complete',
                design_data: this.canvas.toJSON(),
                final_image_url: finalImageUrl,
                template_id: this.currentTemplate?.id
            };
            
            const response = await fetch(`${this.API_BASE}/admin/design-editor.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            
            if (data.status === 'success') {
                alert('Design completed successfully!');
                // Redirect or close editor
            }
            */
        } catch (error) {
            console.error('Error completing design:', error);
            this.showError('Failed to complete design: ' + error.message);
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
            case 's':
                e.preventDefault();
                this.saveAsTemplate();
                break;
            case 'd':
                e.preventDefault();
                this.downloadDesign();
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
        overlay.style.display = show ? 'flex' : 'none';
    }
    
    showError(message) {
        // Simple alert for now - could be enhanced with toast notifications
        alert('Error: ' + message);
    }
}

// Initialize the editor when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.canvaEditor = new CanvaStyleEditor();
});