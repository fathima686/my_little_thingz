# 🔄 Migration Guide - From Old System to V2

## Overview

This guide helps you migrate from the existing image authenticity system to the new Enhanced V2 system without disrupting current operations.

---

## 🎯 Migration Strategy

### Zero-Downtime Approach

The V2 system is designed to run **alongside** the old system, allowing for gradual migration:

1. ✅ Old APIs continue to work
2. ✅ New APIs use different endpoints
3. ✅ New database tables (no conflicts)
4. ✅ Gradual frontend migration
5. ✅ Rollback capability maintained

---

## 📊 System Comparison

### Old System vs V2

| Feature | Old System | V2 System |
|---------|-----------|-----------|
| **Similarity Detection** | File hash (MD5) | pHash (Perceptual) |
| **AI Integration** | None | Google Vision API |
| **Comparison Scope** | All images | Category-specific |
| **Decision Logic** | Simple | Multi-criteria |
| **Admin Review** | Basic | Enhanced with context |
| **Auto-Rejection** | Possible | Never |
| **Metadata** | Limited | Comprehensive |
| **Progress Tracking** | Basic | Admin-controlled |

---

## 🗄️ Database Migration

### Step 1: Understand Table Differences

#### Old Tables:
- `image_authenticity_simple`
- `admin_review_simple`
- `image_authenticity_basic`

#### New Tables (V2):
- `image_authenticity_v2`
- `admin_review_v2`

**Important**: Old tables are NOT modified or deleted!

### Step 2: Create New Tables

Tables are created automatically on first use, but you can create them manually:

```sql
-- Run this if you want to pre-create tables
SOURCE backend/database/image-authenticity-v2-schema.sql;
```

Or let the system create them automatically:
```php
// EnhancedImageAuthenticityServiceV2 creates tables on first use
$service = new EnhancedImageAuthenticityServiceV2($pdo, $apiKey);
```

### Step 3: Migrate Historical Data (Optional)

If you want to migrate approved images from old system:

```sql
-- Migrate approved images from old system to V2
INSERT INTO image_authenticity_v2 
(image_id, image_type, user_id, tutorial_id, category, 
 evaluation_status, admin_decision, requires_review, created_at)
SELECT 
    image_id,
    image_type,
    user_id,
    tutorial_id,
    category,
    'unique' as evaluation_status,
    'approved' as admin_decision,
    0 as requires_review,
    created_at
FROM image_authenticity_simple
WHERE admin_decision = 'approved'
AND NOT EXISTS (
    SELECT 1 FROM image_authenticity_v2 v2 
    WHERE v2.image_id = image_authenticity_simple.image_id 
    AND v2.image_type = image_authenticity_simple.image_type
);

-- Verify migration
SELECT 
    'Old System' as source, 
    COUNT(*) as total_approved 
FROM image_authenticity_simple 
WHERE admin_decision = 'approved'
UNION ALL
SELECT 
    'V2 System' as source, 
    COUNT(*) as total_approved 
FROM image_authenticity_v2 
WHERE admin_decision = 'approved';
```

**Note**: This is optional. V2 works fine without historical data.

---

## 🔌 API Migration

### Step 1: Identify Current API Usage

Find all places where the old API is called:

```bash
# Search for old API endpoint
grep -r "practice-upload.php" frontend/
grep -r "practice-upload-simple.php" frontend/
grep -r "practice-upload-fixed.php" frontend/
```

### Step 2: Update API Endpoints Gradually

#### Option A: Update All at Once

```javascript
// OLD
const API_ENDPOINT = '/backend/api/pro/practice-upload.php';

// NEW
const API_ENDPOINT = '/backend/api/pro/practice-upload-v2.php';
```

#### Option B: Feature Flag (Recommended)

```javascript
// Use feature flag for gradual rollout
const USE_V2_API = true; // Set to false to rollback

const API_ENDPOINT = USE_V2_API 
    ? '/backend/api/pro/practice-upload-v2.php'
    : '/backend/api/pro/practice-upload.php';
```

