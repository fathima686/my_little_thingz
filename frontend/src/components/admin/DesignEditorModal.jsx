import React, { useState, useEffect, useRef } from 'react';
import { LuX, LuDownload, LuSave, LuUpload, LuImage, LuType, LuSquare, LuRotateCw, LuScissors, LuFileText, LuPlus, LuUndo, LuRedo } from 'react-icons/lu';
import TemplateGallery from './TemplateGallery';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

export default function DesignEditorModal({ requestId, isOpen, onClose, onComplete, inline = false, customerImages = null }) {
  const [showTemplateSelection, setShowTemplateSelection] = useState(true);
  const [templates, setTemplates] = useState([]);
  const [groupedTemplates, setGroupedTemplates] = useState({});
  const [selectedCategory, setSelectedCategory] = useState('Frame'); // Default to Frame category
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const [templateLayout, setTemplateLayout] = useState(null); // Grid layout: '1x1', '2x2', '3x3', etc.
  const [canvas, setCanvas] = useState(null);
  const [fabricCanvas, setFabricCanvas] = useState(null);
  const [designId, setDesignId] = useState(null);
  const [loading, setLoading] = useState(false);
  const [activeTool, setActiveTool] = useState(null);
  const [templateImageSlots, setTemplateImageSlots] = useState({}); // Store images for each grid slot

  // Undo / Redo history for Fabric canvas
  const historyRef = useRef([]);
  const redoRef = useRef([]);
  const isRestoringRef = useRef(false);
  const historyAttachedRef = useRef(false);
  const MAX_HISTORY = 50;

  useEffect(() => {
    if (isOpen && showTemplateSelection) {
      loadTemplates();
    }
  }, [isOpen, showTemplateSelection]);

  useEffect(() => {
    if (isOpen && !showTemplateSelection && selectedTemplate && !fabricCanvas) {
      console.log('useEffect: Initializing canvas, customerImages:', customerImages?.length || 0);
      initializeCanvas();
    }
  }, [isOpen, showTemplateSelection, selectedTemplate, customerImages]);

  const loadTemplates = async () => {
    try {
      const res = await fetch(`${API_BASE}/admin/design-templates.php`);
      const data = await res.json();
      if (data.status === 'success') {
        setTemplates(data.templates || []);
        // Use grouped templates if available, otherwise group them ourselves
        if (data.grouped) {
          setGroupedTemplates(data.grouped);
        } else {
          // Group templates by category
          const grouped = {};
          (data.templates || []).forEach(template => {
            const cat = template.category || 'Other';
            if (!grouped[cat]) {
              grouped[cat] = [];
            }
            grouped[cat].push(template);
          });
          setGroupedTemplates(grouped);
        }
      }
    } catch (err) {
      console.error('Error loading templates:', err);
    }
  };

  // Category order for display (Canva-like organization)
  const categoryOrder = ['Frame', 'Photo', 'Polaroid', 'Card', 'Social', 'Document', 'Poster'];
  const categoryLabels = {
    'Frame': 'Photo Frames',
    'Photo': 'Photo Prints',
    'Polaroid': 'Polaroid',
    'Card': 'Cards',
    'Social': 'Social Media',
    'Document': 'Documents',
    'Poster': 'Posters'
  };

  const selectTemplate = async (template) => {
    setLoading(true);
    try {
      const res = await fetch(`${API_BASE}/admin/design-templates.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          request_id: requestId,
          template_id: template.id
        })
      });
      const data = await res.json();
      if (data.status === 'success') {
        setSelectedTemplate(template);
        setDesignId(data.design_id);
        setShowTemplateSelection(false);
        console.log('Template selected, customerImages available:', customerImages?.length || 0);
        console.log('Template layout:', templateLayout, 'Image slots:', templateImageSlots);
      }
    } catch (err) {
      alert('Error selecting template: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleImageSlotSelect = (slotIndex) => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      
      const reader = new FileReader();
      reader.onload = (event) => {
        setTemplateImageSlots(prev => ({
          ...prev,
          [slotIndex]: event.target.result
        }));
      };
      reader.readAsDataURL(file);
    };
    input.click();
  };

  // Grid Template Preview Component
  const GridTemplatePreview = ({ layout, template, onImageSelect, selectedImages }) => {
    const [rows, cols] = layout.split('×').map(Number);
    const totalSlots = rows * cols;
    
    return (
      <div style={{
        display: 'grid',
        gridTemplateColumns: `repeat(${cols}, 1fr)`,
        gridTemplateRows: `repeat(${rows}, 1fr)`,
        gap: 8,
        aspectRatio: `${cols} / ${rows}`,
        maxHeight: 300
      }}>
        {Array(totalSlots).fill(0).map((_, index) => (
          <div
            key={index}
            style={{
              border: '2px dashed #ccc',
              borderRadius: 4,
              position: 'relative',
              background: selectedImages[index] ? `url(${selectedImages[index]}) center/cover` : '#f9f9f9',
              cursor: 'pointer',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              minHeight: 60
            }}
            onClick={() => onImageSelect(index)}
          >
            {selectedImages[index] ? (
              <div style={{
                position: 'absolute',
                top: 4,
                right: 4,
                background: 'rgba(0,0,0,0.6)',
                color: 'white',
                borderRadius: '50%',
                width: 24,
                height: 24,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
                fontSize: 12
              }}>
                <LuX />
              </div>
            ) : (
              <LuPlus size={24} style={{ color: '#999' }} />
            )}
          </div>
        ))}
      </div>
    );
  };

  const initializeCanvas = () => {
    // Load Fabric.js dynamically
    if (typeof window.fabric === 'undefined') {
      const script = document.createElement('script');
      script.src = 'https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js';
      script.onload = () => setupCanvas();
      document.body.appendChild(script);
    } else {
      setupCanvas();
    }
  };

  const setupCanvas = () => {
    const canvasEl = document.getElementById('design-canvas');
    if (!canvasEl || !selectedTemplate) {
      console.error('Canvas element or template not found');
      return;
    }

    console.log('Setting up canvas with template:', selectedTemplate);
    console.log('Template layout:', templateLayout);
    console.log('Template image slots:', templateImageSlots);

    const fabric = window.fabric;
    const fc = new fabric.Canvas('design-canvas', {
      width: Math.min(selectedTemplate.width, 800),
      height: Math.min(selectedTemplate.height, 600),
      backgroundColor: selectedTemplate.background_color || '#FFFFFF'
    });

    setFabricCanvas(fc);

    // Set actual canvas dimensions for export
    canvasEl.width = selectedTemplate.width;
    canvasEl.height = selectedTemplate.height;
    fc.setDimensions({
      width: Math.min(selectedTemplate.width, 800),
      height: Math.min(selectedTemplate.height, 600)
    });

    // Wait a bit for canvas to be fully initialized
    setTimeout(() => {
      // Load existing design if available
      loadExistingDesign(fc).then((hasDesign) => {
        if (!hasDesign) {
          console.log('No existing design found, loading images...');
          
          // If we have a grid layout with image slots, load those first
          if (templateLayout && Object.keys(templateImageSlots).length > 0) {
            setTimeout(() => {
              loadGridLayoutImages(fc);
            }, 300);
          } else {
            // Otherwise, load customer reference images
            setTimeout(() => {
              loadCustomerImagesWithData(fc, customerImages);
            }, 300);
          }
        } else {
          console.log('Existing design loaded, skipping new images');
        }

        // Attach history tracking once design (or blank state) is ready
        if (!historyAttachedRef.current) {
          attachHistory(fc);
          historyAttachedRef.current = true;
        }
      });
    }, 100);
  };

  const saveSnapshot = (fc) => {
    if (!fc || isRestoringRef.current) return;
    try {
      const json = fc.toJSON();
      const serialized = JSON.stringify(json);
      const history = historyRef.current;
      if (history.length && history[history.length - 1] === serialized) {
        return;
      }
      history.push(serialized);
      if (history.length > MAX_HISTORY) {
        history.shift();
      }
      // Whenever we create a new snapshot, clear redo stack
      redoRef.current = [];
    } catch (e) {
      console.error('History snapshot failed:', e);
    }
  };

  const attachHistory = (fc) => {
    // Initial snapshot
    saveSnapshot(fc);

    const handler = () => saveSnapshot(fc);
    fc.on('object:added', handler);
    fc.on('object:modified', handler);
    fc.on('object:removed', handler);
    fc.on('text:changed', handler);
  };

  const restoreFrom = (fc, serialized) => {
    if (!fc || !serialized) return;
    isRestoringRef.current = true;
    try {
      const json = JSON.parse(serialized);
      fc.loadFromJSON(json, () => {
        fc.renderAll();
        isRestoringRef.current = false;
      });
    } catch (e) {
      console.error('Error restoring canvas state:', e);
      isRestoringRef.current = false;
    }
  };

  const undo = () => {
    const fc = fabricCanvas;
    if (!fc) return;
    const history = historyRef.current;
    const redoStack = redoRef.current;
    if (history.length <= 1) return;
    const current = history.pop();
    redoStack.push(current);
    const previous = history[history.length - 1];
    restoreFrom(fc, previous);
  };

  const redo = () => {
    const fc = fabricCanvas;
    if (!fc) return;
    const history = historyRef.current;
    const redoStack = redoRef.current;
    if (!redoStack.length) return;
    const next = redoStack.pop();
    history.push(next);
    restoreFrom(fc, next);
  };

  const loadGridLayoutImages = (fc) => {
    if (!templateLayout || !fc) return;
    
    const [rows, cols] = templateLayout.split('×').map(Number);
    const slotWidth = fc.width / cols;
    const slotHeight = fc.height / rows;
    const padding = 4;
    
    const fabric = window.fabric;
    
    Object.keys(templateImageSlots).forEach(slotIndex => {
      const slotNum = parseInt(slotIndex);
      const row = Math.floor(slotNum / cols);
      const col = slotNum % cols;
      const imageUrl = templateImageSlots[slotIndex];
      
      fabric.Image.fromURL(imageUrl, (fabricImg) => {
        if (!fabricImg) return;
        
        // Calculate position and size for this slot
        const left = col * slotWidth + padding;
        const top = row * slotHeight + padding;
        const maxWidth = slotWidth - (padding * 2);
        const maxHeight = slotHeight - (padding * 2);
        
        // Scale image to fit within the slot
        const scale = Math.min(maxWidth / fabricImg.width, maxHeight / fabricImg.height, 1);
        
        fabricImg.set({
          left: left + (maxWidth - fabricImg.width * scale) / 2,
          top: top + (maxHeight - fabricImg.height * scale) / 2,
          scaleX: scale,
          scaleY: scale,
          selectable: true,
          evented: true,
          hasControls: true,
          hasBorders: true
        });
        
        fc.add(fabricImg);
        fc.renderAll();
        
        console.log(`Loaded image into slot ${slotIndex} at position (${row}, ${col})`);
      }, { crossOrigin: 'anonymous' });
    });
  };

  const loadCustomerImagesWithData = async (fc, images) => {
    if (!requestId || !fc) {
      console.error('Cannot load images: missing requestId or fabricCanvas');
      return;
    }
    
    console.log('loadCustomerImagesWithData called');
    console.log('Canvas dimensions:', { width: fc.width, height: fc.height });
    console.log('Images provided:', images?.length || 0);
    
    let imagesToLoad = [];
    
    // Use provided images if available, otherwise fetch from API
    if (images && Array.isArray(images) && images.length > 0) {
      console.log('Using provided customer images:', images.length);
      imagesToLoad = images
        .filter(img => {
          const url = typeof img === 'string' ? img : (img.url || img.image_url || img.image_path || '');
          if (!url) {
            console.warn('Skipping image without URL:', img);
            return false;
          }
          return true;
        })
        .map(img => ({
          url: typeof img === 'string' ? img : (img.url || img.image_url || img.image_path || ''),
          filename: typeof img === 'string' ? 'image' : (img.filename || img.original_filename || img.original_name || 'image')
        }));
      console.log('Processed images to load:', imagesToLoad);
    } else {
      console.log('No images provided, fetching from API...');
      try {
        const res = await fetch(`${API_BASE}/admin/get-request-images.php?request_id=${requestId}`);
        const data = await res.json();
        
        if (data.status === 'success' && data.images && data.images.length > 0) {
          console.log('Found customer images from API:', data.images.length);
          imagesToLoad = data.images.filter(img => img.url);
        } else {
          console.log('No customer images found for this request');
          alert('No reference images found for this request. You can add images manually.');
          return;
        }
      } catch (err) {
        console.error('Error loading customer images:', err);
        alert('Error loading reference images: ' + err.message);
        return;
      }
    }
    
    if (imagesToLoad.length === 0) {
      console.log('No images to load after filtering');
      alert('No valid images found to load. Please check the image URLs.');
      return;
    }
    
    console.log('Loading', imagesToLoad.length, 'images onto canvas...');
    
    const fabric = window.fabric;
    
    // Load each image onto the canvas
    imagesToLoad.forEach((img, index) => {
      if (!img.url) {
        console.warn('Skipping image without URL at index', index);
        return;
      }
      
      console.log(`Loading image ${index + 1}/${imagesToLoad.length}:`, img.url);
      
      fabric.Image.fromURL(img.url, (fabricImg) => {
        if (!fabricImg) {
          console.error('Failed to load image:', img.url);
          return;
        }
        
        console.log('Image loaded successfully. Original size:', { width: fabricImg.width, height: fabricImg.height });
        
        // Scale image to fit canvas (max 60% of canvas size)
        const maxWidth = fc.width * 0.6;
        const maxHeight = fc.height * 0.6;
        
        let scale = 1;
        if (fabricImg.width > maxWidth || fabricImg.height > maxHeight) {
          scale = Math.min(maxWidth / fabricImg.width, maxHeight / fabricImg.height);
          console.log('Scaling image by:', scale);
        }
        
        // Center first image, offset others slightly
        const offsetX = index * 30;
        const offsetY = index * 30;
        
        const leftPos = (fc.width / 2) + offsetX - (fabricImg.width * scale / 2);
        const topPos = (fc.height / 2) + offsetY - (fabricImg.height * scale / 2);
        
        console.log('Positioning image at:', { left: leftPos, top: topPos });
        
        fabricImg.set({
          left: leftPos,
          top: topPos,
          scaleX: scale,
          scaleY: scale,
          selectable: true,
          evented: true,
          hasControls: true,
          hasBorders: true
        });
        
        fc.add(fabricImg);
        fc.setActiveObject(fabricImg);
        fc.renderAll();
        
        console.log('✓ Added customer image to canvas:', img.filename || 'image ' + (index + 1));
      }, {
        crossOrigin: 'anonymous'
      });
    });
  };

  const loadExistingDesign = async (fc) => {
    if (!designId) {
      // No design ID means this is a new design, check if there's any saved design
      try {
        const res = await fetch(`${API_BASE}/admin/get-design.php?request_id=${requestId}`);
        const data = await res.json();
        if (data.status === 'success' && data.latest && data.latest.canvas_data) {
          const canvasData = JSON.parse(data.latest.canvas_data);
          return new Promise((resolve) => {
            fc.loadFromJSON(canvasData, () => {
              fc.renderAll();
              resolve(true);
            });
          });
        }
      } catch (err) {
        console.error('Error loading latest design:', err);
      }
      return Promise.resolve(false);
    }
    
    try {
      const res = await fetch(`${API_BASE}/admin/get-design.php?request_id=${requestId}&design_id=${designId}`);
      const data = await res.json();
      if (data.status === 'success' && data.design && data.design.canvas_data) {
        const canvasData = JSON.parse(data.design.canvas_data);
        return new Promise((resolve) => {
          fc.loadFromJSON(canvasData, () => {
            fc.renderAll();
            resolve(true);
          });
        });
      }
    } catch (err) {
      console.error('Error loading design:', err);
    }
    return Promise.resolve(false);
  };

  const loadCustomerImages = async (fc) => {
    // Simply call the function that explicitly receives images parameter
    // This ensures we use the latest customerImages prop value
    return loadCustomerImagesWithData(fc, customerImages);
  };

  const addText = () => {
    if (!fabricCanvas) return;
    const fabric = window.fabric;
    const text = new fabric.IText('Double click to edit', {
      left: 100,
      top: 100,
      fontSize: 24,
      fontFamily: 'Arial',
      fill: '#000000'
    });
    fabricCanvas.add(text);
    fabricCanvas.setActiveObject(text);
    text.enterEditing();
    fabricCanvas.renderAll();
  };

  const addImage = () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (event) => {
        const fabric = window.fabric;
        fabric.Image.fromURL(event.target.result, (img) => {
          img.set({
            left: 100,
            top: 100,
            scaleX: Math.min(300 / img.width, 1),
            scaleY: Math.min(300 / img.height, 1)
          });
          fabricCanvas.add(img);
          fabricCanvas.setActiveObject(img);
          fabricCanvas.renderAll();
        });
      };
      reader.readAsDataURL(file);
    };
    input.click();
  };

  const rotateObject = () => {
    if (!fabricCanvas) return;
    const obj = fabricCanvas.getActiveObject();
    if (obj) {
      obj.rotate((obj.angle || 0) + 90);
      fabricCanvas.renderAll();
    }
  };

  const deleteSelected = () => {
    if (!fabricCanvas) return;
    const activeObjects = fabricCanvas.getActiveObjects();
    activeObjects.forEach(obj => fabricCanvas.remove(obj));
    fabricCanvas.discardActiveObject();
    fabricCanvas.renderAll();
  };

  const addShape = (shapeType) => {
    if (!fabricCanvas) return;
    const fabric = window.fabric;
    let shape;
    
    switch (shapeType) {
      case 'rect':
        shape = new fabric.Rect({
          left: 100,
          top: 100,
          width: 100,
          height: 100,
          fill: '#000000'
        });
        break;
      case 'circle':
        shape = new fabric.Circle({
          left: 100,
          top: 100,
          radius: 50,
          fill: '#000000'
        });
        break;
      default:
        return;
    }
    fabricCanvas.add(shape);
    fabricCanvas.setActiveObject(shape);
    fabricCanvas.renderAll();
  };

  const addBackground = () => {
    if (!fabricCanvas || !selectedTemplate) return;
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
      const file = e.target.files?.[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (event) => {
        const fabric = window.fabric;
        fabric.Image.fromURL(event.target.result, (img) => {
          // Scale to fit canvas
          const scale = Math.max(
            fabricCanvas.width / img.width,
            fabricCanvas.height / img.height
          );
          img.set({
            left: 0,
            top: 0,
            scaleX: scale,
            scaleY: scale,
            selectable: false,
            evented: false,
            excludeFromExport: false
          });
          // Add to back
          fabricCanvas.add(img);
          fabricCanvas.sendToBack(img);
          fabricCanvas.renderAll();
        });
      };
      reader.readAsDataURL(file);
    };
    input.click();
  };

  const addFrame = () => {
    if (!fabricCanvas || !selectedTemplate) return;
    const fabric = window.fabric;
    
    // Create a decorative frame (rectangle with inner border effect)
    const frameWidth = fabricCanvas.width - 40;
    const frameHeight = fabricCanvas.height - 40;
    const frame = new fabric.Rect({
      left: 20,
      top: 20,
      width: frameWidth,
      height: frameHeight,
      fill: 'transparent',
      stroke: '#8B4513',
      strokeWidth: 20,
      rx: 10,
      ry: 10,
      selectable: true,
      evented: true
    });
    
    fabricCanvas.add(frame);
    fabricCanvas.setActiveObject(frame);
    fabricCanvas.renderAll();
  };

  const saveDesign = async (finalStatus = 'designing') => {
    if (!fabricCanvas || !selectedTemplate) return;
    setLoading(true);
    
    try {
      const canvasData = fabricCanvas.toJSON();
      const previewImage = fabricCanvas.toDataURL({ format: 'png', quality: 0.8 });
      const exportImage = fabricCanvas.toDataURL({ format: 'png', quality: 1.0 });
      
      const res = await fetch(`${API_BASE}/admin/save-design.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          request_id: requestId,
          design_id: designId,
          canvas_data: canvasData,
          preview_image: previewImage,
          export_image: exportImage,
          status: finalStatus
        })
      });
      
      const data = await res.json();
      if (data.status === 'success') {
        if (finalStatus === 'design_completed' && onComplete) {
          onComplete();
        }
        alert('Design saved successfully!');
      } else {
        throw new Error(data.message || 'Failed to save design');
      }
    } catch (err) {
      alert('Error saving design: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const completeDesign = () => {
    if (confirm('Mark this design as completed? This will update the request status.')) {
      saveDesign('design_completed');
    }
  };

  const downloadImage = () => {
    if (!fabricCanvas) return;
    const dataURL = fabricCanvas.toDataURL({ format: 'png', quality: 1.0 });
    const link = document.createElement('a');
    link.download = `design_request_${requestId}.png`;
    link.href = dataURL;
    link.click();
  };

  if (!isOpen) return null;

  // Inline mode - render directly without modal overlay
  if (inline) {
    return (
      <div style={{
        display: 'flex',
        flexDirection: 'column',
        height: '100%',
        minHeight: 600,
        maxHeight: 'calc(100vh - 150px)',
        background: 'white',
        overflow: 'hidden'
      }}>
        {/* Header */}
        <div style={{
          padding: '16px 20px',
          borderBottom: '1px solid #eee',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center',
          background: '#f9f9f9',
          flexShrink: 0
        }}>
          <h3 style={{ margin: 0 }}>{showTemplateSelection ? 'Select Template' : 'Design Editor'}</h3>
          {requestId && (
            <div style={{ fontSize: 14, color: '#666' }}>
              Request ID: <strong>#{requestId}</strong>
            </div>
          )}
        </div>

        {/* Template Selection - Using Canva-style TemplateGallery */}
        {showTemplateSelection ? (
          <div style={{ flex: 1, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
            <TemplateGallery
              inline={true}
              onSelectTemplate={async (template) => {
                try {
                  // Use template-gallery.php API to track usage
                  const res = await fetch(`${API_BASE}/admin/template-gallery.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                      action: 'use',
                      template_id: template.id,
                      request_id: requestId
                    })
                  });
                  const data = await res.json();

                  // Map template to format expected by editor
                  const mappedTemplate = {
                    ...template,
                    width: template.canvas_width || 800,
                    height: template.canvas_height || 600,
                    background_color: template.template_data?.background?.color || 
                                     (template.template_data?.background?.colors?.[0]) || 
                                     '#ffffff'
                  };

                  setSelectedTemplate(mappedTemplate);
                  if (data.status === 'success' && data.design_id) {
                    setDesignId(data.design_id);
                  }
                  setShowTemplateSelection(false);
                } catch (e) {
                  alert('Error selecting template: ' + e.message);
                }
              }}
              onCreateNew={() => {
                // "Create blank" option
                setSelectedTemplate({
                  width: 800,
                  height: 600,
                  background_color: '#ffffff'
                });
                setShowTemplateSelection(false);
              }}
            />
          </div>
        ) : (
          <>
            {/* Toolbar */}
            <div style={{
              padding: '12px 20px',
              borderBottom: '1px solid #eee',
              display: 'flex',
              gap: 8,
              flexWrap: 'wrap',
              alignItems: 'center',
              background: '#f5f5f5',
              flexShrink: 0,
              minHeight: 60
            }}>
              <div style={{ display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
                <button onClick={addText} className="btn btn-outline btn-sm">
                  <LuType /> Add Text
                </button>
                <button onClick={addImage} className="btn btn-outline btn-sm">
                  <LuImage /> Add Image
                </button>
                <button onClick={() => addShape('rect')} className="btn btn-outline btn-sm">
                  <LuSquare /> Rectangle
                </button>
                <button onClick={() => addShape('circle')} className="btn btn-outline btn-sm">
                  ⭕ Circle
                </button>
                <button onClick={addBackground} className="btn btn-outline btn-sm">
                  🖼️ Background
                </button>
                <button onClick={addFrame} className="btn btn-outline btn-sm">
                  🖼️ Add Frame
                </button>
                <button onClick={rotateObject} className="btn btn-outline btn-sm">
                  <LuRotateCw /> Rotate
                </button>
                <button onClick={deleteSelected} className="btn btn-outline btn-sm">
                  🗑️ Delete
                </button>
                <button onClick={undo} className="btn btn-outline btn-sm">
                  <LuUndo /> Undo
                </button>
                <button onClick={redo} className="btn btn-outline btn-sm">
                  <LuRedo /> Redo
                </button>
              </div>
              <div style={{ flex: 1, minWidth: 20 }} />
              <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                <button onClick={saveDesign} className="btn btn-primary btn-sm" disabled={loading}>
                  <LuSave /> Save
                </button>
                <button onClick={downloadImage} className="btn btn-outline btn-sm">
                  <LuDownload /> Download
                </button>
                <button onClick={completeDesign} className="btn btn-success btn-sm" disabled={loading}>
                  ✓ Complete Design
                </button>
              </div>
            </div>

            {/* Canvas Area */}
            <div style={{ 
              flex: 1, 
              overflow: 'auto', 
              padding: 20, 
              background: '#f5f5f5', 
              display: 'flex', 
              justifyContent: 'center', 
              alignItems: 'flex-start',
              minHeight: 0
            }}>
              <div style={{
                background: 'white',
                padding: 20,
                borderRadius: 8,
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
                margin: 'auto'
              }}>
                <canvas id="design-canvas" style={{ border: '1px solid #ddd', display: 'block' }} />
              </div>
            </div>
          </>
        )}

        {loading && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(255,255,255,0.8)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 18,
            zIndex: 1000
          }}>
            Loading...
          </div>
        )}
      </div>
    );
  }

  // Modal mode - original modal overlay
  return (
    <div style={{
      position: 'fixed',
      top: 0,
      left: 0,
      right: 0,
      bottom: 0,
      background: 'rgba(0,0,0,0.8)',
      zIndex: 10000,
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: 20
    }}>
      <div className="modal-content" style={{
        background: 'white',
        borderRadius: 8,
        maxWidth: '95%',
        maxHeight: '95%',
        width: showTemplateSelection ? 800 : 1200,
        height: showTemplateSelection ? 600 : 800,
        display: 'flex',
        flexDirection: 'column',
        overflow: 'hidden'
      }}>
        {/* Header */}
        <div style={{
          padding: '16px 20px',
          borderBottom: '1px solid #eee',
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'center'
        }}>
          <h3>{showTemplateSelection ? 'Select Template' : 'Design Editor'}</h3>
          <button onClick={onClose} style={{ border: 'none', background: 'none', cursor: 'pointer', fontSize: 24 }}>
            <LuX />
          </button>
        </div>

        {/* Template Selection */}
        {showTemplateSelection ? (
          <div style={{ flex: 1, overflow: 'auto', padding: 20 }}>
            <div style={{
              display: 'grid',
              gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
              gap: 20
            }}>
              {templates.map(template => (
                <div
                  key={template.id}
                  onClick={() => selectTemplate(template)}
                  style={{
                    border: '2px solid #ddd',
                    borderRadius: 8,
                    padding: 16,
                    cursor: 'pointer',
                    textAlign: 'center',
                    transition: 'all 0.2s',
                    background: selectedTemplate?.id === template.id ? '#e3f2fd' : 'white'
                  }}
                  onMouseEnter={(e) => e.currentTarget.style.borderColor = '#2196f3'}
                  onMouseLeave={(e) => e.currentTarget.style.borderColor = '#ddd'}
                >
                  <div style={{
                    width: '100%',
                    height: 120,
                    border: '1px solid #ddd',
                    background: template.background_color || '#fff',
                    marginBottom: 12,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    fontSize: 12,
                    color: '#666'
                  }}>
                    {template.orientation === 'portrait' ? '📄' : template.orientation === 'landscape' ? '📰' : '⬜'}
                  </div>
                  <div style={{ fontWeight: 'bold', marginBottom: 4 }}>{template.name}</div>
                  <div style={{ fontSize: 12, color: '#666' }}>
                    {template.width} × {template.height}px
                  </div>
                  {template.description && (
                    <div style={{ fontSize: 11, color: '#999', marginTop: 4 }}>
                      {template.description}
                    </div>
                  )}
                </div>
              ))}
            </div>
          </div>
        ) : (
          <>
            {/* Toolbar */}
            <div style={{
              padding: '12px 20px',
              borderBottom: '1px solid #eee',
              display: 'flex',
              gap: 8,
              flexWrap: 'wrap',
              background: '#f5f5f5'
            }}>
              <button onClick={addText} className="btn btn-outline btn-sm">
                <LuType /> Add Text
              </button>
              <button onClick={addImage} className="btn btn-outline btn-sm">
                <LuImage /> Add Image
              </button>
              <button onClick={() => addShape('rect')} className="btn btn-outline btn-sm">
                <LuSquare /> Rectangle
              </button>
              <button onClick={() => addShape('circle')} className="btn btn-outline btn-sm">
                ⭕ Circle
              </button>
              <button onClick={addBackground} className="btn btn-outline btn-sm">
                🖼️ Background
              </button>
              <button onClick={addFrame} className="btn btn-outline btn-sm">
                🖼️ Add Frame
              </button>
              <button onClick={rotateObject} className="btn btn-outline btn-sm">
                <LuRotateCw /> Rotate
              </button>
              <button onClick={deleteSelected} className="btn btn-outline btn-sm">
                🗑️ Delete
              </button>
              <button onClick={undo} className="btn btn-outline btn-sm">
                <LuUndo /> Undo
              </button>
              <button onClick={redo} className="btn btn-outline btn-sm">
                <LuRedo /> Redo
              </button>
              <div style={{ flex: 1 }} />
              <button onClick={saveDesign} className="btn btn-primary btn-sm" disabled={loading}>
                <LuSave /> Save
              </button>
              <button onClick={downloadImage} className="btn btn-outline btn-sm">
                <LuDownload /> Download
              </button>
              <button onClick={completeDesign} className="btn btn-success btn-sm" disabled={loading}>
                ✓ Complete Design
              </button>
            </div>

            {/* Canvas Area */}
            <div style={{ flex: 1, overflow: 'auto', padding: 20, background: '#f5f5f5', display: 'flex', justifyContent: 'center', alignItems: 'center' }}>
              <div style={{
                background: 'white',
                padding: 20,
                borderRadius: 8,
                boxShadow: '0 2px 8px rgba(0,0,0,0.1)'
              }}>
                <canvas id="design-canvas" style={{ border: '1px solid #ddd', display: 'block' }} />
              </div>
            </div>
          </>
        )}

        {loading && (
          <div style={{
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(255,255,255,0.8)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 18,
            zIndex: 1000
          }}>
            Loading...
          </div>
        )}
      </div>
    </div>
  );
}

