# Image Authenticity & Practice Validation System V2

## 🎯 Overview

Enhanced image authenticity system that correctly handles unclear/unrelated images and reduces false similarity detection without rebuilding the project architecture.

## ✅ Key Features

### 1. **Category Enforcement**
- Uses tutorial/category selected by student as ground truth
- Does NOT auto-detect category from image
- Performs similarity comparison ONLY within the same category

### 2. **Pre-trained AI Integration (Warning System)**
- Uses **Google Vision API - Label Detection**
- Analyzes labels: person, landscape, animal, object, etc.
- If labels indicate unrelated content with confidence ≥ 0.80:
  - Marks image as `possibly_unrelated`
  - Shows warning to student
  - Does NOT auto-reject

### 3. **Similarity Detection (pHash Only)**
- Uses ONLY perceptual hash (pHash)
- Removed multi-hash logic (aHash, dHash, wavelet)
- Strict threshold: pHash distance ≤ 5 → `possible_reuse`
- Compares only against admin-approved images in same category

### 4. **Decision Logic (Mandatory)**
```
IF (possibly_unrelated == true OR phash_distance ≤ 5)
    → evaluation_status = 'needs_admin_review'
ELSE
    → evaluation_status = 'unique'
```
- Never auto-rejects
- Never auto-approves practice submissions
- Admin is final authority

### 5. **Metadata Handling**
- Extracts EXIF metadata using PHP EXIF functions
- Used only for admin reference
- Missing metadata does NOT affect evaluation

### 6. **Progress & Certificate Rules**
- Practice progress increases only after admin approval
- Certificate unlocks only when overall progress ≥ 80%

## 🔧 Technology Stack (Existing - Not Changed)

- **Backend**: PHP 7.4+ (OOP)
- **Database**: MySQL 8.0+ (InnoDB)
- **Database Access**: PDO
- **Image Processing**: PHP GD Extension
- **Frontend**: React.js 18+
- **API Communication**: Fetch API (async/await)
- **Styling**: CSS3 / Flexbox / Grid
- **Similarity**: pHash (Perceptual Hash)
- **AI**: Google Vision API (Pre-trained, Label Detection only)

## 📦 Installation & Setup

### Step 1: Copy Environment File
```bash
cd backend
cp .env.example .env
```

### Step 2: Configure Google Vision API (Optional but Recommended)

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable **Cloud Vision API**
4. Create API credentials (API Key)
5. Copy the API key to `.env`:
```env
GOOGLE_VISION_API_KEY=your_actual_api_key_here
```

**Note**: System works without Google Vision API, but AI content warnings will be disabled.

### Step 3: Database Setup

The system automatically creates required tables on first use. Tables created:
- `image_authenticity_v2` - Main authenticity records
- `admin_review_v2` - Admin review queue

Existing tables updated:
- `practice_uploads` - Added `authenticity_status`, `progress_approved`
- `learning_progress` - Added `practice_admin_approved`

### Step 4: Update API Endpoint

Update your frontend to use the new API endpoint:

**Old endpoint:**
```javascript
const response = await fetch('/backend/api/pro/practice-upload.php', {
    method: 'POST',
    headers: {
        'X-Tutorial-Email': userEmail
    },
    body: formData
});
```

**New endpoint (V2):**
```javascript
const response = await fetch('/backend/api/pro/practice-upload-v2.php', {
    method: 'POST',
    headers: {
        'X-Tutorial-Email': userEmail
    },
    body: formData
});
```

## 🔌 API Endpoints

### 1. Practice Upload API (Student)
**Endpoint**: `POST /backend/api/pro/practice-upload-v2.php`

**Headers**:
```
X-Tutorial-Email: student@example.com
Content-Type: multipart/form-data
```

**Form Data**:
- `tutorial_id`: Tutorial ID (required)
- `description`: Practice description (optional)
- `practice_images[]`: Image files (required, multiple)

