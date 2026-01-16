import React, { useState, useEffect, useRef, useCallback } from 'react';
import { fabric } from 'fabric';
import { 
  LuX, LuSave, LuDownload, LuUpload, LuType, LuImage, LuSquare, 
  LuCircle, LuRotateCw, LuMove, LuPalette, LuAlignLeft, LuAlignCenter, 
  LuAlignRight, LuBold, LuItalic, LuUnderline, LuZoomIn, LuZoomOut,
  LuLayers, LuCopy, LuTrash2, LuUndo, LuRedo, LuGrid3X3, LuEye
} from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function TemplateEditor({ 
  template, 
  requestId, 
  designId, 
  isOpen, 
  onClose, 
  onSave, 
  onComplete,
  customerImages = [],
  inline = false 
}) {
  const canvasRef = useRef(null);
  const [fabricCanvas, setFabricCanvas] = useState(null);
  const [activeTool, setActiveTool] = useState('select');
  const [selectedObject, setSelectedObject] = useState(null);
  const [canvasHistory, setCanvasHistory] = useState([]);
  const [historyIndex, setHistoryIndex] = useState(-1);
  const [zoom, setZoom] = useState(1);
  const [showColorPicker, setShowColorPicker] = useState(false);
  const [showTextPanel, setShowTextPanel] = useState(false);
  const [showLayersPanel, setShowLayersPanel] = useState(false);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  
  // Text properties
  const [textProperties, setTextProperties] = useState({
    fontSize: 24,
    fontFamily: 'Arial',
    fontWeight: 'normal',
    fontStyle: 'normal',
    textDecoration: '',
    fill: '#000000',
    textAlign: 'left'
  });
  
  // Color picker
  const [currentColor, setCurrentColor] = useState('#000000');

  // Initialize canvas
  useEffect(() => {
    if (!canvasRef.current || fabricCanvas) return;

    const canvas = new fabric.Canvas(canvasRef.current, {
      width: 800,
      height: 600,
      backgroundColor: '#ffffff',
      selection: true,
      preserveObjectStacking: true
    });

    // Enable object controls
    canvas.on('selection:created', handleObjectSelection);
    canvas.on('selection:updated', handleObjectSelection);
    canvas.on('selection:cleared', () => setSelectedObject(null));
    canvas.on('object:modified', saveCanvasState);
    canvas.on('object:added', saveCanvasState);
    canvas.on('object:removed', saveCanvasState);

    setFabricCanvas(canvas);
    
    // Load template if provided
    if (template) {
      loadTemplate(canvas, template);
    }

    return () => {
      canvas.dispose();
    };
  }, [template]);

  // Handle object selection
  const handleObjectSelection = useCallback((e) => {
    const activeObject = e.selected?.[0] || e.target;
    setSelectedObject(activeObject);
    
    if (activeObject && activeObject.type === 'textbox') {
      setTextProperties({
        fontSize: activeObject.fontSize || 24,
        fontFamily: activeObject.fontFamily || 'Arial',
        fontWeight: activeObject.fontWeight || 'normal',
        fontStyle: activeObject.fontStyle || 'normal',
        textDecoration: activeObject.textDecoration || '',
        fill: activeObject.fill || '#000000',
        textAlign: activeObject.textAlign || 'left'
      });
    }
  }, []);

  // Save canvas state for undo/redo
  const saveCanvasState = useCallback(() => {
    if (!fabricCanvas) return;
    
    const state = JSON.stringify(fabricCanvas.toJSON());
    const newHistory = canvasHistory.slice(0, historyIndex + 1);
    newHistory.push(state);
    
    if (newHistory.length > 50) {
      newHistory.shift();
    }
    
    setCanvasHistory(newHistory);
    setHistoryIndex(newHistory.length - 1);
  }, [fabricCanvas, canvasHistory, historyIndex]);

  // Load template data
  const loadTemplate = async (canvas, templateData) => {
    try {
      setLoading(true);
      
      if (typeof templateData === 'string') {
        // Load from API
        const res = await fetch(`${API_BASE}/admin/template-gallery.php?action=template&id=${templateData}`);
        const data = await res.json();
        if (data.status === 'success') {
          templateData = data.template;
        }
      }
      
      if (templateData.template_data) {
        await loadDesignData(canvas, templateData.template_data);
      }
      
      canvas.renderAll();
      saveCanvasState();
    } catch (err) {
      console.error('Error loading template:', err);
    } finally {
      setLoading(false);
    }
  };

  // Load design data into canvas
  const loadDesignData = async (canvas, designData) => {
    if (!designData || !designData.elements) return;
    
    canvas.clear();
    
    // Set background
    if (designData.background) {
      if (designData.background.type === 'solid') {
        canvas.setBackgroundColor(designData.background.color, canvas.renderAll.bind(canvas));
      } else if (designData.background.type === 'gradient') {
        const gradient = new fabric.Gradient({
          type: designData.background.direction === 'radial' ? 'radial' : 'linear',
          coords: designData.background.direction === 'radial' 
            ? { x1: canvas.width/2, y1: canvas.height/2, r1: 0, r2: Math.max(canvas.width, canvas.height)/2 }
            : { x1: 0, y1: 0, x2: canvas.width, y2: canvas.height },
          colorStops: designData.background.colors.map((color, i) => ({
            offset: i / (designData.background.colors.length - 1),
            color: color
          }))
        });
        canvas.setBackgroundColor(gradient, canvas.renderAll.bind(canvas));
      }
    }
    
    // Add elements
    for (const element of designData.elements) {
      await addElementToCanvas(canvas, element);
    }
  };

  // Add element to canvas
  const addElementToCanvas = async (canvas, element) => {
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
        if (element.placeholder) {
          // Create placeholder rectangle
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
          
          // Add placeholder text
          const placeholderText = new fabric.Text(element.label || 'Image', {
            left: (element.x || 100) + (element.width || 200) / 2,
            top: (element.y || 100) + (element.height || 150) / 2,
            fontSize: 16,
            fill: '#999999',
            textAlign: 'center',
            originX: 'center',
            originY: 'center'
          });
          
          canvas.add(fabricObject);
          canvas.add(placeholderText);
          return;
        }
        break;
    }
    
    if (fabricObject) {
      canvas.add(fabricObject);
    }
  };

  // Tool handlers
  const handleToolSelect = (tool) => {
    setActiveTool(tool);
    
    if (fabricCanvas) {
      fabricCanvas.isDrawingMode = false;
      fabricCanvas.selection = tool === 'select';
      
      if (tool === 'select') {
        fabricCanvas.defaultCursor = 'default';
      } else {
        fabricCanvas.defaultCursor = 'crosshair';
      }
    }
  };

  const addText = () => {
    if (!fabricCanvas) return;
    
    const text = new fabric.Textbox('New Text', {
      left: 100,
      top: 100,
      fontSize: textProperties.fontSize,
      fontFamily: textProperties.fontFamily,
      fontWeight: textProperties.fontWeight,
      fontStyle: textProperties.fontStyle,
      fill: textProperties.fill,
      textAlign: textProperties.textAlign,
      width: 200
    });
    
    fabricCanvas.add(text);
    fabricCanvas.setActiveObject(text);
    fabricCanvas.renderAll();
    setShowTextPanel(true);
  };

  const addShape = (shapeType) => {
    if (!fabricCanvas) return;
    
    let shape;
    
    if (shapeType === 'rectangle') {
      shape = new fabric.Rect({
        left: 100,
        top: 100,
        width: 100,
        height: 100,
        fill: currentColor,
        stroke: '#000000',
        strokeWidth: 1
      });
    } else if (shapeType === 'circle') {
      shape = new fabric.Circle({
        left: 100,
        top: 100,
        radius: 50,
        fill: currentColor,
        stroke: '#000000',
        strokeWidth: 1
      });
    }
    
    if (shape) {
      fabricCanvas.add(shape);
      fabricCanvas.setActiveObject(shape);
      fabricCanvas.renderAll();
    }
  };

  const addImage = async (imageUrl) => {
    if (!fabricCanvas) return;
    
    try {
      const img = await new Promise((resolve, reject) => {
        fabric.Image.fromURL(imageUrl, resolve, { crossOrigin: 'anonymous' });
      });
      
      img.set({
        left: 100,
        top: 100,
        scaleX: 0.5,
        scaleY: 0.5
      });
      
      fabricCanvas.add(img);
      fabricCanvas.setActiveObject(img);
      fabricCanvas.renderAll();
    } catch (err) {
      console.error('Error adding image:', err);
    }
  };

  // Object manipulation
  const deleteSelected = () => {
    if (!fabricCanvas || !selectedObject) return;
    
    fabricCanvas.remove(selectedObject);
    fabricCanvas.renderAll();
    setSelectedObject(null);
  };

  const duplicateSelected = () => {
    if (!fabricCanvas || !selectedObject) return;
    
    selectedObject.clone((cloned) => {
      cloned.set({
        left: selectedObject.left + 20,
        top: selectedObject.top + 20
      });
      fabricCanvas.add(cloned);
      fabricCanvas.setActiveObject(cloned);
      fabricCanvas.renderAll();
    });
  };

  // Alignment functions
  const alignObject = (alignment) => {
    if (!fabricCanvas || !selectedObject) return;
    
    const canvasWidth = fabricCanvas.width;
    const canvasHeight = fabricCanvas.height;
    const objectWidth = selectedObject.getScaledWidth();
    const objectHeight = selectedObject.getScaledHeight();
    
    switch (alignment) {
      case 'left':
        selectedObject.set({ left: 0 });
        break;
      case 'center':
        selectedObject.set({ left: (canvasWidth - objectWidth) / 2 });
        break;
      case 'right':
        selectedObject.set({ left: canvasWidth - objectWidth });
        break;
      case 'top':
        selectedObject.set({ top: 0 });
        break;
      case 'middle':
        selectedObject.set({ top: (canvasHeight - objectHeight) / 2 });
        break;
      case 'bottom':
        selectedObject.set({ top: canvasHeight - objectHeight });
        break;
    }
    
    fabricCanvas.renderAll();
  };

  // Text property updates
  const updateTextProperty = (property, value) => {
    if (!selectedObject || selectedObject.type !== 'textbox') return;
    
    selectedObject.set(property, value);
    fabricCanvas.renderAll();
    
    setTextProperties(prev => ({
      ...prev,
      [property]: value
    }));
  };

  // Zoom functions
  const zoomIn = () => {
    if (!fabricCanvas) return;
    const newZoom = Math.min(zoom * 1.2, 3);
    fabricCanvas.setZoom(newZoom);
    setZoom(newZoom);
  };

  const zoomOut = () => {
    if (!fabricCanvas) return;
    const newZoom = Math.max(zoom / 1.2, 0.1);
    fabricCanvas.setZoom(newZoom);
    setZoom(newZoom);
  };

  const resetZoom = () => {
    if (!fabricCanvas) return;
    fabricCanvas.setZoom(1);
    setZoom(1);
  };

  // Undo/Redo
  const undo = () => {
    if (historyIndex > 0) {
      const prevState = canvasHistory[historyIndex - 1];
      fabricCanvas.loadFromJSON(prevState, () => {
        fabricCanvas.renderAll();
        setHistoryIndex(historyIndex - 1);
      });
    }
  };

  const redo = () => {
    if (historyIndex < canvasHistory.length - 1) {
      const nextState = canvasHistory[historyIndex + 1];
      fabricCanvas.loadFromJSON(nextState, () => {
        fabricCanvas.renderAll();
        setHistoryIndex(historyIndex + 1);
      });
    }
  };

  // Save design
  const saveDesign = async () => {
    if (!fabricCanvas) return;
    
    try {
      setSaving(true);
      
      const designData = {
        version: '1.0',
        canvas: {
          width: fabricCanvas.width,
          height: fabricCanvas.height,
          backgroundColor: fabricCanvas.backgroundColor
        },
        elements: fabricCanvas.toJSON().objects
      };
      
      const previewUrl = fabricCanvas.toDataURL({
        format: 'png',
        quality: 0.8,
        multiplier: 0.5
      });
      
      const payload = {
        action: 'save',
        design_id: designId,
        request_id: requestId,
        design_data: designData,
        preview_url: previewUrl
      };
      
      const res = await fetch(`${API_BASE}/admin/template-editor.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Admin-User-Id': '1' // TODO: Get from auth context
        },
        body: JSON.stringify(payload)
      });
      
      const data = await res.json();
      
      if (data.status === 'success') {
        if (onSave) onSave(data.design_id);
        alert('Design saved successfully!');
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      console.error('Error saving design:', err);
      alert('Error saving design: ' + err.message);
    } finally {
      setSaving(false);
    }
  };

  // Export design
  const exportDesign = () => {
    if (!fabricCanvas) return;
    
    const dataURL = fabricCanvas.toDataURL({
      format: 'png',
      quality: 1.0,
      multiplier: 2
    });
    
    const link = document.createElement('a');
    link.download = `design_${Date.now()}.png`;
    link.href = dataURL;
    link.click();
  };

  // Complete design
  const completeDesign = async () => {
    if (!fabricCanvas) return;
    
    try {
      setSaving(true);
      
      const finalImageUrl = fabricCanvas.toDataURL({
        format: 'png',
        quality: 1.0,
        multiplier: 2
      });
      
      const payload = {
        action: 'complete',
        design_id: designId,
        request_id: requestId,
        final_image_url: finalImageUrl,
        export_format: 'png',
        export_quality: 'high'
      };
      
      const res = await fetch(`${API_BASE}/admin/template-editor.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Admin-User-Id': '1' // TODO: Get from auth context
        },
        body: JSON.stringify(payload)
      });
      
      const data = await res.json();
      
      if (data.status === 'success') {
        if (onComplete) onComplete(data.request_id, finalImageUrl);
        alert('Design completed successfully!');
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      console.error('Error completing design:', err);
      alert('Error completing design: ' + err.message);
    } finally {
      setSaving(false);
    }
  };

  if (!isOpen && !inline) return null;

  return (
    <div className={`template-editor ${inline ? 'inline-editor' : 'modal-editor'}`}>
      {!inline && (
        <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
          <div className="bg-white rounded-lg shadow-xl w-full h-full max-w-7xl max-h-[95vh] flex flex-col">
            <div className="flex items-center justify-between p-4 border-b">
              <h2 className="text-xl font-semibold">Template Editor</h2>
              <button
                onClick={onClose}
                className="p-2 hover:bg-gray-100 rounded-lg"
              >
                <LuX className="w-5 h-5" />
              </button>
            </div>
            <div className="flex-1 flex overflow-hidden">
              <EditorContent />
            </div>
          </div>
        </div>
      )}
      
      {inline && <EditorContent />}
    </div>
  );

  function EditorContent() {
    return (
      <>
        {/* Toolbar */}
        <div className="w-16 bg-gray-100 border-r flex flex-col items-center py-4 space-y-2">
          <ToolButton
            icon={LuMove}
            active={activeTool === 'select'}
            onClick={() => handleToolSelect('select')}
            tooltip="Select"
          />
          <ToolButton
            icon={LuType}
            active={activeTool === 'text'}
            onClick={addText}
            tooltip="Add Text"
          />
          <ToolButton
            icon={LuSquare}
            active={activeTool === 'rectangle'}
            onClick={() => addShape('rectangle')}
            tooltip="Rectangle"
          />
          <ToolButton
            icon={LuCircle}
            active={activeTool === 'circle'}
            onClick={() => addShape('circle')}
            tooltip="Circle"
          />
          <ToolButton
            icon={LuImage}
            active={activeTool === 'image'}
            onClick={() => document.getElementById('image-upload').click()}
            tooltip="Add Image"
          />
          
          <div className="border-t border-gray-300 w-8 my-2"></div>
          
          <ToolButton
            icon={LuUndo}
            onClick={undo}
            disabled={historyIndex <= 0}
            tooltip="Undo"
          />
          <ToolButton
            icon={LuRedo}
            onClick={redo}
            disabled={historyIndex >= canvasHistory.length - 1}
            tooltip="Redo"
          />
          
          <div className="border-t border-gray-300 w-8 my-2"></div>
          
          <ToolButton
            icon={LuZoomIn}
            onClick={zoomIn}
            tooltip="Zoom In"
          />
          <ToolButton
            icon={LuZoomOut}
            onClick={zoomOut}
            tooltip="Zoom Out"
          />
          <button
            onClick={resetZoom}
            className="text-xs px-2 py-1 bg-gray-200 rounded hover:bg-gray-300"
            title="Reset Zoom"
          >
            {Math.round(zoom * 100)}%
          </button>
        </div>

        {/* Canvas Area */}
        <div className="flex-1 flex flex-col">
          {/* Top Controls */}
          <div className="bg-white border-b p-3 flex items-center justify-between">
            <div className="flex items-center space-x-4">
              {selectedObject && (
                <>
                  <button
                    onClick={duplicateSelected}
                    className="flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
                  >
                    <LuCopy className="w-4 h-4 mr-1" />
                    Duplicate
                  </button>
                  <button
                    onClick={deleteSelected}
                    className="flex items-center px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200"
                  >
                    <LuTrash2 className="w-4 h-4 mr-1" />
                    Delete
                  </button>
                  
                  <div className="border-l border-gray-300 h-6 mx-2"></div>
                  
                  <div className="flex items-center space-x-1">
                    <ToolButton
                      icon={LuAlignLeft}
                      onClick={() => alignObject('left')}
                      tooltip="Align Left"
                      size="sm"
                    />
                    <ToolButton
                      icon={LuAlignCenter}
                      onClick={() => alignObject('center')}
                      tooltip="Align Center"
                      size="sm"
                    />
                    <ToolButton
                      icon={LuAlignRight}
                      onClick={() => alignObject('right')}
                      tooltip="Align Right"
                      size="sm"
                    />
                  </div>
                </>
              )}
            </div>
            
            <div className="flex items-center space-x-2">
              <button
                onClick={saveDesign}
                disabled={saving}
                className="flex items-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
              >
                <LuSave className="w-4 h-4 mr-2" />
                {saving ? 'Saving...' : 'Save'}
              </button>
              <button
                onClick={exportDesign}
                className="flex items-center px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
              >
                <LuDownload className="w-4 h-4 mr-2" />
                Export
              </button>
              {requestId && (
                <button
                  onClick={completeDesign}
                  disabled={saving}
                  className="flex items-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 disabled:opacity-50"
                >
                  <LuEye className="w-4 h-4 mr-2" />
                  Complete
                </button>
              )}
            </div>
          </div>

          {/* Canvas Container */}
          <div className="flex-1 bg-gray-50 p-4 overflow-auto">
            <div className="flex items-center justify-center min-h-full">
              <div className="bg-white shadow-lg rounded-lg p-4">
                {loading && (
                  <div className="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                  </div>
                )}
                <canvas ref={canvasRef} />
              </div>
            </div>
          </div>
        </div>

        {/* Properties Panel */}
        {selectedObject && (
          <div className="w-80 bg-white border-l flex flex-col">
            <div className="p-4 border-b">
              <h3 className="font-semibold">Properties</h3>
            </div>
            
            <div className="flex-1 overflow-auto p-4 space-y-4">
              {selectedObject.type === 'textbox' && (
                <TextProperties
                  properties={textProperties}
                  onChange={updateTextProperty}
                />
              )}
              
              {(selectedObject.type === 'rect' || selectedObject.type === 'circle') && (
                <ShapeProperties
                  object={selectedObject}
                  onChange={(prop, value) => {
                    selectedObject.set(prop, value);
                    fabricCanvas.renderAll();
                  }}
                />
              )}
              
              <ObjectProperties
                object={selectedObject}
                onChange={(prop, value) => {
                  selectedObject.set(prop, value);
                  fabricCanvas.renderAll();
                }}
              />
            </div>
          </div>
        )}

        {/* Hidden file input */}
        <input
          id="image-upload"
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(e) => {
            const file = e.target.files[0];
            if (file) {
              const reader = new FileReader();
              reader.onload = (event) => {
                addImage(event.target.result);
              };
              reader.readAsDataURL(file);
            }
          }}
        />
      </>
    );
  }
}

