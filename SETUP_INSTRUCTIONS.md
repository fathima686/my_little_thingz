# 🚀 Quick Setup Instructions

## Step-by-Step Setup Guide

### 1. Environment Configuration

```bash
# Navigate to backend directory
cd backend

# Copy environment file
cp .env.example .env

# Edit .env file and add your Google Vision API key (optional)
# GOOGLE_VISION_API_KEY=your_actual_api_key_here
```

### 2. Google Vision API Setup (Optional but Recommended)

#### Get API Key:
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Cloud Vision API**:
   - Navigate to "APIs & Services" > "Library"
   - Search for "Cloud Vision API"
   - Click "Enable"
4. Create credentials:
   - Go to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "API Key"
   - Copy the API key
5. Add to `.env`:
   ```env
   GOOGLE_VISION_API_KEY=AIzaSyXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
   ```

**Note**: System works without API key, but AI content warnings will be disabled.

### 3. Database Setup

The system automatically creates required tables on first use. No manual SQL execution needed!

Tables that will be created:
- `image_authenticity_v2`
- `admin_review_v2`

Existing tables that will be updated:
- `practice_uploads` (adds columns if missing)
- `learning_progress` (adds columns if missing)

### 4. Update Frontend API Endpoint

#### Option A: Update Existing Upload Component

Find your practice upload component (e.g., `frontend/src/components/PracticeUpload.jsx`) and update the API endpoint:

```javascript
// OLD
const response = await fetch('/backend/api/pro/practice-upload.php', {
    method: 'POST',
    headers: {
        'X-Tutorial-Email': userEmail
    },
    body: formData
});

// NEW (V2)
const response = await fetch('/backend/api/pro/practice-upload-v2.php', {
    method: 'POST',
    headers: {
        'X-Tutorial-Email': userEmail
    },
    body: formData
});
```

#### Option B: Test with HTML Form First

Create a test file `test-upload-v2.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Test Practice Upload V2</title>
</head>
<body>
    <h1>Test Practice Upload V2</h1>
    <form id="uploadForm">
        <input type="email" name="email" placeholder="Your email" required><br><br>
        <input type="number" name="tutorial_id" placeholder="Tutorial ID" required><br><br>
        <textarea name="description" placeholder="Description"></textarea><br><br>
        <input type="file" name="practice_images[]" multiple accept="image/*" required><br><br>
        <button type="submit">Upload</button>
    </form>
    <div id="result"></div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const email = formData.get('email');

            try {
                const response = await fetch('/backend/api/pro/practice-upload-v2.php', {
                    method: 'POST',
                    headers: {
                        'X-Tutorial-Email': email
                    },
                    body: formData
                });

                const result = await response.json();
                document.getElementById('result').innerHTML = 
                    '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            } catch (error) {
                document.getElementById('result').innerHTML = 
                    '<p style="color: red;">Error: ' + error.message + '</p>';
            }
        });
    </script>
</body>
</html>
```

### 5. Access Admin Dashboard

Open in browser:
```
http://localhost/frontend/admin/image-review-dashboard-v2.html
```

Or if using a different setup:
```
http://your-domain.com/frontend/admin/image-review-dashboard-v2.html
```

**Default Admin Email**: Update the email in the dashboard to match your admin account.

### 6. Test the System

#### Test 1: Upload Clean Image
1. Select a tutorial (e.g., Embroidery)
2. Upload an embroidery work photo
3. Should be auto-approved (no AI warning, no similarity)

#### Test 2: Upload Unrelated Image
1. Select a tutorial (e.g., Embroidery)
2. Upload a photo of a person or landscape
3. Should be flagged with AI warning: "possibly_unrelated"

#### Test 3: Upload Duplicate
1. Upload the same image twice to the same tutorial
2. Second upload should be flagged: "possible_reuse"

#### Test 4: Admin Review
1. Go to admin dashboard
2. See flagged images
3. Approve or reject with notes
4. Check student progress updates

### 7. Verify Installation

Check if everything is working:

```bash
# Check PHP GD extension
php -m | grep gd

# Check database tables
mysql -u root -p my_little_thingz -e "SHOW TABLES LIKE '%authenticity%';"

# Check API endpoint
curl -X GET "http://localhost/backend/api/admin/image-review-v2.php?status=pending" \
     -H "X-Admin-Email: admin@example.com"
```

