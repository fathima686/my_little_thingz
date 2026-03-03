# PowerShell Commands for AI Image Generation Service

## 🎯 Quick Commands Reference

### Check Service Health

```powershell
Invoke-WebRequest -Uri "http://localhost:8001/health" -UseBasicParsing | Select-Object -ExpandProperty Content
```

**Expected output:**
```json
{"status":"healthy","gemini":"configured","stable_diffusion":"ready"}
```

---

### Generate an Image

```powershell
$body = @{ prompt = "a golden trophy" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

**Expected output:**
```json
{
  "image_url": "http://localhost:8001/images/ai_generated_20260116_143022.png",
  "refined_prompt": "A professional golden trophy...",
  "original_prompt": "a golden trophy"
}
```

---

### Open Test UI in Browser

```powershell
Start-Process "http://localhost:8001/test-ui.html"
```

This will open your default browser with the test interface.

---

### View Generated Images

```powershell
Get-ChildItem .\generated_images\
```

Shows all generated images with timestamps.

---

### View Latest Generated Image

```powershell
Get-ChildItem .\generated_images\ | Sort-Object LastWriteTime -Descending | Select-Object -First 1
```

---

### Open Generated Images Folder

```powershell
explorer.exe .\generated_images\
```

Opens the folder in Windows Explorer.

---

### Start the Service

```powershell
cd C:\xampp\htdocs\my_little_thingz\ai_service
.\venv\Scripts\python.exe main.py
```

**Note:** This will keep the terminal busy. Open a new terminal for other commands.

---

### Check if Service is Running

```powershell
Test-NetConnection -ComputerName localhost -Port 8001
```

**Expected output:**
```
TcpTestSucceeded : True
```

---

### Find Process Using Port 8001

```powershell
netstat -ano | findstr :8001
```

---

### Kill Process on Port 8001 (if needed)

```powershell
# First find the PID
$pid = (Get-NetTCPConnection -LocalPort 8001).OwningProcess
# Then kill it
Stop-Process -Id $pid -Force
```

---

## 🎨 Example Prompts to Test

### Simple Test
```powershell
$body = @{ prompt = "a red apple" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

### Professional Design
```powershell
$body = @{ prompt = "professional certificate border with elegant floral design" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

### Abstract Art
```powershell
$body = @{ prompt = "abstract geometric pattern in blue and gold" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

---

## 📊 Monitor Service Logs

If you started the service in a terminal, you can see real-time logs there.

To view logs in a separate window:
```powershell
# The service outputs to the terminal where it was started
# Keep that terminal open to see logs
```

---

## 🔧 Troubleshooting Commands

### Check Python Version
```powershell
python --version
```

### Check if Virtual Environment is Activated
```powershell
# Your prompt should show (venv) at the beginning
# If not, activate it:
.\venv\Scripts\Activate.ps1
```

### List Installed Packages
```powershell
.\venv\Scripts\pip.exe list
```

### Check Disk Space
```powershell
Get-PSDrive C | Select-Object Used,Free
```

### Check Memory Usage
```powershell
Get-Process python | Select-Object Name,CPU,WorkingSet
```

---

## 🎯 Complete Workflow Example

```powershell
# 1. Navigate to service directory
cd C:\xampp\htdocs\my_little_thingz\ai_service

# 2. Check if service is running
Test-NetConnection -ComputerName localhost -Port 8001

# 3. If not running, start it (in a separate terminal)
.\venv\Scripts\python.exe main.py

# 4. Wait a few seconds, then check health
Start-Sleep -Seconds 5
Invoke-WebRequest -Uri "http://localhost:8001/health" -UseBasicParsing

# 5. Generate an image
$body = @{ prompt = "a golden trophy" } | ConvertTo-Json
$result = Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"

# 6. Display the result
$result | ConvertTo-Json -Depth 10

# 7. Open the image in browser
Start-Process $result.image_url

# 8. View all generated images
Get-ChildItem .\generated_images\
```

---

## 💡 Pro Tips

### Save Result to Variable
```powershell
$result = Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
$result.image_url
$result.refined_prompt
```

### Download Image Directly
```powershell
$result = Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
Invoke-WebRequest -Uri $result.image_url -OutFile "my_image.png"
```

### Generate Multiple Images
```powershell
$prompts = @("a red apple", "a blue butterfly", "a golden trophy")
foreach ($prompt in $prompts) {
    $body = @{ prompt = $prompt } | ConvertTo-Json
    $result = Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
    Write-Host "Generated: $($result.image_url)"
}
```

### Pretty Print JSON Response
```powershell
$result | ConvertTo-Json -Depth 10 | Out-Host
```

---

## 🚀 Quick Start Commands

**Just want to test quickly?**

```powershell
# Open test UI (easiest way)
Start-Process "http://localhost:8001/test-ui.html"

# Or test with PowerShell
$body = @{ prompt = "a beautiful sunset" } | ConvertTo-Json
Invoke-RestMethod -Uri http://localhost:8001/generate-image -Method Post -Body $body -ContentType "application/json"
```

---

## 📝 Notes

- **First generation takes 2-5 minutes** (model loading)
- **Subsequent generations take 30-90 seconds**
- **Keep service running** for faster generations
- **Images saved in** `generated_images/` folder
- **Service runs on** `http://localhost:8001`

---

**Happy generating!** 🎨✨
