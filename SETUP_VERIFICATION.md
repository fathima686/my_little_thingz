# Setup Verification & Configuration Guide

## Current Status

✓ **PHP Version**: 8.2.12 (Compatible)
✓ **API Key**: Configured in `backend/.env`
❌ **GD Extension**: NOT LOADED - **ACTION REQUIRED**

## Critical: Enable GD Extension

The GD extension is required for image processing (pHash generation). Without it, all image uploads will fail with error code `GD_NOT_AVAILABLE`.

### Steps to Enable GD in XAMPP

1. **Locate php.ini file**:
   - Path: `C:\xampp\php\php.ini`
   - Open with a text editor (Notepad++, VS Code, etc.)

2. **Find the GD extension line**:
   - Search for: `;extension=gd`
   - It should be around line 900-950

3. **Uncomment the line**:
   - Change: `;extension=gd`
   - To: `extension=gd`
   - (Remove the semicolon at the beginning)

4. **Save the file**

5. **Restart Apache**:
   - Open XAMPP Control Panel
   - Click "Stop" on Apache
   - Wait 2 seconds
   - Click "Start" on Apache

6. **Verify GD is loaded**:
   ```bash
   C:\xampp\php\php.exe -m | findstr /i gd
   ```
   Should output: `gd`

### Alternative: Check via Web

Create a file `phpinfo.php` in your web root:
```php
<?php phpinfo(); ?>
```

Visit: `http://localhost/my_little_thingz/phpinfo.php`
Search for "gd" - you should see a GD section with version info.

**IMPORTANT**: Delete `phpinfo.php` after verification for security.

## Verification Checklist

Run these commands to verify your setup:

### 1. Check PHP Version
```bash
C:\xampp\php\php.exe -v
```
Expected: PHP 8.x or higher

### 2. Check GD Extension
```bash
C:\xampp\php\php.exe -r "echo extension_loaded('gd') ? 'GD: OK' : 'GD: MISSING';"
```
Expected: `GD: OK`

### 3. Check Environment Variables
```bash
cd backend
C:\xampp\php\php.exe -r "require 'config/env-loader.php'; EnvLoader::load(); echo getenv('GOOGLE_VISION_API_KEY') ? 'API Key: OK' : 'API Key: MISSING';"
```
Expected: `API Key: OK`

### 4. Run Full Test Suite
```bash
cd backend
C:\xampp\php\php.exe test-image-analysis-fixed.php
```
Expected: All tests pass

## Quick Fix Script

Save this as `check-setup.bat` in your project root:

```batch
@echo off
echo ========================================
echo Image Analysis Setup Verification
echo ========================================
echo.

echo Checking PHP...
C:\xampp\php\php.exe -v
echo.

echo Checking GD Extension...
C:\xampp\php\php.exe -r "echo 'GD Extension: ' . (extension_loaded('gd') ? 'LOADED' : 'NOT LOADED') . PHP_EOL;"
echo.

echo Checking API Key...
cd backend
C:\xampp\php\php.exe -r "require 'config/env-loader.php'; EnvLoader::load(); $key = getenv('GOOGLE_VISION_API_KEY'); echo 'API Key: ' . ($key ? 'CONFIGURED (' . substr($key, 0, 10) . '...)' : 'NOT CONFIGURED') . PHP_EOL;"
cd ..
echo.

echo ========================================
echo Running Full Test Suite...
echo ========================================
cd backend
C:\xampp\php\php.exe test-image-analysis-fixed.php
cd ..

pause
```

Run it: Double-click `check-setup.bat`

## Configuration Files Summary

### 1. backend/.env
```env
RAZORPAY_KEY_ID=rzp_test_RGXWGOBliVCIpU
RAZORPAY_KEY_SECRET=9Q49llzcN0kLD3021OoSstOp

# Google Vision API Configuration
GOOGLE_VISION_API_KEY=AIzaSyCDYZ8HuywIb2Pi_WfqXtosCL2WQ_D4BI4
```
✓ Status: Configured

### 2. C:\xampp\php\php.ini
Find and uncomment:
```ini
extension=gd
```
❌ Status: NOT ENABLED - **ACTION REQUIRED**

### 3. Apache Configuration
After changing php.ini, restart Apache in XAMPP Control Panel.

## Testing After Setup

### Test 1: Basic GD Test
```bash
C:\xampp\php\php.exe -r "if (extension_loaded('gd')) { $img = imagecreatetruecolor(100, 100); echo 'GD works!'; imagedestroy($img); } else { echo 'GD not loaded'; }"
```

