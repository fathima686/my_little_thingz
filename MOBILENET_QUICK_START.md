# MobileNet Quick Start (5 Minutes)

## 🎯 Goal
Replace Google Vision API (paid) with free local MobileNet classifier.

## ⚡ Quick Setup

### Step 1: Install Python Service (5 minutes, one-time)

```bash
cd python_ml_service
setup.bat
```

Wait for installation to complete (~500MB download).

### Step 2: Start Service

```bash
cd python_ml_service
start-service.bat
```

**Keep this window open!** Service runs on http://localhost:5000

### Step 3: Test

Open new terminal:
```bash
cd python_ml_service
test-classifier.bat
```

Should see:
```json
{
  "success": true,
  "ai_enabled": true,
  "model": "MobileNetV2"
}
```

### Step 4: Done!

Your application now uses free local AI classification!

## ✅ What You Get

- ✅ **Free** - No costs, no billing
- ✅ **Fast** - 100-200ms per image
- ✅ **Private** - All local processing
- ✅ **Reliable** - No quota limits

## 🔧 Configuration

Already configured in `backend/.env`:
```env
LOCAL_CLASSIFIER_URL=http://localhost:5000
```

## 🧪 Test Integration

1. Start service: `python_ml_service\start-service.bat`
2. Upload image through your app
3. Check logs for: "Local Classifier Response Code: 200"

## ⚠️ Important

**Keep the service running** while your application is active.

To run as background service, see `MOBILENET_MIGRATION_GUIDE.md`.

## 🐛 Troubleshooting

### Service won't start?
- Check Python installed: `python --version` (need 3.8+)
- Reinstall: `cd python_ml_service` → `setup.bat`

### Classification not working?
- Check service running: `curl http://localhost:5000/health`
- Check logs in service window
- Restart service

## 📚 More Info

- Full guide: `MOBILENET_MIGRATION_GUIDE.md`
- Service docs: `python_ml_service/README.md`

## 🎉 Benefits

**Before** (Google Vision):
- ❌ Requires billing
- ❌ HTTP 403 errors
- ❌ Costs money

**After** (MobileNet):
- ✅ Free forever
- ✅ No errors
- ✅ $0 cost

That's it! You're now using free AI classification! 🚀
