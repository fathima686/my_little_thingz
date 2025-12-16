# 🚀 Start Sentiment Analysis Service

## Quick Start

### Step 1: Start the Python Flask Service

Open a **NEW** terminal/PowerShell window and run:

```bash
cd C:\xampp\htdocs\my_little_thingz\python_ml_service
python app.py
```

You should see:
```
Running on http://0.0.0.0:5001
```

**IMPORTANT:** Keep this terminal open! The service needs to be running for sentiment analysis to work.

### Step 2: Open Your Admin Dashboard

1. Open your browser
2. Go to: `http://localhost:5173` (or your frontend URL)
3. Log in as admin
4. Click "Customer Reviews" in the sidebar
5. You'll now see sentiment badges!

## What You'll See

Each review will show a colored badge:

- 🟢 **POSITIVE** (Green) - Good reviews
- 🔴 **NEGATIVE** (Red) - Reviews with issues  
- 🟡 **NEUTRAL** (Yellow) - Average reviews

## Troubleshooting

### No sentiment badges showing?
- Make sure the Flask service is running on port 5001
- Check browser console for errors (F12)
- Try refreshing the reviews page

### "Connection refused" error?
- Make sure you started `python app.py` from the `python_ml_service` folder
- Check that port 5001 is not being used by another program

### Running the Python service in background?

If you want to run it in the background on Windows, you can use:
```bash
cd C:\xampp\htdocs\my_little_thingz\python_ml_service
start python app.py
```

Or use PowerShell with `Start-Process`:
```powershell
cd C:\xampp\htdocs\my_little_thingz\python_ml_service
Start-Process python -ArgumentList "app.py"
```

## Files Modified

- ✅ `frontend/src/pages/AdminDashboard.jsx` - Added sentiment display
- ✅ `python_ml_service/app.py` - Added sentiment API
- ✅ `python_ml_service/gift_review_sentiment_analysis.py` - ML model

## Test It

After starting the service, you should see your reviews like this:

```
Review #6 • wedding card
Rating: 5/5 • 10/27/2025

Customer Review:
"good product"
✓ POSITIVE (52.8%)
```

---

**Note:** The Python service must be running at all times for sentiment analysis to work in the admin dashboard.




