### Test 2: Environment Loading
```bash
cd backend
C:\xampp\php\php.exe -r "require 'config/env-loader.php'; EnvLoader::load(); var_dump(getenv('GOOGLE_VISION_API_KEY'));"
```

### Test 3: Full Pipeline Test
```bash
cd backend
C:\xampp\php\php.exe test-image-analysis-fixed.php
```

## Expected Test Output

When everything is configured correctly:

```
=== Image Analysis Pipeline Test ===

Test 1: Environment Variable Loading
-------------------------------------
✓ PASSED: API key loaded successfully
   Key: AIzaSyCDYZ...BI4

Test 2: PHP GD Extension
------------------------
✓ PASSED: GD extension is loaded
   GD Version: bundled (2.1.0 compatible)
   JPEG Support: Yes
   PNG Support: Yes

Test 3: Database Connection
---------------------------
✓ PASSED: Database connection successful

Test 4: Service Initialization
------------------------------
✓ PASSED: Service initialized successfully

Test 5: Test Image Creation
---------------------------
✓ PASSED: Test image created
   Path: uploads/test_image_1234567890.jpg
   Size: 2847 bytes

Test 6: Image Analysis Pipeline
-------------------------------
Analysis Result:
  ✓ Explanation: No similar images found in the platform
  Category: general
  Images Compared: 0
  Requires Review: No

Test 7: Missing File Error Handling
-----------------------------------
✓ PASSED: Missing file error handled correctly
  Error Code: FILE_NOT_FOUND
  Error Message: Image file does not exist

Test 8: Google Vision API Test
------------------------------
Testing Vision API with test image...
✓ PASSED: Vision API working correctly
   Labels detected:
     - rectangle (95.2%)
     - font (89.3%)
     - electric blue (87.1%)

Cleanup
-------
✓ Test image deleted

=== Test Complete ===

Summary:
--------
✓ Environment variables: ✓ Configured
✓ GD Extension: ✓ Loaded
✓ Database: ✓ Connected
✓ Service: ✓ Initialized

All critical components are now properly configured!
```

## Troubleshooting

### Issue: GD Still Not Loading After php.ini Change

**Solution**:
1. Make sure you edited the correct php.ini:
   - CLI: `C:\xampp\php\php.ini`
   - Apache: Same file in XAMPP
2. Verify the line has NO semicolon: `extension=gd`
3. Restart Apache completely (Stop, wait, Start)
4. Check Apache error log: `C:\xampp\apache\logs\error.log`

### Issue: API Key Not Loading

**Solution**:
1. Verify `.env` file location: `backend/.env`
2. Check file has no BOM (use UTF-8 without BOM)
3. Verify no extra spaces around `=`
4. Check file permissions (should be readable)

### Issue: Database Connection Failed

**Solution**:
1. Start MySQL in XAMPP Control Panel
2. Verify database exists: `my_little_thingz`
3. Check credentials in `backend/config/database.php`
4. Test connection: `mysql -u root -p` (no password)

### Issue: Vision API Returns 403 or 401

**Solution**:
1. Verify API key is correct
2. Check API key has Vision API enabled in Google Cloud Console
3. Verify no billing issues in Google Cloud
4. Check API quotas and limits

### Issue: File Upload Fails

**Solution**:
1. Check `upload_max_filesize` in php.ini (should be >= 5M)
2. Check `post_max_size` in php.ini (should be >= 10M)
3. Verify `uploads/practice/` directory exists and is writable
4. Check Apache error log for permission issues

## Security Checklist

- [ ] `.env` file is in `.gitignore`
- [ ] API key is not committed to version control
- [ ] `phpinfo.php` is deleted after testing
- [ ] Upload directory has proper permissions (755)
- [ ] Database credentials are secure
- [ ] Error messages don't expose sensitive info

## Performance Optimization

After setup, consider:
1. Enable OPcache in php.ini for better performance
2. Increase `memory_limit` if processing large images
3. Set up proper error logging
4. Configure log rotation
5. Monitor API usage and quotas

## Next Steps

1. ✓ Enable GD extension in php.ini
2. ✓ Restart Apache
3. ✓ Run verification script
4. ✓ Test with real image upload
5. ✓ Monitor error logs
6. ✓ Update UI to display error messages

## Support

If you continue to have issues:
1. Check Apache error log: `C:\xampp\apache\logs\error.log`
2. Check PHP error log: `C:\xampp\php\logs\php_error_log`
3. Run test script and save output
4. Check database tables exist
5. Verify all files are in correct locations
