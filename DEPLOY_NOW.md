# Deploy to Render - Quick Steps

## ✅ Files Ready
- `render.yaml` - Deployment configuration
- `frontend/.node-version` - Node 18 specified
- Frontend builds with Vite

## 🚀 Deploy Now

### Step 1: Push to GitHub
```bash
git add .
git commit -m "Fix React dependency conflict for Render"
git push
```

### Step 2: Go to Render
1. Open: https://dashboard.render.com/
2. Click "New +" → "Blueprint"
3. Connect your GitHub repo: `fathima686/my_little_thingz`
4. Click "Apply"

### Step 3: Add Environment Variables
In Render dashboard, add:
- `VITE_GOOGLE_CLIENT_ID` = `12668430306-fg4m3l8mh7hqb84m5s2j7qrtgk7naojm.apps.googleusercontent.com`
- `VITE_RAZORPAY_KEY` = `rzp_test_RGXWGOBliVCIpU`
- `VITE_API_URL` = Your backend URL (IMPORTANT!)

### Step 4: Deploy
Click "Manual Deploy" and wait 2-5 minutes.

## 🎉 Done!
Your site will be live at: `https://my-little-things-frontend.onrender.com`

## ⚠️ Important
Don't forget to set `VITE_API_URL` to your backend URL, otherwise API calls won't work!
