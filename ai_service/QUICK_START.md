# Quick Start Guide - AI Image Generation

Get up and running in 5 minutes!

## Step 1: Setup (2 minutes)

```bash
cd ai_service
setup.bat
```

Wait for installation to complete.

## Step 2: Start Service (30 seconds)

```bash
venv\Scripts\activate.bat
python main.py
```

You should see:
```
INFO:     Uvicorn running on http://0.0.0.0:8001
INFO:     Application startup complete.
```

## Step 3: Test (1 minute)

Open new terminal:

```bash
cd ai_service
venv\Scripts\activate.bat
python test_service.py
```

Expected output:
```
✓ PASS - Health Check
✓ PASS - Image Generation
✓ PASS - Error Handling

Total: 3/3 tests passed
```

## Step 4: Try It!

### Option A: Browser Test

Open: `http://localhost:8001`

### Option B: Command Line Test

```bash
curl -X POST http://localhost:8001/generate-image ^
  -H "Content-Type: application/json" ^
  -d "{\"prompt\": \"a golden trophy\"}"
```

### Option C: PowerShell Test

```powershell
$body = @{ prompt = "a beautiful sunset" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

## What's Next?

1. **Integrate with Frontend**: See `INTEGRATION_GUIDE.md`
2. **Customize Settings**: Edit `.env` file
3. **Read Documentation**: Check `TECHNICAL_DOCUMENTATION.md`

## Common Issues

**Port already in use?**
- Change port in `.env`: `SERVICE_PORT=8002`

**Out of memory?**
- Edit `.env`: `IMAGE_SIZE=256`

**Slow generation?**
- Edit `.env`: `INFERENCE_STEPS=20`

## Example Prompts to Try

- "a professional certificate border"
- "a golden trophy on a pedestal"
- "abstract geometric pattern"
- "minimalist mountain landscape"
- "elegant floral design"

That's it! You're ready to generate AI images. 🎉
