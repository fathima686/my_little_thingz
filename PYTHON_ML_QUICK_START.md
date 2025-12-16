# 🚀 How to Run Your Python ML Service

## Method 1: Easiest Way (Using Batch File) ⭐

**From the root directory:**
```bash
start_sentiment_service.bat
```

This will automatically:
- Navigate to python_ml_service
- Start the Flask API on port 5001

---

## Method 2: Manual Start (Recommended for Development)

### Step 1: Open Terminal/Command Prompt

Navigate to your project root:
```bash
cd C:\xampp\htdocs\my_little_thingz
```

### Step 2: Go to Python ML Directory
```bash
cd python_ml_service
```

### Step 3: Activate Virtual Environment
```bash
# On Windows:
venv\Scripts\activate

# You should see (venv) in your prompt like this:
# (venv) C:\xampp\htdocs\my_little_thingz\python_ml_service>
```

### Step 4: Start the Service
```bash
python app.py
```

### Expected Output:
```
 * Serving Flask app 'app'
 * Debug mode: on
WARNING: This is a development server. Do not use it in a production deployment.
 * Running on all addresses (0.0.0.0)
 * Running on http://127.0.0.1:5001
 * Running on http://YOUR_IP:5001
Press CTRL+C to quit
 * Restarting with stat
 * Debugger is active!
```

---

## Method 3: Using the Start Script
```bash
cd python_ml_service
python start_service.py
```

---

## ✅ Verify It's Running

### Test 1: Health Check
Open in browser: `http://localhost:5001/api/ml/health`

Should return:
```json
{
  "status": "healthy",
  "service": "Python ML Microservice",
  "timestamp": "2025-01-XX...",
  "algorithms": ["KNN", "Bayesian", "Decision Tree", "SVM", "BPNN", "Sentiment Analysis"]
}
```

### Test 2: Using cURL
```bash
curl http://localhost:5001/api/ml/health
```

### Test 3: Using Python
```bash
python test_api.py
```

### Test 4: Using Sentiment Test
```bash
python test_sentiment_api.py
```

---

## 🎯 API Endpoints Available

Once running, these endpoints are available:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/ml/health` | GET | Health check |
| `/api/ml/knn/recommendations` | POST | KNN recommendations |
| `/api/ml/bayesian/classify` | POST | Category classification |
| `/api/ml/decision-tree/addon-suggestion` | POST | Add-on suggestions |
| `/api/ml/svm/classify` | POST | Budget/Premium classification |
| `/api/ml/bpnn/predict-preference` | POST | Preference predictions |
| `/api/ml/sentiment/analyze` | POST | Review sentiment analysis |
| `/api/ml/trending/classify` | POST | Trending classification |
| `/api/ml/models/train` | POST | Train all models |

---

## 🔧 Troubleshooting

### Issue 1: "Module not found" error
**Solution:** Install dependencies
```bash
cd python_ml_service
pip install -r requirements.txt
```

### Issue 2: "Port 5001 already in use"
**Solutions:**

**Option A:** Change the port in `app.py` line 603:
```python
app.run(host='0.0.0.0', port=5002, debug=True)
```

**Option B:** Kill the process using port 5001:
```bash
# Windows:
netstat -ano | findstr :5001
taskkill /PID <PID_NUMBER> /F

# Find and kill Python process
tasklist | findstr python
taskkill /F /IM python.exe
```

### Issue 3: Virtual environment not activated
**Solution:** Activate it
```bash
# Windows:
venv\Scripts\activate

# Linux/Mac:
source venv/bin/activate
```

### Issue 4: Import errors
**Solution:** Make sure you're in the right directory
```bash
# Always run from python_ml_service directory
cd python_ml_service
python app.py
```

---

## 📝 Test Examples

### Test Sentiment Analysis:
```bash
curl -X POST http://localhost:5001/api/ml/sentiment/analyze ^
  -H "Content-Type: application/json" ^
  -d "{\"review_text\": \"Great product! Very happy with the purchase.\"}"
```

### Test KNN:
```bash
curl -X POST http://localhost:5001/api/ml/knn/recommendations ^
  -H "Content-Type: application/json" ^
  -d "{\"product_id\": 1, \"k\": 5}"
```

### Test Bayesian:
```bash
curl -X POST http://localhost:5001/api/ml/bayesian/classify ^
  -H "Content-Type: application/json" ^
  -d "{\"gift_name\": \"Wedding Card\"}"
```

---

## 🎨 Using in Browser

Open these URLs in your browser:

1. **Health Check**: http://localhost:5001/api/ml/health
2. **Test Dashboard**: http://localhost/my_little_thingz/backend/ml_algorithms_dashboard.html

---

## 🔄 Running in Background (Production)

### Using Windows Task Scheduler:
1. Create a batch file: `start_ml_service.bat`
2. Content:
```batch
@echo off
cd C:\xampp\htdocs\my_little_thingz\python_ml_service
venv\Scripts\activate
python app.py
```

### Using PM2 (if Node.js installed):
```bash
npm install -g pm2
pm2 start app.py --name "ml-service" --interpreter python
pm2 save
pm2 startup
```

---

## 🎉 Success!

When you see:
```
 * Running on http://127.0.0.1:5001
 * Debugger is active!
```

Your ML service is running! 🚀

---

## 📊 Next Steps

1. ✅ Service running on port 5001
2. ✅ Test health endpoint
3. ✅ Use in admin reviews (sentiment analysis)
4. ✅ Use in frontend for ML recommendations
5. ✅ Access ML dashboard to test all algorithms

**Your Python ML Service is Ready! 🎊**



















