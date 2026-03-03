# AI Image Generation Feature - Complete Setup Guide

## Overview

This document provides step-by-step instructions to set up and integrate the AI image generation feature into your Canva-style template editor.

## What You Get

✓ **Gemini-powered prompt refinement** - Turns "a cat" into professional prompts
✓ **Stable Diffusion image generation** - Free, open-source, runs locally
✓ **FastAPI backend** - Clean REST API
✓ **Seamless Fabric.js integration** - Generated images work like uploaded images
✓ **No API costs** - Completely free (except Gemini API, which has free tier)

## Prerequisites

- Python 3.10 or higher
- 8GB RAM minimum (16GB recommended)
- 10GB free disk space
- Internet connection (for initial setup)
- Gemini API key (already provided)

## Installation Steps

### Step 1: Navigate to AI Service Directory

```bash
cd ai_service
```

### Step 2: Run Setup Script

**Windows (Command Prompt)**:
```bash
setup.bat
```

**Windows (PowerShell)**:
```powershell
.\setup.ps1
```

This will:
- Create Python virtual environment
- Install all dependencies
- Download Stable Diffusion model (~4GB)
- Takes 5-10 minutes

### Step 3: Verify Installation

```bash
venv\Scripts\activate.bat
python test_service.py
```

Expected output:
```
✓ Service is healthy
✓ Gemini API working
✓ Stable Diffusion ready
```

## Starting the Service

### Method 1: Using Start Script

```bash
start.bat
```

### Method 2: Manual Start

```bash
venv\Scripts\activate.bat
python main.py
```

Service will be available at: `http://localhost:8001`

## Testing the Service

### Test 1: Health Check

Open browser: `http://localhost:8001/health`

Expected response:
```json
{
  "status": "healthy",
  "gemini": "configured",
  "stable_diffusion": "ready"
}
```

### Test 2: Generate Test Image

```bash
python test_service.py
```

This will:
1. Check service health
2. Generate a test image
3. Test error handling

### Test 3: Manual API Call

Using curl:
```bash
curl -X POST http://localhost:8001/generate-image ^
  -H "Content-Type: application/json" ^
  -d "{\"prompt\": \"a golden trophy\"}"
```

Using PowerShell:
```powershell
$body = @{ prompt = "a golden trophy" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

## Frontend Integration

### Step 1: Add AI Button to Template Editor

Edit `frontend/src/components/admin/TemplateEditor.jsx`:

```jsx
// Add import
import { LuSparkles } from 'react-icons/lu';

// Add state
const [showAIDialog, setShowAIDialog] = useState(false);
const [aiPrompt, setAIPrompt] = useState('');
const [generating, setGenerating] = useState(false);

// Add button to toolbar
<ToolButton
  icon={LuSparkles}
  onClick={() => setShowAIDialog(true)}
  tooltip="Generate AI Image"
/>
```

### Step 2: Add AI Dialog Component

```jsx
function AIImageDialog() {
  const handleGenerate = async () => {
    if (!aiPrompt.trim()) return;
    
    try {
      setGenerating(true);
      
      const response = await fetch('http://localhost:8001/generate-image', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ prompt: aiPrompt })
      });
      
      const data = await response.json();
      await addImage(data.image_url);
      
      setShowAIDialog(false);
      setAIPrompt('');
    } catch (error) {
      alert('Error: ' + error.message);
    } finally {
      setGenerating(false);
    }
  };
  
  return showAIDialog ? (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
      <div className="bg-white rounded-lg p-6 w-96">
        <h3 className="text-lg font-semibold mb-4">Generate AI Image</h3>
        
        <textarea
          value={aiPrompt}
          onChange={(e) => setAIPrompt(e.target.value)}
          placeholder="Describe your image..."
          className="w-full h-32 p-3 border rounded-lg mb-4"
          disabled={generating}
        />
        
        <div className="flex justify-end space-x-2">
          <button
            onClick={() => setShowAIDialog(false)}
            className="px-4 py-2 bg-gray-200 rounded"
            disabled={generating}
          >
            Cancel
          </button>
          <button
            onClick={handleGenerate}
            disabled={generating || !aiPrompt.trim()}
            className="px-4 py-2 bg-blue-600 text-white rounded disabled:opacity-50"
          >
            {generating ? 'Generating...' : 'Generate'}
          </button>
        </div>
        
        {generating && (
          <div className="mt-4 text-center text-sm text-gray-600">
            This may take 1-2 minutes...
          </div>
        )}
      </div>
    </div>
  ) : null;
}

