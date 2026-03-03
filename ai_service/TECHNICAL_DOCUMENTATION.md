# Technical Documentation: AI Image Generation Service

## System Architecture

### High-Level Flow

```
┌─────────────┐      ┌──────────────┐      ┌─────────────────┐      ┌──────────────────┐
│   Frontend  │─────▶│   FastAPI    │─────▶│  Gemini API     │─────▶│ Stable Diffusion │
│  (React)    │      │   Backend    │      │ (Prompt Refine) │      │  (Image Gen)     │
└─────────────┘      └──────────────┘      └─────────────────┘      └──────────────────┘
       ▲                     │                                                │
       │                     │                                                │
       │                     ▼                                                ▼
       │              ┌──────────────┐                                ┌──────────────┐
       └──────────────│  Image URL   │◀───────────────────────────────│  Save Image  │
                      └──────────────┘                                └──────────────┘
```

### Component Breakdown

#### 1. FastAPI Backend (`main.py`)
- **Purpose**: REST API server
- **Port**: 8001
- **Endpoints**:
  - `GET /` - Service info
  - `GET /health` - Health check
  - `POST /generate-image` - Main generation endpoint
  - `GET /images/{filename}` - Serve generated images
  - `DELETE /images/{filename}` - Delete images
- **Features**:
  - CORS enabled for frontend access
  - Static file serving for images
  - Error handling and validation
  - Request/response models with Pydantic

#### 2. Gemini Prompt Refinement (`gemini_prompt.py`)
- **Purpose**: Enhance user prompts for better image quality
- **Model**: `gemini-pro`
- **Process**:
  1. Receives raw user prompt
  2. Sends to Gemini with system instructions
  3. Gemini adds quality constraints and artistic details
  4. Returns refined prompt
- **Fallback**: Rule-based enhancement if API fails
- **Quality Constraints**:
  - Clean background
  - No text in image
  - No watermark
  - High quality
  - Printable design

#### 3. Stable Diffusion Engine (`diffusion_engine.py`)
- **Purpose**: Generate images from refined prompts
- **Model**: `runwayml/stable-diffusion-v1-5`
- **Configuration**:
  - Image size: 512x512 pixels
  - Inference steps: 30
  - Guidance scale: 7.5
  - Scheduler: DPM-Solver++
- **Optimizations**:
  - Model loaded once and cached
  - Attention slicing for CPU
  - FP16 for GPU, FP32 for CPU
- **Negative Prompt**: Automatically adds terms to avoid unwanted elements

## API Specification

### POST /generate-image

**Request Body**:
```json
{
  "prompt": "string (1-500 characters, required)"
}
```

**Success Response (200)**:
```json
{
  "image_url": "http://localhost:8001/images/ai_generated_20260116_143022.png",
  "refined_prompt": "A professional photograph of...",
  "original_prompt": "a cat"
}
```

**Error Responses**:

400 Bad Request:
```json
{
  "detail": "Prompt cannot be empty"
}
```

500 Internal Server Error:
```json
{
  "detail": "Failed to generate image: [error details]"
}
```

## Data Flow

### Request Processing

1. **Validation Phase**
   - Check prompt is not empty
   - Check prompt length ≤ 500 characters
   - Sanitize input

2. **Refinement Phase**
   - Send prompt to Gemini API
   - Apply system instructions
   - Validate refined prompt contains quality constraints
   - Fallback to rule-based if Gemini fails

3. **Generation Phase**
   - Load Stable Diffusion model (if not cached)
   - Apply refined prompt
   - Apply negative prompt
   - Generate 512x512 image
   - 30 inference steps

4. **Storage Phase**
   - Generate unique filename with timestamp
   - Save as PNG in `generated_images/`
   - Optimize file size

5. **Response Phase**
   - Construct image URL
   - Return JSON response with URLs and prompts

## Performance Characteristics

### First Request
- **Time**: 2-5 minutes
- **Reason**: Model download and initialization
- **One-time**: Yes

### Subsequent Requests (CPU)
- **Time**: 30-90 seconds
- **Factors**: Prompt complexity, system resources

### Subsequent Requests (GPU)
- **Time**: 5-15 seconds
- **Requirements**: CUDA-compatible GPU

### Memory Usage
- **Model Size**: ~4GB
- **Runtime RAM**: 6-8GB (CPU), 4-6GB (GPU)
- **Disk Space**: ~5GB (model + dependencies)

## Configuration Options

### Environment Variables (`.env`)

```env
# Required
GEMINI_API_KEY=your_api_key_here

# Optional (defaults shown)
SERVICE_PORT=8001
SERVICE_HOST=0.0.0.0
IMAGE_SIZE=512
INFERENCE_STEPS=30
GUIDANCE_SCALE=7.5
MODEL_ID=runwayml/stable-diffusion-v1-5
```

### Tuning Parameters