// Tool Button Component
function ToolButton({ icon: Icon, active, onClick, disabled, tooltip, size = 'md' }) {
  const sizeClasses = {
    sm: 'w-8 h-8',
    md: 'w-10 h-10'
  };
  
  return (
    <button
      onClick={onClick}
      disabled={disabled}
      className={`${sizeClasses[size]} flex items-center justify-center rounded-lg transition-colors ${
        active 
          ? 'bg-blue-600 text-white' 
          : 'bg-white text-gray-600 hover:bg-gray-200'
      } ${disabled ? 'opacity-50 cursor-not-allowed' : ''}`}
      title={tooltip}
    >
      <Icon className="w-5 h-5" />
    </button>
  );
}

// Text Properties Component
function TextProperties({ properties, onChange }) {
  return (
    <div className="space-y-3">
      <h4 className="font-medium text-gray-900">Text Properties</h4>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Font Size</label>
        <input
          type="number"
          value={properties.fontSize}
          onChange={(e) => onChange('fontSize', parseInt(e.target.value))}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
          min="8"
          max="200"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Font Family</label>
        <select
          value={properties.fontFamily}
          onChange={(e) => onChange('fontFamily', e.target.value)}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
        >
          <option value="Arial">Arial</option>
          <option value="Georgia">Georgia</option>
          <option value="Times New Roman">Times New Roman</option>
          <option value="Helvetica">Helvetica</option>
          <option value="Playfair Display">Playfair Display</option>
        </select>
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Color</label>
        <input
          type="color"
          value={properties.fill}
          onChange={(e) => onChange('fill', e.target.value)}
          className="w-full h-10 border border-gray-300 rounded-md"
        />
      </div>
      
      <div className="flex space-x-2">
        <button
          onClick={() => onChange('fontWeight', properties.fontWeight === 'bold' ? 'normal' : 'bold')}
          className={`flex-1 py-2 px-3 rounded-md border ${
            properties.fontWeight === 'bold' 
              ? 'bg-blue-100 border-blue-300 text-blue-700' 
              : 'bg-white border-gray-300'
          }`}
        >
          <LuBold className="w-4 h-4 mx-auto" />
        </button>
        <button
          onClick={() => onChange('fontStyle', properties.fontStyle === 'italic' ? 'normal' : 'italic')}
          className={`flex-1 py-2 px-3 rounded-md border ${
            properties.fontStyle === 'italic' 
              ? 'bg-blue-100 border-blue-300 text-blue-700' 
              : 'bg-white border-gray-300'
          }`}
        >
          <LuItalic className="w-4 h-4 mx-auto" />
        </button>
        <button
          onClick={() => onChange('textDecoration', properties.textDecoration === 'underline' ? '' : 'underline')}
          className={`flex-1 py-2 px-3 rounded-md border ${
            properties.textDecoration === 'underline' 
              ? 'bg-blue-100 border-blue-300 text-blue-700' 
              : 'bg-white border-gray-300'
          }`}
        >
          <LuUnderline className="w-4 h-4 mx-auto" />
        </button>
      </div>
    </div>
  );
}

