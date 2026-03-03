# AI Image Generation Service

Complete AI-powered image generation system using **Gemini** for prompt refinement and **Stable Diffusion** for image generation.

## Architecture

```
User Input → Gemini API → Refined Prompt → Stable Diffusion → Generated Image → Fabric.js Canvas
```

## Features

- **Gemini Prompt Refinement**: Intelligently enhances user prompts
- **Stable Diffusion Generation**: Free, open-source image generation
- **Local Image Storage**: All images saved locally
- **FastAPI Backend**: Clean REST API
- **CORS Enabled**: Works with any frontend
- **Quality Constraints**: Enforces clean, professional output

## Quick Start

### 1. Setup (First Time Only)

```bash
cd ai_service
setup.bat
```

This will:
- Create Python virtual environment
- Install all dependencies
- Download Stable Diffusion model (~4GB)

### 2. Start the Service

```bash
venv\Scripts\activate.bat
python main.py
```

Service runs at: `http://localhost:8001`

### 3. Test the Service

```bash
python test_service.py
```

## API Usage

### Generate Image

**Endpoint**: `POST /generate-image`

**Request**:
```json
{
  "prompt": "a beautiful sunset"
}
```

**Response**:
```json
{
  "image_url": "http://localhost:8001/images/ai_generated_20260116_143022.png",
  "refined_prompt": "A stunning sunset over the ocean...",
  "original_prompt": "a beautiful sunset"
}
```

## Integration with Fabric.js Editor

Add this to your TemplateEditor.jsx:

```javascript
const generateAIImage = async (prompt) => {
  const response = await fetch('http://localhost:8001/generate-image', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prompt })
  });
  
  const data = await response.json();
  addImage(data.image_url);  // Use existing addImage function
};
```

## File Structure

```
ai_service/
├── main.py              # FastAPI server
├── gemini_prompt.py     # Prompt refinement
├── diffusion_engine.py  # Image generation
├── .env                 # API keys
├── requirements.txt     # Dependencies
├── setup.bat           # Setup script
├── start.bat           # Start script
├── test_service.py     # Test suite
└── generated_images/   # Output directory
```

## Configuration

Edit `.env` file:

```env
GEMINI_API_KEY=your_key_here
SERVICE_PORT=8001
IMAGE_SIZE=512
INFERENCE_STEPS=30
```

## Performance

- **First generation**: 2-5 minutes (model loading)
- **Subsequent generations**: 30-90 seconds (CPU)
- **With GPU**: 5-15 seconds

## Troubleshooting

**Service won't start**:
- Check Python version: `python --version` (need 3.10+)
- Reinstall dependencies: `pip install -r requirements.txt`

**Gemini API errors**:
- Verify API key in `.env`
- Check internet connection

**Out of memory**:
- Reduce `IMAGE_SIZE` in `.env`
- Close other applications

## Technologies Used

- **FastAPI**: Web framework
- **Gemini API**: Prompt intelligence
- **Stable Diffusion v1.5**: Image generation
- **PyTorch**: ML framework
- **Diffusers**: Hugging Face library