#### Option C: A/B Testing

```javascript
// Roll out to 50% of users
const USE_V2_API = Math.random() < 0.5;

const API_ENDPOINT = USE_V2_API 
    ? '/backend/api/pro/practice-upload-v2.php'
    : '/backend/api/pro/practice-upload.php';
```

### Step 3: Update Request Handling

The V2 API has the same request format, so minimal changes needed:

```javascript
// OLD and NEW both work the same way
const formData = new FormData();
formData.append('tutorial_id', tutorialId);
formData.append('description', description);
formData.append('practice_images[]', file);

const response = await fetch(API_ENDPOINT, {
    method: 'POST',
    headers: {
        'X-Tutorial-Email': userEmail
    },
    body: formData
});
```

### Step 4: Update Response Handling

V2 response has more detailed analysis:

```javascript
const result = await response.json();

if (result.status === 'success') {
    // V2 has enhanced analysis
    const analysis = result.authenticity_analysis;
    
    console.log('System Version:', analysis.system_version);
    console.log('AI Enabled:', analysis.ai_enabled);
    console.log('Analysis Results:', analysis.analysis_results);
    
    // Check for warnings
    if (analysis.warnings.ai_warnings.length > 0) {
        showWarning('Some images may contain unrelated content');
    }
    
    if (analysis.warnings.similarity_flags.length > 0) {
        showWarning('Some images are similar to existing work');
    }
    
    // Show appropriate message
    if (analysis.summary.requires_admin_review > 0) {
        showMessage('Upload successful! Awaiting admin review.');
    } else {
        showMessage('Upload successful! Progress updated.');
    }
}
```

---

## 🎨 Frontend Migration

### Step 1: Update Upload Component

#### React Component Example:

```jsx
// OLD
import { uploadPracticeImages } from './api/practice';

// NEW
import { uploadPracticeImagesV2 } from './api/practice-v2';

const PracticeUpload = () => {
    const handleUpload = async (files) => {
        try {
            // Use V2 API
            const result = await uploadPracticeImagesV2({
                tutorialId,
                description,
                images: files
            });
            
            // Handle V2 response
            if (result.authenticity_analysis) {
                const { summary, warnings } = result.authenticity_analysis;
                
                // Show AI warnings if any
                if (warnings.ai_warnings.length > 0) {
                    setAiWarnings(warnings.ai_warnings);
                }
                
                // Show similarity flags if any
                if (warnings.similarity_flags.length > 0) {
                    setSimilarityFlags(warnings.similarity_flags);
                }
                
                // Update UI based on review status
                if (summary.requires_admin_review > 0) {
                    setStatus('pending_review');
                } else {
                    setStatus('approved');
                }
            }
        } catch (error) {
            console.error('Upload failed:', error);
        }
    };
    
    return (
        <div>
            {/* Upload UI */}
            {aiWarnings.length > 0 && (
                <div className="warning">
                    <h4>⚠️ AI Content Warning</h4>
                    {aiWarnings.map((warning, i) => (
                        <p key={i}>{warning}</p>
                    ))}
                </div>
            )}
            {/* Rest of component */}
        </div>
    );
};
```

### Step 2: Add Warning Display

Create a component to show AI warnings:

```jsx
const AIWarningBanner = ({ warnings }) => {
    if (warnings.length === 0) return null;
    
    return (
        <div className="ai-warning-banner">
            <div className="warning-icon">⚠️</div>
            <div className="warning-content">
                <h4>Content Warning</h4>
                <p>Our AI detected that some images may contain unrelated content:</p>
                <ul>
                    {warnings.map((warning, i) => (
                        <li key={i}>{warning}</li>
                    ))}
                </ul>
                <p className="warning-note">
                    Don't worry! An admin will review your submission and provide feedback.
                </p>
            </div>
        </div>
    );
};
```

### Step 3: Update Progress Display

Show pending review status:

```jsx
const ProgressIndicator = ({ tutorial, progress }) => {
    return (
        <div className="progress-indicator">
            <div className="progress-item">
                <span>Video Watched</span>
                <span>{progress.video_watched ? '✅' : '⏸️'}</span>
            </div>
            <div className="progress-item">
                <span>Quiz Completed</span>
                <span>{progress.quiz_completed ? '✅' : '⏸️'}</span>
            </div>
            <div className="progress-item">
                <span>Practice Submitted</span>
                {progress.practice_uploaded && !progress.practice_admin_approved ? (
                    <span className="pending">⏳ Pending Review</span>
                ) : progress.practice_admin_approved ? (
                    <span>✅</span>
                ) : (
                    <span>⏸️</span>
                )}
            </div>
        </div>
    );
};
```

---

## 👨‍💼 Admin Dashboard Migration

### Step 1: Deploy New Dashboard

1. Copy `frontend/admin/image-review-dashboard-v2.html` to your admin folder
2. Update admin navigation to include new dashboard
3. Keep old dashboard accessible during transition

### Step 2: Train Admins

Provide training on new features:
- AI warnings interpretation
- Similarity detection understanding
- Enhanced metadata review
- Decision-making guidelines

### Step 3: Gradual Transition

Week 1:
- [ ] Admins use both dashboards
- [ ] Compare results
- [ ] Provide feedback

Week 2:
- [ ] Primary use of V2 dashboard
- [ ] Old dashboard as backup
- [ ] Monitor admin efficiency

Week 3+:
- [ ] Full migration to V2
- [ ] Retire old dashboard
- [ ] Document lessons learned

---

## 🔄 Rollback Plan

If issues arise, you can rollback easily:

### Step 1: Switch API Endpoint

```javascript
// Revert to old API
const API_ENDPOINT = '/backend/api/pro/practice-upload.php';
```

### Step 2: Use Old Dashboard

```
http://localhost/frontend/admin/image-authenticity-dashboard.html
```

### Step 3: Data Integrity

Old system data is untouched, so rollback is safe:
- Old tables still exist
- Old APIs still work
- No data loss

---

## 📊 Migration Timeline

### Recommended 4-Week Migration Plan

#### Week 1: Preparation
- [ ] Deploy V2 code
- [ ] Configure Google Vision API
- [ ] Test V2 APIs
- [ ] Train admins
- [ ] Create rollback plan

#### Week 2: Pilot
- [ ] Enable V2 for 10% of users
- [ ] Monitor closely
- [ ] Collect feedback
- [ ] Fix issues
- [ ] Adjust thresholds

#### Week 3: Expansion
- [ ] Enable V2 for 50% of users
- [ ] Compare old vs new metrics
- [ ] Admin team fully on V2
- [ ] Document improvements
- [ ] Optimize performance

#### Week 4: Full Migration
- [ ] Enable V2 for 100% of users
- [ ] Retire old dashboard
- [ ] Update documentation
- [ ] Celebrate success! 🎉
- [ ] Plan future improvements

---

## 📈 Success Metrics

Track these metrics during migration:

### Technical Metrics
- [ ] API response time (should be <5s)
- [ ] Error rate (should be <1%)
- [ ] False positive rate (should decrease)
- [ ] Admin review time (should decrease)

### User Metrics
- [ ] Student satisfaction
- [ ] Upload success rate
- [ ] Re-upload rate
- [ ] Certificate completion rate

### Admin Metrics
- [ ] Review queue size
- [ ] Average review time
- [ ] Decision confidence
- [ ] Workload reduction

---

## 🐛 Common Migration Issues

### Issue 1: Google Vision API Not Working

**Symptoms**:
- AI warnings not appearing
- API errors in logs