// Add to render
<AIImageDialog />
```

## Usage Workflow

1. **Admin opens template editor**
2. **Clicks "AI Image" button** (sparkle icon)
3. **Enters prompt**: "a professional certificate border"
4. **Clicks "Generate"**
5. **Waits 30-90 seconds**
6. **Image appears on canvas** (movable, resizable, rotatable)
7. **Saves/exports as normal**

## Example Prompts

### Good Prompts
- "a golden trophy on a pedestal"
- "professional certificate border with elegant design"
- "abstract geometric pattern in blue and gold"
- "minimalist mountain landscape"
- "corporate handshake illustration"

### Weak Prompts (Gemini will enhance)
- "trophy" → "A professional golden trophy..."
- "border" → "An elegant certificate border..."
- "mountains" → "A majestic mountain landscape..."

## Performance Expectations

### First Generation
- **Time**: 2-5 minutes
- **Reason**: Model loading
- **One-time**: Yes

### Subsequent Generations
- **CPU**: 30-90 seconds
- **GPU**: 5-15 seconds

## Troubleshooting

### Service Won't Start

**Error**: `Python not found`
- Install Python 3.10+
- Add to PATH

**Error**: `Module not found`
- Run: `pip install -r requirements.txt`

**Error**: `Port 8001 already in use`
- Change port in `.env`: `SERVICE_PORT=8002`

### Generation Errors

**Error**: `Gemini API error`
- Check API key in `.env`
- Verify internet connection

**Error**: `Out of memory`
- Close other applications
- Reduce `IMAGE_SIZE` in `.env` to 256

**Error**: `Timeout`
- Increase timeout in frontend code
- Check CPU usage

### Image Quality Issues

**Problem**: Blurry images
- Increase `INFERENCE_STEPS` in `.env`

**Problem**: Wrong content
- Improve prompt specificity
- Add more descriptive details

**Problem**: Text in image
- Gemini should prevent this
- Add "no text" to prompt manually

## Configuration

Edit `ai_service/.env`:

```env
# API Key
GEMINI_API_KEY=AIzaSyBQwcoPhCyPgKf4RCDil05Q16nh2EIwP3o

# Service
SERVICE_PORT=8001

# Quality (adjust for performance)
IMAGE_SIZE=512          # 256, 512, 768, 1024
INFERENCE_STEPS=30      # 20-50
GUIDANCE_SCALE=7.5      # 5.0-15.0
```

## File Structure

```
ai_service/
├── main.py                    # FastAPI server
├── gemini_prompt.py           # Prompt refinement
├── diffusion_engine.py        # Image generation
├── test_service.py            # Test suite
├── .env                       # Configuration
├── requirements.txt           # Dependencies
├── setup.bat / setup.ps1      # Setup scripts
├── start.bat                  # Start script
├── generated_images/          # Output directory
├── README.md                  # Documentation
├── INTEGRATION_GUIDE.md       # Frontend integration
└── TECHNICAL_DOCUMENTATION.md # Technical details
```

## Next Steps

1. ✓ Complete setup
2. ✓ Test service
3. ✓ Integrate with frontend
4. Test end-to-end workflow
5. Adjust configuration for your needs
6. Deploy to production (optional)

## Support

For issues or questions:
1. Check `TECHNICAL_DOCUMENTATION.md`
2. Check `INTEGRATION_GUIDE.md`
3. Review error logs in terminal
4. Test individual components

## Academic Notes

This implementation demonstrates:
- **Microservice architecture**
- **AI API integration** (Gemini)
- **Machine learning deployment** (Stable Diffusion)
- **REST API design** (FastAPI)
- **Frontend-backend communication**
- **Error handling and validation**
- **Performance optimization**

Perfect for viva demonstrations and technical explanations.

## Success Criteria

✓ Service starts without errors
✓ Health check returns "healthy"
✓ Test image generates successfully
✓ Frontend can call API
✓ Generated images appear on canvas
✓ Images are movable/resizable/rotatable
✓ Export includes AI-generated images

You're all set! 🎉