// Shape Properties Component
function ShapeProperties({ object, onChange }) {
  return (
    <div className="space-y-3">
      <h4 className="font-medium text-gray-900">Shape Properties</h4>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Fill Color</label>
        <input
          type="color"
          value={object.fill || '#cccccc'}
          onChange={(e) => onChange('fill', e.target.value)}
          className="w-full h-10 border border-gray-300 rounded-md"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Stroke Color</label>
        <input
          type="color"
          value={object.stroke || '#000000'}
          onChange={(e) => onChange('stroke', e.target.value)}
          className="w-full h-10 border border-gray-300 rounded-md"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Stroke Width</label>
        <input
          type="number"
          value={object.strokeWidth || 0}
          onChange={(e) => onChange('strokeWidth', parseInt(e.target.value))}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
          min="0"
          max="20"
        />
      </div>
    </div>
  );
}

// Object Properties Component
function ObjectProperties({ object, onChange }) {
  return (
    <div className="space-y-3">
      <h4 className="font-medium text-gray-900">Position & Size</h4>
      
      <div className="grid grid-cols-2 gap-2">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">X</label>
          <input
            type="number"
            value={Math.round(object.left || 0)}
            onChange={(e) => onChange('left', parseInt(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Y</label>
          <input
            type="number"
            value={Math.round(object.top || 0)}
            onChange={(e) => onChange('top', parseInt(e.target.value))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
      </div>
      
      <div className="grid grid-cols-2 gap-2">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Width</label>
          <input
            type="number"
            value={Math.round(object.getScaledWidth?.() || object.width || 0)}
            onChange={(e) => {
              const newWidth = parseInt(e.target.value);
              if (object.type === 'textbox') {
                onChange('width', newWidth);
              } else {
                const scaleX = newWidth / (object.width || 1);
                onChange('scaleX', scaleX);
              }
            }}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Height</label>
          <input
            type="number"
            value={Math.round(object.getScaledHeight?.() || object.height || 0)}
            onChange={(e) => {
              const newHeight = parseInt(e.target.value);
              if (object.type === 'textbox') {
                // Textbox height is auto-calculated
              } else {
                const scaleY = newHeight / (object.height || 1);
                onChange('scaleY', scaleY);
              }
            }}
            className="w-full px-3 py-2 border border-gray-300 rounded-md"
            disabled={object.type === 'textbox'}
          />
        </div>
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Rotation</label>
        <input
          type="number"
          value={Math.round(object.angle || 0)}
          onChange={(e) => onChange('angle', parseInt(e.target.value))}
          className="w-full px-3 py-2 border border-gray-300 rounded-md"
          min="-180"
          max="180"
        />
      </div>
      
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-1">Opacity</label>
        <input
          type="range"
          value={(object.opacity || 1) * 100}
          onChange={(e) => onChange('opacity', parseInt(e.target.value) / 100)}
          className="w-full"
          min="0"
          max="100"
        />
        <div className="text-center text-sm text-gray-500">
          {Math.round((object.opacity || 1) * 100)}%
        </div>
      </div>
    </div>
  );
}