**Solution**:
```bash
# Check API key
grep GOOGLE_VISION_API_KEY backend/.env

# Test API directly
curl -X POST "https://vision.googleapis.com/v1/images:annotate?key=YOUR_KEY" \
  -H "Content-Type: application/json" \
  -d '{"requests":[{"image":{"source":{"imageUri":"https://example.com/test.jpg"}},"features":[{"type":"LABEL_DETECTION"}]}]}'

# If fails, check:
# 1. API enabled in Google Cloud Console
# 2. Billing enabled
# 3. Quota not exceeded
```

### Issue 2: Tables Not Created

**Symptoms**:
- SQL errors about missing tables
- Upload fails

**Solution**:
```sql
-- Check if tables exist
SHOW TABLES LIKE '%authenticity%';

-- If not, create manually
CREATE TABLE IF NOT EXISTS `image_authenticity_v2` (
  -- See IMAGE_AUTHENTICITY_V2_README.md for schema
);
```

### Issue 3: Progress Not Updating

**Symptoms**:
- Admin approves but progress doesn't update
- Certificate not unlocking

**Solution**:
```sql
-- Check learning_progress table
SELECT * FROM learning_progress 
WHERE user_id = ? AND tutorial_id = ?;

-- Manually update if needed
UPDATE learning_progress 
SET practice_completed = 1, 
    practice_admin_approved = 1
WHERE user_id = ? AND tutorial_id = ?;
```

### Issue 4: Old and New Data Conflicts

**Symptoms**:
- Duplicate entries
- Inconsistent states

**Solution**:
```sql
-- Check for duplicates
SELECT image_id, image_type, COUNT(*) 
FROM image_authenticity_v2 
GROUP BY image_id, image_type 
HAVING COUNT(*) > 1;

-- Remove duplicates (keep latest)
DELETE t1 FROM image_authenticity_v2 t1
INNER JOIN image_authenticity_v2 t2 
WHERE t1.id < t2.id 
AND t1.image_id = t2.image_id 
AND t1.image_type = t2.image_type;
```

---

## ✅ Migration Checklist

### Pre-Migration
- [ ] Backup database
- [ ] Test V2 in staging
- [ ] Configure Google Vision API
- [ ] Train admin team
- [ ] Prepare rollback plan
- [ ] Document current metrics

### During Migration
- [ ] Deploy V2 code
- [ ] Enable for pilot users
- [ ] Monitor closely
- [ ] Collect feedback
- [ ] Fix issues quickly
- [ ] Communicate with users

### Post-Migration
- [ ] Verify all features working
- [ ] Compare metrics
- [ ] Update documentation
- [ ] Retire old code (after 30 days)
- [ ] Celebrate success
- [ ] Plan improvements

---

## 📞 Support During Migration

### Getting Help

1. **Check Documentation**:
   - `IMAGE_AUTHENTICITY_V2_README.md`
   - `SETUP_INSTRUCTIONS.md`
   - `DECISION_LOGIC_REFERENCE.md`

2. **Check Logs**:
   ```bash
   tail -f backend/logs/error.log
   tail -f backend/logs/api.log
   ```

3. **Enable Debug Mode**:
   ```env
   APP_DEBUG=true
   ```

4. **Test Endpoints**:
   ```bash
   # Test upload API
   curl -X POST "http://localhost/backend/api/pro/practice-upload-v2.php" \
     -H "X-Tutorial-Email: test@example.com" \
     -F "tutorial_id=1" \
     -F "practice_images[]=@test.jpg"
   
   # Test admin API
   curl -X GET "http://localhost/backend/api/admin/image-review-v2.php?status=pending" \
     -H "X-Admin-Email: admin@example.com"
   ```

---

## 🎉 Migration Complete!

Once migration is complete:

1. ✅ All users on V2 system
2. ✅ Old APIs deprecated
3. ✅ Admin team trained
4. ✅ Metrics improved
5. ✅ Documentation updated

**Next Steps**:
- Monitor system performance
- Collect user feedback
- Plan future enhancements
- Optimize thresholds based on data

---

**Migration Guide Version**: 2.0
**Last Updated**: January 14, 2026
**Estimated Migration Time**: 4 weeks
**Risk Level**: Low (rollback available)