**Response**:
```json
{
    "status": "success",
    "message": "Practice work uploaded and flagged for admin review",
    "upload_id": 123,
    "files_uploaded": 2,
    "authenticity_analysis": {
        "system_version": "enhanced_v2.0",
        "detection_method": "phash_similarity + ai_content_analysis",
        "comparison_scope": "same_category_only",
        "ai_enabled": true,
        "analysis_results": [
            {
                "image_id": "123_0",
                "file_name": "embroidery_work.jpg",
                "status": "unique",
                "requires_admin_review": false,
                "category": "embroidery",
                "ai_warning": null
            },
            {
                "image_id": "123_1",
                "file_name": "photo.jpg",
                "status": "possibly_unrelated",
                "requires_admin_review": true,
                "category": "embroidery",
                "ai_warning": "Image may contain unrelated content: person (confidence: 92.3%)"
            }
        ],
        "summary": {
            "total_images": 2,
            "unique_images": 1,
            "possible_reuse": 0,
            "possibly_unrelated": 1,
            "requires_admin_review": 1,
            "auto_approved": false
        }
    }
}
```

### 2. Admin Review API (Admin)

#### Get Pending Reviews
**Endpoint**: `GET /backend/api/admin/image-review-v2.php`

**Headers**:
```
X-Admin-Email: admin@example.com
```

**Query Parameters**:
- `status`: pending|approved|rejected (default: pending)
- `category`: Filter by category (optional)
- `limit`: Results per page (default: 50)
- `offset`: Pagination offset (default: 0)

**Response**:
```json
{
    "status": "success",
    "data": {
        "reviews": [
            {
                "review_id": 1,
                "image_id": "123_1",
                "image_type": "practice_upload",
                "user_email": "student@example.com",
                "tutorial_title": "Basic Embroidery",
                "category": "embroidery",
                "evaluation_status": "possibly_unrelated",
                "flagged_reason": "AI detected possibly unrelated content",
                "ai_warning": "Image may contain unrelated content: person (confidence: 92.3%)",
                "metadata_notes": "Dimensions: 1920x1080; Camera: Canon EOS 5D; File size: 2.3 MB",
                "image_urls": [
                    {
                        "url": "../../uploads/practice/practice_5_10_1234567890_1.jpg",
                        "original_name": "photo.jpg",
                        "file_size": 2411520
                    }
                ],
                "flagged_at": "2026-01-14 10:30:00"
            }
        ],
        "total_count": 15,
        "current_page": 1,
        "per_page": 50,
        "has_more": false
    }
}
```

#### Submit Admin Decision
**Endpoint**: `POST /backend/api/admin/image-review-v2.php`

**Headers**:
```
X-Admin-Email: admin@example.com
Content-Type: application/x-www-form-urlencoded
```

**Form Data**:
- `image_id`: Image ID (required)
- `image_type`: practice_upload|custom_request (default: practice_upload)
- `decision`: approved|rejected (required)
- `admin_notes`: Admin feedback (optional)

**Response**:
```json
{
    "status": "success",
    "message": "Image approved successfully",
    "data": {
        "image_id": "123_1",
        "decision": "approved",
        "reviewed_by": 1,
        "reviewed_at": "2026-01-14 11:00:00"
    }
}
```

## 📊 Database Schema

### image_authenticity_v2
```sql
CREATE TABLE `image_authenticity_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `phash` text DEFAULT NULL,
  `evaluation_status` enum('unique', 'possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL DEFAULT 'unique',
  `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `requires_review` tinyint(1) DEFAULT 0,
  `flagged_reason` text DEFAULT NULL,
  `metadata_notes` text DEFAULT NULL,
  `ai_labels` json DEFAULT NULL,
  `ai_warning` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_image` (`image_id`, `image_type`)
);
```

### admin_review_v2
```sql
CREATE TABLE `admin_review_v2` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image_id` varchar(255) NOT NULL,
  `image_type` enum('practice_upload', 'custom_request') NOT NULL,
  `user_id` int(11) NOT NULL,
  `tutorial_id` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL,
  `evaluation_status` enum('possible_reuse', 'possibly_unrelated', 'needs_admin_review') NOT NULL,
  `flagged_reason` text NOT NULL,
  `similar_image_info` json DEFAULT NULL,
  `ai_warning` text DEFAULT NULL,
  `admin_decision` enum('pending', 'approved', 'rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `flagged_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_review` (`image_id`, `image_type`)
);
```

## 🔍 How It Works