### 8. Troubleshooting

#### Issue: "Failed to generate perceptual hash"
**Solution**: Install PHP GD extension
```bash
# Ubuntu/Debian
sudo apt-get install php-gd
sudo service apache2 restart

# Windows (XAMPP)
# Uncomment in php.ini: extension=gd
```

#### Issue: "Google Vision API error"
**Solution**: 
1. Check API key in `.env`
2. Verify API is enabled in Google Cloud Console
3. Check API quota/billing

#### Issue: "Table doesn't exist"
**Solution**: Tables are created automatically. If not:
```sql
-- Run manually
CREATE TABLE IF NOT EXISTS `image_authenticity_v2` (
  -- See IMAGE_AUTHENTICITY_V2_README.md for full schema
);
```

#### Issue: "Admin authentication failed"
**Solution**: Ensure admin user exists with role='admin'
```sql
UPDATE users SET role = 'admin' WHERE email = 'your-admin@example.com';
```

### 9. Migration from Old System

If you have existing data:

```sql
-- Optional: Migrate old data to new tables
INSERT INTO image_authenticity_v2 
(image_id, image_type, user_id, tutorial_id, category, evaluation_status, created_at)
SELECT 
    image_id, 
    image_type, 
    user_id, 
    tutorial_id, 
    category, 
    'unique' as evaluation_status,
    created_at
FROM image_authenticity_simple
WHERE admin_decision = 'approved';
```

### 10. Production Deployment

Before going live:

1. **Set environment to production**:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Secure API endpoints**:
   - Add proper authentication
   - Enable HTTPS
   - Set CORS properly

3. **Monitor API usage**:
   - Google Vision API has free tier limits
   - Monitor costs in Google Cloud Console

4. **Backup database**:
   ```bash
   mysqldump -u root -p my_little_thingz > backup_$(date +%Y%m%d).sql
   ```

5. **Test thoroughly**:
   - Upload various image types
   - Test admin approval flow
   - Verify progress updates
   - Check certificate eligibility

### 11. Performance Optimization

For high-traffic sites:

1. **Add caching**:
   ```php
   // Cache pHash results
   $redis->set("phash:$imageId", $pHash, 3600);
   ```

2. **Optimize database**:
   ```sql
   -- Add indexes
   CREATE INDEX idx_category_phash ON image_authenticity_v2(category, phash(64));
   ```

3. **Batch processing**:
   - Process images in background queue
   - Use cron jobs for heavy operations

### 12. Monitoring & Maintenance

Set up monitoring:

```bash
# Check error logs
tail -f backend/logs/error.log

# Monitor API calls
tail -f backend/logs/api.log

# Check database size
mysql -u root -p -e "
SELECT 
    table_name, 
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'my_little_thingz' 
AND table_name LIKE '%authenticity%';
"
```

## ✅ Checklist

- [ ] Environment file configured (`.env`)
- [ ] Google Vision API key added (optional)
- [ ] Database tables created automatically
- [ ] Frontend API endpoint updated
- [ ] Admin dashboard accessible
- [ ] Test uploads working
- [ ] AI warnings working (if API key configured)
- [ ] Admin review flow working
- [ ] Progress updates working
- [ ] Certificate eligibility working

## 📞 Need Help?

1. Check `IMAGE_AUTHENTICITY_V2_README.md` for detailed documentation
2. Review error logs in `backend/logs/`
3. Enable debug mode: `APP_DEBUG=true` in `.env`
4. Check browser console for frontend errors
5. Verify database connections and permissions

## 🎉 You're Done!

The system is now ready to:
- ✅ Detect similar images within same category
- ✅ Warn about unrelated content using AI
- ✅ Require admin approval for flagged images
- ✅ Update student progress correctly
- ✅ Calculate certificate eligibility

**Next Steps**:
1. Train admins on review process
2. Monitor first few days of usage
3. Adjust thresholds if needed
4. Collect feedback from students

---

**Last Updated**: January 14, 2026
**Version**: 2.0
**Status**: Production Ready ✅
