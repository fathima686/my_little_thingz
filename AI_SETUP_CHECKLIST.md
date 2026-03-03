# AI Image Generation - Setup Checklist

Use this checklist to ensure everything is set up correctly.

## ✅ Pre-Setup Checklist

- [ ] Python 3.10 or higher installed
  ```bash
  python --version
  ```
  Expected: `Python 3.10.x` or higher

- [ ] pip is up to date
  ```bash
  python -m pip --version
  ```

- [ ] At least 10GB free disk space
  ```bash
  # Check available space on C: drive
  ```

- [ ] Internet connection available (for initial setup)

- [ ] Gemini API key available
  - ✅ Already configured: `AIzaSyBQwcoPhCyPgKf4RCDil05Q16nh2EIwP3o`

---

## ✅ Installation Checklist

### Step 1: Navigate to Directory
- [ ] Opened terminal/command prompt
- [ ] Changed to project directory
  ```bash
  cd C:\xampp\htdocs\my_little_thingz
  cd ai_service
  ```

### Step 2: Run Setup Script
- [ ] Executed setup script
  ```bash
  setup.bat
  ```
  OR
  ```powershell
  .\setup.ps1
  ```

- [ ] Virtual environment created
  - Check: `venv` folder exists in `ai_service/`

- [ ] Dependencies installed
  - Check: No error messages during installation
  - Check: `pip list` shows all packages

- [ ] Stable Diffusion model downloaded
  - Check: `~/.cache/huggingface/` contains model files
  - Size: ~4GB

---

## ✅ Configuration Checklist

### Step 3: Verify Configuration
- [ ] `.env` file exists in `ai_service/`
- [ ] `.env` contains Gemini API key
  ```env
  GEMINI_API_KEY=AIzaSyBQwcoPhCyPgKf4RCDil05Q16nh2EIwP3o
  ```
- [ ] `generated_images/` directory exists
- [ ] Port 8001 is available (not used by other services)

---

## ✅ Service Startup Checklist

### Step 4: Start the Service
- [ ] Activated virtual environment
  ```bash
  venv\Scripts\activate.bat
  ```
  - Check: Command prompt shows `(venv)` prefix

- [ ] Started FastAPI server
  ```bash
  python main.py
  ```

- [ ] Service started successfully
  - Check: Terminal shows:
    ```
    INFO:     Uvicorn running on http://0.0.0.0:8001
    INFO:     Application startup complete.
    ```

- [ ] No error messages in terminal

---

## ✅ Testing Checklist

### Step 5: Health Check
- [ ] Opened browser
- [ ] Navigated to `http://localhost:8001`
- [ ] Received JSON response:
  ```json
  {
    "status": "online",
    "service": "AI Image Generation Service"
  }
  ```

- [ ] Checked health endpoint
  - URL: `http://localhost:8001/health`
  - Expected response:
    ```json
    {
      "status": "healthy",
      "gemini": "configured",
      "stable_diffusion": "ready"
    }
    ```

### Step 6: Browser Test UI
- [ ] Opened test UI
  - URL: `http://localhost:8001/test-ui.html`
- [ ] Status shows "Service is online and ready"
- [ ] Entered test prompt: "a golden trophy"
- [ ] Clicked "Generate Image"
- [ ] Waited for generation (30-90 seconds)
- [ ] Image displayed successfully
- [ ] Refined prompt shown
- [ ] No error messages

### Step 7: Automated Test Suite
- [ ] Opened new terminal
- [ ] Activated virtual environment
  ```bash
  cd ai_service
  venv\Scripts\activate.bat
  ```
- [ ] Ran test suite
  ```bash
  python test_service.py
  ```
- [ ] All tests passed:
  ```
  ✓ PASS - Health Check
  ✓ PASS - Image Generation
  ✓ PASS - Error Handling
  
  Total: 3/3 tests passed
  ```

### Step 8: Manual API Test
- [ ] Tested with curl or PowerShell
  ```bash
  curl -X POST http://localhost:8001/generate-image ^
    -H "Content-Type: application/json" ^
    -d "{\"prompt\": \"a red apple\"}"
  ```
- [ ] Received valid JSON response with image_url
- [ ] Image file created in `generated_images/`
- [ ] Image accessible via URL

---

## ✅ Frontend Integration Checklist

### Step 9: React Component
- [ ] `AIImageGenerator.jsx` exists in `frontend/src/components/admin/`
- [ ] Component imports correctly
- [ ] No TypeScript/ESLint errors

### Step 10: Template Editor Integration
- [ ] Imported `AIImageGenerator` component
- [ ] Added state: `showAIGenerator`
- [ ] Added AI button to toolbar
- [ ] Added component to render tree
- [ ] Tested button click opens dialog