### Student Upload Flow
1. Student selects tutorial and uploads practice images
2. System extracts tutorial category (ground truth)
3. For each image:
   - Generate pHash
   - Extract EXIF metadata
   - Call Google Vision API (if configured)
   - Compare pHash with approved images in same category
   - Apply decision logic
4. If flagged → Add to admin review queue
5. If clean → Auto-approve and update progress

### Admin Review Flow
1. Admin views pending reviews in dashboard
2. For each flagged image, admin sees:
   - Original image
   - AI warning (if any)
   - Similar image (if found)
   - EXIF metadata
   - Student information
3. Admin makes decision: Approve or Reject
4. If approved → Update student progress
5. If rejected → Student can re-upload

## ⚠️ Important Notes

1. **No False Claims**: System does NOT claim to detect Google images or internet sources
2. **No Auto-Rejection**: All flagged images require admin review
3. **Category-Specific**: Similarity checked only within same category
4. **AI is Advisory**: AI warnings are for admin reference, not automatic decisions
5. **Metadata Optional**: Missing EXIF data does not affect evaluation
6. **Admin Authority**: Admin decision is final and overrides all automated checks

## 🧪 Testing

### Test Without Google Vision API
1. Set `GOOGLE_VISION_API_KEY=` (empty) in `.env`
2. Upload practice images
3. System will work with pHash similarity only
4. AI warnings will be disabled

### Test With Google Vision API
1. Configure valid API key in `.env`
2. Upload test images:
   - Embroidery work (should pass)
   - Photo of person (should flag as possibly_unrelated)
   - Landscape photo (should flag as possibly_unrelated)
   - Same embroidery work twice (should flag as possible_reuse)

## 📈 Progress & Certificate Logic

### Progress Calculation
```php
// Progress increases only after admin approval
if ($admin_decision === 'approved') {
    UPDATE learning_progress 
    SET practice_completed = 1, 
        practice_admin_approved = 1
    WHERE user_id = ? AND tutorial_id = ?
}
```

### Certificate Eligibility
```php
// Certificate unlocks when overall progress ≥ 80%
$overallProgress = (
    $video_watched + 
    $quiz_completed + 
    $practice_admin_approved
) / total_requirements;

if ($overallProgress >= 0.80) {
    // Unlock certificate
}
```

## 🔒 Security Considerations

1. **File Upload Validation**:
   - Allowed types: JPEG, PNG, GIF, WebP
   - Max file size: 5MB
   - Unique filename generation

2. **Admin Authentication**:
   - Email-based admin verification
   - Role check in database

3. **SQL Injection Prevention**:
   - All queries use PDO prepared statements

4. **API Key Security**:
   - Google Vision API key stored in `.env`
   - Never exposed to frontend

## 🐛 Troubleshooting

### Issue: AI warnings not working
**Solution**: Check if `GOOGLE_VISION_API_KEY` is set in `.env` and API is enabled in Google Cloud Console

### Issue: Images not comparing
**Solution**: Ensure tutorial has a category set. Check `tutorials.category` field.

### Issue: Progress not updating after approval
**Solution**: Check `learning_progress` table and ensure trigger is working correctly.

### Issue: pHash generation fails
**Solution**: Ensure PHP GD extension is installed: `php -m | grep gd`

## 📝 Migration from Old System

If you're migrating from the old system:

1. **Keep old tables**: Don't drop existing tables
2. **Run both systems**: Old API continues to work
3. **Gradual migration**: Update frontend endpoints one by one
4. **Data migration**: Optional - migrate old data to new tables

## 🎓 Best Practices

1. **Regular Admin Review**: Check pending reviews daily
2. **Clear Feedback**: Provide specific admin notes for rejected images
3. **Category Accuracy**: Ensure tutorials have correct categories
4. **API Monitoring**: Monitor Google Vision API usage and costs
5. **Backup**: Regular database backups before major updates

## 📞 Support

For issues or questions:
1. Check logs: `backend/logs/`
2. Enable debug mode: `APP_DEBUG=true` in `.env`
3. Review error logs: `error_log()` messages

## 🔄 Version History

- **V2.0** (2026-01-14): Enhanced system with AI integration and improved similarity detection
- **V1.0**: Basic file hash similarity system

---

**System Status**: ✅ Production Ready
**Last Updated**: January 14, 2026
