# 🔧 Troubleshooting Guide - My Little Thingz

## 🚨 Common Issues & Solutions

### 1. **ERR_CONNECTION_REFUSED Errors**

**Problem:** APIs returning `net::ERR_CONNECTION_REFUSED`

**Solutions:**
1. **Start your web server:**
   ```bash
   # For XAMPP users
   Start Apache and MySQL from XAMPP Control Panel
   
   # For WAMP users
   Start WAMP services
   
   # For built-in PHP server
   cd /path/to/your/project
   php -S localhost:8000
   ```

2. **Check server status:**
   - Open: `http://localhost/my_little_thingz/backend/test-server-status-json.php`
   - Should return JSON with server info

3. **Verify project path:**
   - Ensure project is in correct web server directory
   - XAMPP: `C:\xampp\htdocs\my_little_thingz`
   - WAMP: `C:\wamp64\www\my_little_thingz`

### 2. **CORS Policy Errors**

**Problem:** `blocked by CORS policy: Request header field x-admin-user-id is not allowed`

**Solution:** Headers are now fixed in APIs. If still occurring:
1. Clear browser cache
2. Use the diagnostic tool: `backend/fix-all-api-issues.html`

### 3. **500 Internal Server Errors**

**Problem:** APIs returning HTML error pages instead of JSON

**Solutions:**
1. **Check PHP errors:**
   ```php
   // Add to top of problematic PHP file
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

2. **Database connection issues:**
   - Verify `backend/config/database.php` exists and has correct credentials
   - Test connection: `backend/api/test-db-connection.php`

3. **Missing database tables:**
   - Run setup scripts in this order:
     ```
     backend/setup-notifications-and-profiles.php
     backend/setup-pro-features.php
     backend/setup-assignment-database.php
     ```

### 4. **React Development Server Issues**

**Problem:** Frontend can't connect to backend APIs

**Solutions:**
1. **Update API URLs in React components:**
   ```javascript
   // Change from:
   const API_URL = '/my_little_thingz/backend/api';
   
   // To (for development):
   const API_URL = 'http://localhost/my_little_thingz/backend/api';
   ```

2. **Add proxy to package.json:**
   ```json
   {
     "proxy": "http://localhost"
   }
   ```

### 5. **Database Connection Failed**

**Problem:** `Database connection failed` errors

**Solutions:**
1. **Check MySQL service:**
   - Ensure MySQL/MariaDB is running
   - Default port 3306 should be accessible

2. **Verify database credentials:**
   ```php
   // In backend/config/database.php
   private $host = "localhost";
   private $db_name = "my_little_thingz";
   private $username = "root";
   private $password = ""; // Your MySQL password
   ```

3. **Create database if missing:**
   ```sql
   CREATE DATABASE my_little_thingz;
   ```

## 🛠️ Quick Fix Tools

### 1. **Automated Diagnostic Tool**
Open: `backend/fix-all-api-issues.html`
- Tests all API endpoints
- Checks database connectivity
- Identifies CORS issues
- Creates sample data

### 2. **Server Status Checker**
Open: `backend/test-server-status-json.php`
- Shows server configuration
- Tests database connection
- Lists PHP extensions

### 3. **Custom Requests Test**
Open: `backend/test-custom-requests.html`
- Tests custom requests system
- Verifies admin and customer APIs
- Creates sample requests

## 📋 Step-by-Step Troubleshooting

### Step 1: Verify Server is Running
```bash
# Test if server responds
curl http://localhost/my_little_thingz/backend/test-server-status-json.php
```

### Step 2: Test Database Connection
```bash
# Test database
curl http://localhost/my_little_thingz/backend/api/test-db-connection.php
```

### Step 3: Test Individual APIs
```bash
# Test notifications API
curl -H "X-Tutorial-Email: test@example.com" \
     http://localhost/my_little_thingz/backend/api/customer/notifications.php

# Test profile API
curl -H "X-Tutorial-Email: test@example.com" \
     http://localhost/my_little_thingz/backend/api/customer/profile.php
```

### Step 4: Check Browser Console
1. Open browser Developer Tools (F12)
2. Check Console tab for JavaScript errors
3. Check Network tab for failed requests
4. Look for specific error messages

## 🔍 Common Error Messages & Fixes

| Error | Cause | Solution |
|-------|-------|----------|
| `ERR_CONNECTION_REFUSED` | Server not running | Start Apache/Nginx |
| `500 Internal Server Error` | PHP/Database error | Check error logs |
| `CORS policy blocked` | Missing CORS headers | Use updated API files |
| `Failed to fetch` | Network/URL issue | Check API URLs |
| `Unexpected token '<'` | HTML returned instead of JSON | Fix PHP errors |

## 🚀 Development Server Setup

### Option 1: XAMPP/WAMP (Recommended)
1. Install XAMPP or WAMP
2. Place project in `htdocs` or `www` folder
3. Start Apache and MySQL
4. Access via `http://localhost/my_little_thingz`

### Option 2: Built-in PHP Server
```bash
cd /path/to/my_little_thingz
php -S localhost:8000
```
Access via `http://localhost:8000`

### Option 3: Using the Starter Script
```bash
php backend/start-local-server.php
```

## 📞 Still Having Issues?

1. **Run the diagnostic tool:** `backend/fix-all-api-issues.html`
2. **Check server logs:** Look in Apache/PHP error logs
3. **Verify file permissions:** Ensure PHP can read/write files
4. **Test with simple API:** Start with `test-server-status-json.php`

## 🎯 Quick Test Checklist

- [ ] Web server (Apache/Nginx) is running
- [ ] MySQL/MariaDB is running
- [ ] Project is in correct web directory
- [ ] Database exists and is accessible
- [ ] PHP extensions (PDO, MySQL) are loaded
- [ ] No PHP syntax errors in API files
- [ ] CORS headers are properly set
- [ ] Frontend is using correct API URLs

---

**Need more help?** Check the diagnostic tool at `backend/fix-all-api-issues.html` for automated testing and fixes.