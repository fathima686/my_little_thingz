# Practice Feedback Module - Quick Reference

## 🚀 Quick Start

### Test the API
```bash
php test-feedback-api.php
```

### Test in Browser
```
Open: test-feedback-module.html
Enter email: soudhame52@gmail.com
Click: Load Feedback
```

### Integrate in React
```jsx
import PracticeUploadFeedback from './components/PracticeUploadFeedback';
import './components/PracticeUploadFeedback.css';

<PracticeUploadFeedback userEmail="user@example.com" />
```

## 📊 Status Indicators

| Status | Color | Icon | Meaning |
|--------|-------|------|---------|
| Approved | 🟢 Green | ✅ | Passed validation |
| Rejected | 🔴 Red | ❌ | Failed validation |
| Under Review | 🟡 Yellow | ⏳ | Pending admin review |
| Pending | ⚪ Gray | 🕐 | Awaiting validation |

## 🎯 API Endpoint

```
GET /backend/api/pro/practice-upload-feedback.php
```

**Parameters**:
- `email` (required)
- `tutorial_id` (optional)
- `upload_id` (optional)
- `limit` (optional, default: 20)

**Example**:
```
/practice-upload-feedback.php?email=user@example.com&tutorial_id=5
```

## 📦 Response Structure

```json
{
  "status": "success",
  "uploads": [
    {
      "upload_id": 59,
      "overall_status": "approved",
      "status_color": "green",
      "ai_validation": {
        "predicted_category": "hand_embroidery",
        "confidence": 85.5,
        "category_matches": true
      },
      "ai_detection": {
        "risk_score": 15,
        "risk_level": "low"
      },
      "feedback": {
        "primary": "Your practice work has been approved!",
        "ai_explanation": "...",
        "next_steps": "..."
      }
    }
  ]
}
```

## 🎨 Component Props

```jsx
<PracticeUploadFeedback 
  userEmail="user@example.com"  // Required
  tutorialId={5}                // Optional: filter by tutorial
/>
```

## 🔍 What's Displayed

### For Approved ✅
- Success message
- AI classification details
- Confidence score
- Progress update confirmation
- "Continue Learning" button

### For Rejected ❌
- Rejection reason
- AI explanation
- Specific issues found
- "Upload New Image" button
- "View Guidelines" button

### For Under Review ⏳
- Review status
- Expected timeline (24-48 hours)
- Reason for flagging
- "Check Back Later" message

## 🧪 Testing Checklist

- [ ] API returns 200 status
- [ ] Feedback data loads
- [ ] Status colors display correctly
- [ ] AI validation shows
- [ ] AI detection shows (if available)
- [ ] Action buttons work
- [ ] Images preview correctly
- [ ] Responsive on mobile

## 📁 Files

```
backend/api/pro/
  └─ practice-upload-feedback.php

frontend/src/components/
  ├─ PracticeUploadFeedback.jsx
  └─ PracticeUploadFeedback.css

tests/
  ├─ test-feedback-module.html
  └─ test-feedback-api.php

docs/
  ├─ PRACTICE_FEEDBACK_MODULE_README.md
  ├─ FEEDBACK_MODULE_IMPLEMENTATION_COMPLETE.md
  └─ FEEDBACK_MODULE_QUICK_REFERENCE.md (this file)
```

## 🐛 Troubleshooting

### No feedback showing
```bash
# Check if uploads exist
php check-practice-tables.php

# Test API directly
php test-feedback-api.php
```

### Missing AI detection data
```bash
# Install OpenCV
pip install opencv-python

# Restart Flask API
cd python_ml_service
python craft_flask_api.py
```

### Wrong status colors
Check database:
```sql
SELECT id, status, craft_validation_status 
FROM practice_uploads 
WHERE user_id = 19 
ORDER BY id DESC LIMIT 5;
```

## 💡 Tips

1. **Test First**: Use `test-feedback-module.html` before integrating
2. **Check API**: Verify endpoint returns data
3. **Customize**: Modify CSS to match your design
4. **Monitor**: Track which feedback helps learners most
5. **Iterate**: Improve messages based on user feedback

## 📞 Support

- **API Test**: `php test-feedback-api.php`
- **Browser Test**: `test-feedback-module.html`
- **Database Check**: `php check-practice-tables.php`
- **Documentation**: `PRACTICE_FEEDBACK_MODULE_README.md`

---

**Quick Start**: Open `test-feedback-module.html` → Enter email → Load Feedback ✅