### Step 11: End-to-End Test
- [ ] Started AI service
- [ ] Started frontend application
- [ ] Opened template editor
- [ ] Clicked AI Image button
- [ ] Entered prompt
- [ ] Clicked Generate
- [ ] Image appeared on canvas
- [ ] Image is movable
- [ ] Image is resizable
- [ ] Image is rotatable
- [ ] Saved design includes AI image
- [ ] Exported PNG includes AI image

---

## ✅ Performance Verification

### Step 12: Performance Check
- [ ] First generation completed (2-5 minutes acceptable)
- [ ] Second generation faster (30-90 seconds)
- [ ] No memory errors
- [ ] No timeout errors
- [ ] Images are 512x512 pixels
- [ ] Images are high quality
- [ ] No text in generated images
- [ ] No watermarks in generated images

---

## ✅ Documentation Review

### Step 13: Documentation
- [ ] Read `QUICK_START.md`
- [ ] Read `INTEGRATION_GUIDE.md`
- [ ] Reviewed `TECHNICAL_DOCUMENTATION.md`
- [ ] Understand architecture
- [ ] Understand API endpoints
- [ ] Understand configuration options

---

## ✅ Production Readiness

### Step 14: Production Preparation
- [ ] Tested error handling
  - Empty prompt
  - Too long prompt
  - Network errors
  - Service offline
- [ ] Tested edge cases
  - Special characters in prompt
  - Very long generation time
  - Multiple simultaneous requests
- [ ] Performance optimized
  - Adjusted IMAGE_SIZE if needed
  - Adjusted INFERENCE_STEPS if needed
- [ ] Security reviewed
  - API key not exposed to frontend
  - CORS configured appropriately
  - Input validation working

---

## ✅ Demo Preparation (Academic)

### Step 15: Viva Preparation
- [ ] Can explain architecture
- [ ] Can explain Gemini's role
- [ ] Can explain Stable Diffusion
- [ ] Can explain prompt refinement
- [ ] Can explain negative prompts
- [ ] Can explain guidance scale
- [ ] Can demonstrate live generation
- [ ] Can show code structure
- [ ] Can explain error handling
- [ ] Can discuss performance trade-offs

### Step 16: Demo Scenarios
- [ ] Scenario 1: Simple prompt
  - Input: "trophy"
  - Show refinement
  - Show generation
  - Show result on canvas

- [ ] Scenario 2: Complex prompt
  - Input: "professional certificate border with floral design"
  - Show how Gemini enhances it
  - Show high-quality result

- [ ] Scenario 3: Error handling
  - Show empty prompt error
  - Show service offline handling

- [ ] Scenario 4: Full workflow
  - Open editor
  - Generate AI image
  - Add text
  - Add shapes
  - Export final design

---

## 🎯 Final Verification

### All Systems Go?
- [ ] ✅ Service starts without errors
- [ ] ✅ Health check passes
- [ ] ✅ Test image generates successfully
- [ ] ✅ Browser test UI works
- [ ] ✅ Automated tests pass
- [ ] ✅ Frontend integration complete
- [ ] ✅ End-to-end workflow works
- [ ] ✅ Performance acceptable
- [ ] ✅ Documentation understood
- [ ] ✅ Demo prepared

---

## 🐛 Troubleshooting Reference

If any checkbox fails, refer to:

| Issue | Solution Document |
|-------|------------------|
| Setup fails | `QUICK_START.md` |
| Service won't start | `TECHNICAL_DOCUMENTATION.md` - Troubleshooting |
| Integration issues | `INTEGRATION_GUIDE.md` |
| Performance problems | `README.md` - Configuration |
| API errors | `TECHNICAL_DOCUMENTATION.md` - Error Handling |

---

## 📊 Success Metrics

Your setup is successful when:

✅ **Functionality**: All features work as expected
✅ **Performance**: Generation completes in reasonable time
✅ **Reliability**: No crashes or unexpected errors
✅ **Quality**: Generated images are high quality
✅ **Integration**: Seamlessly works with template editor
✅ **Documentation**: You understand how it works
✅ **Demo-Ready**: Can demonstrate confidently

---

## 🎉 Congratulations!

If all checkboxes are checked, you have successfully:

✅ Installed and configured the AI image generation service
✅ Tested all components
✅ Integrated with your frontend
✅ Verified end-to-end functionality
✅ Prepared for demonstration

**You're ready to generate amazing AI images!** 🚀

---

**Date Completed**: _______________

**Tested By**: _______________

**Notes**: 
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________
