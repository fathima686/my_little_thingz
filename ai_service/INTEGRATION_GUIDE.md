# Integration Guide: AI Image Generation with Fabric.js Editor

## Overview

This guide shows how to integrate the AI image generation service into your existing Canva-style template editor.

## Step 1: Add AI Image Button to Toolbar

Update `TemplateEditor.jsx`:

```jsx
// Add to toolbar section
<ToolButton
  icon={LuSparkles}  // Import from react-icons/lu
  active={activeTool === 'ai-image'}
  onClick={() => setShowAIPromptDialog(true)}
  tooltip="Generate AI Image"
/>
```

## Step 2: Add AI Prompt Dialog Component

```jsx
// Add state
const [showAIPromptDialog, setShowAIPromptDialog] = useState(false);
const [aiPrompt, setAIPrompt] = useState('');
const [isGenerating, setIsGenerating] = useState(false);

// Add dialog component
function AIPromptDialog() {
  return showAIPromptDialog ? (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
      <div className="bg-white rounded-lg p-6 w-96">
        <h3 className="text-lg font-semibold mb-4">Generate AI Image</h3>
        
        <textarea
          value={aiPrompt}
          onChange={(e) => setAIPrompt(e.target.value)}
          placeholder="Describe the image you want to generate..."
          className="w-full h-32 p-3 border rounded-lg mb-4"
          disabled={isGenerating}
        />
        
        <div className="flex justify-end space-x-2">
          <button
            onClick={() => setShowAIPromptDialog(false)}
            className="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
            disabled={isGenerating}
          >
            Cancel
          </button>
          <button
            onClick={handleGenerateAIImage}
            disabled={isGenerating || !aiPrompt.trim()}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {isGenerating ? 'Generating...' : 'Generate'}
          </button>
        </div>
        
        {isGenerating && (
          <div className="mt-4 text-center text-sm text-gray-600">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto mb-2"></div>
            This may take 1-2 minutes...
          </div>
        )}
      </div>
    </div>
  ) : null;
}
```

## Step 3: Add AI Image Generation Function

```jsx
const handleGenerateAIImage = async () => {
  if (!aiPrompt.trim()) return;
  
  try {
    setIsGenerating(true);
    
    // Call AI service
    const response = await fetch('http://localhost:8001/generate-image', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        prompt: aiPrompt
      })
    });
    
    if (!response.ok) {
      throw new Error('Failed to generate image');
    }
    
    const data = await response.json();
    
    // Add generated image to canvas using existing addImage function
    await addImage(data.image_url);
    
    // Close dialog and reset
    setShowAIPromptDialog(false);
    setAIPrompt('');
    
    alert('AI image generated successfully!');
    
  } catch (error) {
    console.error('Error generating AI image:', error);
    alert('Failed to generate image: ' + error.message);
  } finally {
    setIsGenerating(false);
  }
};
```

## Step 4: Add Dialog to Render

```jsx
// In EditorContent function, add before closing fragment
return (
  <>
    {/* Existing toolbar and canvas */}
    
    {/* Add AI Prompt Dialog */}
    <AIPromptDialog />
  </>
);
```

## Step 5: Import Required Icon

```jsx
import { 
  LuX, LuSave, LuDownload, LuUpload, LuType, LuImage, LuSquare, 
  LuCircle, LuRotateCw, LuMove, LuPalette, LuAlignLeft, LuAlignCenter, 
  LuAlignRight, LuBold, LuItalic, LuUnderline, LuZoomIn, LuZoomOut,
  LuLayers, LuCopy, LuTrash2, LuUndo, LuRedo, LuGrid3X3, LuEye,
  LuSparkles  // Add this for AI button
} from 'react-icons/lu';
```

## Complete Example Usage

1. User clicks "AI Image" button in toolbar
2. Dialog opens with text input
3. User types: "a professional certificate border"
4. User clicks "Generate"
5. Service refines prompt with Gemini
6. Stable Diffusion generates image
7. Image appears on canvas (movable, resizable, rotatable)
8. User can save/export as normal

## Error Handling

```jsx
const handleGenerateAIImage = async () => {
  try {
    // Validation
    if (!aiPrompt.trim()) {
      alert('Please enter a prompt');
      return;
    }
    
    if (aiPrompt.length > 500) {
      alert('Prompt too long. Maximum 500 characters.');
      return;
    }
    
    setIsGenerating(true);
    
    const response = await fetch('http://localhost:8001/generate-image', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: aiPrompt }),
      signal: AbortSignal.timeout(300000)  // 5 minute timeout
    });
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.detail || 'Generation failed');
    }
    
    const data = await response.json();
    await addImage(data.image_url);
    
    setShowAIPromptDialog(false);
    setAIPrompt('');
    
  } catch (error) {
    if (error.name === 'TimeoutError') {
      alert('Generation timed out. Please try a simpler prompt.');
    } else {
      alert('Error: ' + error.message);
    }
  } finally {
    setIsGenerating(false);
  }
};
```

## Testing

1. Start AI service: `cd ai_service && python main.py`
2. Start your frontend
3. Open template editor
4. Click AI Image button
5. Enter prompt: "a golden trophy"
6. Wait for generation
7. Image appears on canvas

## Production Considerations

1. **Service URL**: Change from `localhost:8001` to production URL
2. **Timeout**: Adjust based on server performance
3. **Loading State**: Show progress indicator
4. **Error Messages**: User-friendly error handling
5. **Rate Limiting**: Prevent abuse
6. **Image Caching**: Store generated images in database

## Advanced Features (Optional)

### Add Style Presets

```jsx
const stylePresets = [
  { name: 'Professional', suffix: ', professional photography style' },
  { name: 'Artistic', suffix: ', artistic illustration style' },
  { name: 'Minimalist', suffix: ', minimalist clean design' },
  { name: 'Vintage', suffix: ', vintage retro style' }
];

// In dialog
<select onChange={(e) => setSelectedStyle(e.target.value)}>
  {stylePresets.map(preset => (
    <option key={preset.name} value={preset.suffix}>
      {preset.name}
    </option>
  ))}
</select>
```

### Show Refined Prompt

```jsx
const [refinedPrompt, setRefinedPrompt] = useState('');

// After generation
setRefinedPrompt(data.refined_prompt);

// Display
{refinedPrompt && (
  <div className="mt-2 text-xs text-gray-500">
    Refined: {refinedPrompt}
  </div>
)}
```

## Troubleshooting

**CORS errors**: Ensure AI service has CORS enabled (already configured)

**Timeout errors**: Increase timeout or reduce image quality

**Image not loading**: Check image URL is accessible from frontend

**Service not responding**: Verify service is running on port 8001
