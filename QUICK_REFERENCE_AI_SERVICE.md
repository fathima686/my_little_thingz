# 🚀 AI Image Generation - Quick Reference Card

## ✅ Service Status: RUNNING

**URL:** `http://localhost:8001`
**Status:** Fully operational with Stable Diffusion
**Mode:** Production (real image generation)

---

## 🎯 Three Ways to Use

### 1. Browser Test UI (Easiest) ⭐

```powershell
Start-Process "http://localhost:8001/test-ui.html"
```

- Visual interface
- Enter prompts easily
- See results immediately
- No coding needed

### 2. PowerShell Command

```powershell
$body = @{ prompt = "a golden trophy" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

### 3. Frontend Integration

Use the React component: `frontend/src/components/admin/AIImageGenerator.jsx`

---

## ⚡ Performance

| Generation | Time | Notes |
|------------|------|-------|
| First | 2-5 min | Model loading (one-time) |
| Subsequent | 30-90 sec | Model cached |
| With GPU | 5-15 sec | If CUDA available |

---

## 📁 Key Files

| File | Purpose |
|------|---------|
| `ai_service/main.py` | Service (currently running) |
| `ai_service/.env` | Configuration |
| `ai_service/generated_images/` | Output folder |
| `ai_service/test-ui.html` | Browser test interface |
| `POWERSHELL_COMMANDS.md` | Command reference |
| `SUCCESS_FULL_INSTALLATION.md` | Complete guide |

---

## 🎨 Example Prompts

- "a golden trophy on a marble pedestal"
- "professional certificate border with elegant design"
- "abstract geometric pattern in blue and gold"
- "minimalist mountain landscape"
- "vintage ornate frame"

---

## 🔧 Common Commands

**Check health:**
```powershell
Invoke-WebRequest -Uri "http://localhost:8001/health" -UseBasicParsing
```

**View generated images:**
```powershell
Get-ChildItem .\ai_service\generated_images\
```

**Open images folder:**
```powershell
explorer.exe .\ai_service\generated_images\
```

---

## 📚 Documentation

1. **POWERSHELL_COMMANDS.md** - All PowerShell commands
2. **SUCCESS_FULL_INSTALLATION.md** - Complete setup info
3. **INTEGRATION_GUIDE.md** - Frontend integration
4. **TECHNICAL_DOCUMENTATION.md** - Technical details

---

## 🎓 For Demo/Viva

**What to demonstrate:**
- Microservice architecture ✅
- REST API design ✅
- Gemini AI integration ✅
- Stable Diffusion generation ✅
- Full-stack integration ✅

**What to explain:**
- Why Gemini? (Prompt refinement)
- Why Stable Diffusion? (Free, open-source)
- How it works? (See workflow diagrams)
- Performance? (CPU vs GPU trade-offs)

---

## ⚠️ Important Notes

- **Keep service running** - Faster subsequent generations
- **First generation is slow** - Model loading is one-time
- **Warnings are normal** - Can be ignored
- **Images are saved** - Check `generated_images/` folder

---

## 🆘 Quick Troubleshooting

**Service not responding?**
```powershell
Test-NetConnection -ComputerName localhost -Port 8001
```

**Restart service:**
```powershell
# Stop: Press Ctrl+C in service terminal
# Start: .\venv\Scripts\python.exe main.py
```

**Check if running:**
```powershell
netstat -ano | findstr :8001
```

---

## 🎉 You're All Set!

✅ Service installed and running
✅ Full image generation enabled
✅ Test UI available
✅ Documentation complete
✅ Ready for integration
✅ Ready for demonstration

**Now generate some amazing images!** 🎨✨

---

**Quick Start:** Open `http://localhost:8001/test-ui.html` in your browser and try generating an image!
