# Quick Start - Windows (What You Just Did)

## ✅ Current Status

Your AI service is **RUNNING** in simplified mode!

- ✅ Virtual environment created
- ✅ Core dependencies installed (FastAPI, Gemini API)
- ✅ Service running at: `http://localhost:8001`
- ⚠️ Stable Diffusion NOT installed (requires large download)

## 🎯 What You Can Do Now

### Option 1: Test Gemini Prompt Refinement (Works Now!)

Open your browser: `http://localhost:8001/test-ui.html`

This will:
- Show you how Gemini refines prompts
- Return placeholder images
- Test the API structure

### Option 2: Test with PowerShell

```powershell
$body = @{ prompt = "a golden trophy" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

You'll see:
- Original prompt
- Refined prompt (from Gemini)
- Placeholder image URL

## 📊 What's Working vs What's Not

| Feature | Status | Notes |
|---------|--------|-------|
| FastAPI Server | ✅ Working | Running on port 8001 |
| Gemini Prompt Refinement | ✅ Working | Enhances your prompts |
| API Endpoints | ✅ Working | All endpoints functional |
| Test UI | ✅ Working | Browser interface ready |
| Stable Diffusion | ❌ Not Installed | Requires 4GB download |
| Real Image Generation | ❌ Not Available | Need ML dependencies |

## 🚀 To Enable Full Image Generation

If you want real AI-generated images (not placeholders), you need to install the ML dependencies.

**Warning**: This will download ~4GB of data and may take 30-60 minutes.

```powershell
# In ai_service directory with venv activated
.\venv\Scripts\pip.exe install torch torchvision --index-url https://download.pytorch.org/whl/cpu
.\venv\Scripts\pip.exe install diffusers transformers accelerate
```

Then stop the current service and run:
```powershell
.\venv\Scripts\python.exe main.py
```

## 🎨 Current Workflow (Simplified Mode)

1. **You enter prompt**: "a golden trophy"
2. **Gemini refines it**: "A professional golden trophy on a marble pedestal, clean white background, no text, no watermark, high quality, studio lighting..."
3. **Returns placeholder**: Shows you what the API response looks like
4. **You see the structure**: Understand how it will work with real images

## 📝 Next Steps

### For Testing/Demo (No ML needed)
1. ✅ Service is running
2. Open: `http://localhost:8001/test-ui.html`
3. Try different prompts
4. See how Gemini enhances them
5. Understand the API structure

### For Real Image Generation (ML required)
1. Install ML dependencies (see above)
2. Wait for 4GB download
3. Run `python main.py` instead
4. First generation takes 2-5 minutes
5. Subsequent generations: 30-90 seconds

### For Frontend Integration
1. Current simplified mode is enough to test API calls
2. Follow: `INTEGRATION_GUIDE.md`
3. Your React component will work with both modes
4. Switch to full mode when ready

## 🔧 Commands Reference

### Check if service is running
```powershell
Invoke-WebRequest -Uri "http://localhost:8001/health" -UseBasicParsing
```

### Test prompt refinement
```powershell
$body = @{ prompt = "your prompt here" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

### Stop the service
Press `Ctrl+C` in the terminal where it's running

### Start again
```powershell
.\venv\Scripts\python.exe main_simple.py
```

## 💡 Pro Tips

1. **For now**: Use simplified mode to test API integration
2. **Later**: Install ML dependencies when you have time
3. **Demo**: Simplified mode is enough to show the concept
4. **Production**: You'll need full mode with Stable Diffusion

## 🎓 For Your Viva/Demo

You can demonstrate:
- ✅ Microservice architecture
- ✅ REST API design
- ✅ Gemini API integration
- ✅ Prompt engineering
- ✅ Error handling
- ✅ Frontend-backend communication

You can explain:
- Why Stable Diffusion (free, open-source)
- How prompt refinement works
- API structure and endpoints
- Integration with Fabric.js
- Performance considerations

## 🐛 Troubleshooting

### Service won't start
```powershell
# Check if port is in use
netstat -ano | findstr :8001

# Kill process if needed
taskkill /PID <process_id> /F
```

### Gemini API errors
- Check `.env` file has correct API key
- Verify internet connection
- Check API quota at: https://makersuite.google.com/

### Want to install full version
See: `SETUP_INSTRUCTIONS_WINDOWS.md`

---

**You're all set for testing and development!** 🎉

The simplified mode lets you test everything except actual image generation. When you're ready for real images, install the ML dependencies.
