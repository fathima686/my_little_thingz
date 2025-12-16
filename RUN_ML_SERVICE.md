# 🚀 How to Run Python ML Service - SIMPLE STEPS

## Quick Start (3 Steps)

### Step 1: Open Command Prompt or PowerShell
```bash
cd C:\xampp\htdocs\my_little_thingz
```

### Step 2: Navigate to Python Service
```bash
cd python_ml_service
```

### Step 3: Start the Service
```bash
python app.py
```

---

## What You Should See:

```
INFO:werkzeug: * Running on all addresses (0.0.0.0)
INFO:werkzeug: * Running on http://127.0.0.1:5001
 * Running on http://[YOUR_IP]:5001
Press CTRL+C to quit
 * Restarting with stat
```

---

## ✅ Test It's Working

**Open this URL in your browser:**
```
http://localhost:5001/api/ml/health
```

**You should see:**
```json
{
  "status": "healthy",
  "service": "Python ML Microservice",
  "timestamp": "2025-01-XX...",
  "algorithms": ["KNN", "Bayesian", "Decision Tree", "SVM", "BPNN", "Sentiment Analysis"]
}
```

---

## 🎯 Common Issues

### If you see "Module not found" error:

**Solution:** Install dependencies first
```bash
cd python_ml_service
pip install -r requirements.txt
```

### If port 5001 is busy:

**Solution:** Check what's using it
```bash
netstat -ano | findstr :5001
```

Kill the process:
```bash
taskkill /PID [NUMBER] /F
```

Or change the port in `app.py` line 603:
```python
app.run(host='0.0.0.0', port=5002, debug=True)
```

---

## 📝 Alternative: Use the Batch File

From project root:
```bash
start_sentiment_service.bat
```

---

## 🎉 Once Running

Your ML service is now providing:
- ✅ Sentiment Analysis for Reviews
- ✅ Product Recommendations (KNN)
- ✅ Category Classification
- ✅ Add-on Suggestions
- ✅ Trending Products

**Access it at: http://localhost:5001**

**Keep the terminal window open while using the service!**



















