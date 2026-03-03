# Setup Instructions for Windows

## Quick Fix for Your Current Issue

You're in PowerShell and encountered some errors. Here's how to fix them:

### Step 1: Create Virtual Environment

```powershell
python -m venv venv
```

### Step 2: Activate Virtual Environment

```powershell
.\venv\Scripts\Activate.ps1
```

**If you get an execution policy error**, run this first:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

Then try activating again:
```powershell
.\venv\Scripts\Activate.ps1
```

### Step 3: Upgrade pip

```powershell
python -m pip install --upgrade pip
```

### Step 4: Install Dependencies

```powershell
pip install -r requirements.txt
```

This will take 5-10 minutes. Wait for it to complete.

### Step 5: Start the Service

```powershell
python main.py
```

You should see:
```
INFO:     Uvicorn running on http://0.0.0.0:8001
INFO:     Application startup complete.
```

### Step 6: Test

Open browser: `http://localhost:8001/test-ui.html`

---

## Alternative: Use Command Prompt Instead

If PowerShell gives you trouble, use Command Prompt (cmd):

1. Open Command Prompt (not PowerShell)
2. Navigate to directory:
   ```cmd
   cd C:\xampp\htdocs\my_little_thingz\ai_service
   ```
3. Run setup:
   ```cmd
   setup.bat
   ```

---

## Troubleshooting

### Error: "python not found"
- Install Python 3.10+ from python.org
- Make sure to check "Add Python to PATH" during installation

### Error: "execution policy"
Run this in PowerShell as Administrator:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Error: "No module named 'fastapi'"
You need to activate the virtual environment first:
```powershell
.\venv\Scripts\Activate.ps1
```

Then your prompt should show `(venv)` at the beginning.

---

## Quick Commands Reference

### PowerShell
```powershell
# Create venv
python -m venv venv

# Activate
.\venv\Scripts\Activate.ps1

# Install
pip install -r requirements.txt

# Run
python main.py
```

### Command Prompt (cmd)
```cmd
# Create venv
python -m venv venv

# Activate
venv\Scripts\activate.bat

# Install
pip install -r requirements.txt

# Run
python main.py
```

---

## What You Need

- Python 3.10 or higher
- 10GB free disk space
- Internet connection (for first-time setup)
- Administrator access (for execution policy)

---

## Next Steps After Setup

1. Service should be running at `http://localhost:8001`
2. Open test UI: `http://localhost:8001/test-ui.html`
3. Try generating an image with prompt: "a golden trophy"
4. Check the documentation for integration steps