**IMAGE_SIZE**:
- 256: Fast, lower quality
- 512: Balanced (recommended)
- 768: Slower, higher quality
- 1024: Very slow, highest quality

**INFERENCE_STEPS**:
- 20: Fast, acceptable quality
- 30: Balanced (recommended)
- 50: Slower, better quality
- 100: Very slow, marginal improvement

**GUIDANCE_SCALE**:
- 5.0: More creative, less accurate
- 7.5: Balanced (recommended)
- 10.0: More accurate, less creative
- 15.0: Very literal, may be rigid

## Error Handling

### Gemini API Errors
- **Network failure**: Falls back to rule-based refinement
- **Rate limit**: Returns 429 error to frontend
- **Invalid API key**: Service fails to start

### Stable Diffusion Errors
- **Out of memory**: Reduce IMAGE_SIZE or INFERENCE_STEPS
- **Model download failure**: Check internet connection
- **CUDA errors**: Falls back to CPU automatically

### File System Errors
- **Disk full**: Returns 500 error
- **Permission denied**: Check directory permissions
- **Invalid filename**: Sanitizes automatically

## Security Considerations

### API Key Protection
- Stored in `.env` file (not committed to git)
- Never exposed to frontend
- Server-side only

### Input Validation
- Prompt length limits
- Character sanitization
- SQL injection prevention (N/A - no database)

### CORS Policy
- Currently allows all origins (`*`)
- **Production**: Restrict to specific frontend domain

### Rate Limiting
- **Not implemented** in current version
- **Recommendation**: Add rate limiting for production

## Testing

### Unit Tests

Test Gemini:
```bash
python gemini_prompt.py
```

Test Stable Diffusion:
```bash
python diffusion_engine.py
```

### Integration Tests

Full service test:
```bash
python test_service.py
```

### Manual Testing

```bash
# Start service
python main.py

# In another terminal
curl -X POST http://localhost:8001/generate-image \
  -H "Content-Type: application/json" \
  -d '{"prompt": "a red apple"}'
```

## Deployment

### Development
```bash
python main.py
```

### Production (with Gunicorn)
```bash
pip install gunicorn
gunicorn main:app --workers 2 --worker-class uvicorn.workers.UvicornWorker --bind 0.0.0.0:8001
```

### Docker (Optional)
```dockerfile
FROM python:3.10
WORKDIR /app
COPY requirements.txt .
RUN pip install -r requirements.txt
COPY . .
CMD ["python", "main.py"]
```

## Troubleshooting

### Service won't start
- Check Python version: `python --version` (need 3.10+)
- Check dependencies: `pip list`
- Check port availability: `netstat -an | findstr 8001`

### Slow generation
- Check CPU usage
- Reduce IMAGE_SIZE
- Reduce INFERENCE_STEPS
- Consider GPU acceleration

### Poor image quality
- Increase INFERENCE_STEPS
- Increase IMAGE_SIZE
- Improve prompt specificity
- Adjust GUIDANCE_SCALE

### Gemini errors
- Verify API key
- Check internet connection
- Check API quota

## Future Enhancements

1. **Multiple Models**: Support different Stable Diffusion versions
2. **Style Transfer**: Apply artistic styles to generated images
3. **Batch Generation**: Generate multiple variations
4. **Image Editing**: Inpainting and outpainting
5. **Caching**: Cache frequently requested prompts
6. **Database**: Store generation history
7. **Authentication**: User-based API access
8. **Rate Limiting**: Prevent abuse
9. **GPU Optimization**: Better CUDA utilization
10. **Progress Tracking**: WebSocket for generation progress

## Academic Context

### Viva Questions & Answers

**Q: Why use Gemini for prompt refinement?**
A: Gemini improves vague prompts by adding artistic details and quality constraints, resulting in more professional images suitable for templates and certificates.

**Q: Why Stable Diffusion instead of DALL-E or Midjourney?**
A: Stable Diffusion is free, open-source, and runs locally. No API costs, no usage limits, and full control over the generation process.

**Q: How does the negative prompt work?**
A: It tells the model what NOT to include. We use it to avoid text, watermarks, and low-quality artifacts.

**Q: What is the guidance scale?**
A: It controls how closely the model follows the prompt. Higher values = more literal, lower values = more creative.

**Q: Why 512x512 resolution?**
A: It's the native resolution of Stable Diffusion v1.5, providing the best balance between quality and generation speed.

**Q: Can this run without internet?**
A: Partially. Gemini requires internet, but Stable Diffusion runs offline after initial model download.

**Q: How is this different from browser-based generation?**
A: Server-side generation is more powerful, protects API keys, and doesn't depend on client hardware.

## References

- [Stable Diffusion Paper](https://arxiv.org/abs/2112.10752)
- [Diffusers Library](https://huggingface.co/docs/diffusers)
- [Gemini API Docs](https://ai.google.dev/docs)
- [FastAPI Documentation](https://fastapi.tiangolo.com/)
