# Practice Upload Feedback Module

## Overview

A comprehensive decision feedback system for learners that provides transparent AI validation results, rejection reasons, and actionable guidance for practice uploads.

## Features

### ✅ Structured Feedback Display
- **Status Indicators**: Clear visual badges (green/red/yellow)
- **AI Validation Results**: Detailed classification and confidence scores
- **AI Detection Evidence**: Multi-layer detection results with explanations
- **Rejection Reasons**: Specific, actionable feedback
- **Admin Feedback**: Manual review comments when available
- **Action Items**: Recommended next steps for learners

### 🎯 Transparency for Research
- Complete AI decision-making process visible
- Evidence from all detection layers shown
- Confidence scores and reasoning explained
- Suitable for academic demonstration

### 📊 Decision Categories

#### 1. Approved (Green)
- ✅ Passed all validation checks
- ✅ Category matches tutorial
- ✅ Confidence above threshold
- ✅ No AI-generated suspicion
- ✅ Progress updated

#### 2. Rejected (Red)
- ❌ Failed validation criteria
- Specific reasons provided:
  - Wrong craft category
  - AI-generated suspicion
  - Low confidence score
  - Duplicate detection
- Clear guidance for resubmission

#### 3. Under Review (Yellow)
- ⏳ Flagged for manual review
- Ambiguous AI classification
- Requires admin decision
- Expected timeline provided

## API Endpoint

### GET `/backend/api/pro/practice-upload-feedback.php`

**Parameters:**
- `email` (required): User email
- `tutorial_id` (optional): Filter by tutorial
- `upload_id` (optional): Get specific upload
- `limit` (optional): Max results (default: 20, max: 50)

**Headers:**
- `X-Tutorial-Email`: User email

**Response Structure:**
```json
{
  "status": "success",
  "user_email": "user@example.com",
  "total_uploads": 3,
  "uploads": [
    {
      "upload_id": 59,
      "tutorial_title": "Hand Embroidery Basics",
      "tutorial_category": "Hand Embroidery",
      "upload_date": "2026-02-17 21:44:18",
      "images_count": 1,
      
      "overall_status": "approved",
      "status_label": "Approved",
      "status_color": "green",
      "status_icon": "check-circle",
      
      "ai_validation": {
        "decision": "auto-approve",
        "predicted_category": "hand_embroidery",
        "confidence": 85.5,
        "category_matches": true,
        "requires_review": false,
        "reasons": ["Good confidence category match"]
      },
      
      "ai_detection": {
        "risk_score": 15,
        "risk_level": "low",
        "decision": "pass",
        "metadata_keywords_found": false,
        "exif_camera_present": true,
        "texture_variance": 145.32,
        "watermark_detected": false
      },
      
      "feedback": {
        "primary": "Your practice work has been approved!",
        "details": [
          "✅ Your submission passed all validation checks",
          "✅ Your learning progress has been updated"
        ],
        "ai_explanation": "AI classified your work as 'hand_embroidery' with 85.5% confidence, matching the tutorial category.",
        "next_steps": "Continue to the next tutorial or practice more!"
      },
      
      "action_items": [
        {
          "type": "success",
          "action": "continue",
          "label": "Continue Learning",
          "description": "Move on to the next tutorial"
        }
      ]
    }
  ]
}
```

## React Component Usage

### Installation

```jsx
import PracticeUploadFeedback from './components/PracticeUploadFeedback';
import './components/PracticeUploadFeedback.css';
```

### Basic Usage

```jsx
// In learner dashboard
<PracticeUploadFeedback 
  userEmail="user@example.com"
/>
```

### Tutorial-Specific Feedback

```jsx
// In tutorial page
<PracticeUploadFeedback 
  userEmail="user@example.com"
  tutorialId={5}
/>
```

## Visual Indicators

### Status Colors

| Status | Color | Badge | Meaning |
|--------|-------|-------|---------|
| Approved | Green | ✅ | Passed validation |
| Rejected | Red | ❌ | Failed validation |
| Under Review | Yellow | ⏳ | Pending admin review |
| Pending | Gray | 🕐 | Awaiting validation |

### AI Risk Levels

| Risk Level | Score Range | Color | Action |
|------------|-------------|-------|--------|
| Low | 0-39 | Green | Pass |
| Medium | 40-69 | Yellow | Flag for review |
| High | 70-100 | Red | Auto-reject |

## Feedback Messages

### Approved Example
```
✅ Your practice work has been approved!

✅ Your submission passed all validation checks
✅ Your learning progress has been updated

AI Explanation:
AI classified your work as 'hand_embroidery' with 85.5% confidence, 
matching the tutorial category.

Next Steps:
Continue to the next tutorial or practice more!
```

### Rejected Example
```
❌ Your practice work was not approved

❌ Your submission did not meet validation criteria
• Category mismatch: predicted clay_modeling (75.2% confidence), 
  selected Hand Embroidery

AI Explanation:
AI detected your work as 'clay_modeling' (75.2% confidence), which 
doesn't match the tutorial category 'Hand Embroidery'.

Next Steps:
Please upload a new image that matches the tutorial category and is 
your own work.
```

### Under Review Example
```
⏳ Your practice work is being reviewed by our team

⏳ Your submission is being reviewed by our team
📧 You will receive feedback within 24-48 hours

Flagged for review because:
• Low confidence category match needs review: hand_embroidery (25.3%)

AI Explanation:
AI classified your work as 'hand_embroidery' with 25.3% confidence. 
Manual review is needed to confirm.

Next Steps:
No action needed. Wait for admin review.
```

## AI Detection Evidence Display

### Evidence Categories

1. **Metadata Analysis**
   - AI generator keywords found/not found
   - Specific keywords listed if detected

2. **EXIF Camera Data**
   - Present: ✅ (indicates real photo)
   - Missing: ⚠️ (suspicious for AI)

3. **Texture Smoothness**
   - Laplacian variance value
   - Below threshold = synthetic appearance

4. **Watermark Detection**
   - Detected: ⚠️ (AI platform watermark)
   - None: ✅ (clean image)

### Example Display

```
AI-Generated Image Detection
Risk: 65/100

Risk Level: MEDIUM
Decision: flag

Detection Evidence:
├─ Metadata Keywords: None
├─ Camera EXIF: Missing ⚠️
├─ Texture Variance: 85.50
└─ Watermark: None
```

## Action Items

### For Approved Submissions
- **Continue Learning**: Navigate to next tutorial
- **Practice More**: Upload additional practice work

### For Rejected Submissions
- **Upload New Image**: Retry with corrected submission
- **View Guidelines**: Review submission requirements
- **Contact Support**: Get help understanding rejection

### For Flagged Submissions
- **Check Back Later**: Wait for admin review
- **View Status**: Monitor review progress

## Integration with Dashboard

### Learner Dashboard Integration

```jsx
import React from 'react';
import PracticeUploadFeedback from './components/PracticeUploadFeedback';

const LearnerDashboard = ({ user }) => {
  return (
    <div className="dashboard">
      <h1>My Learning Dashboard</h1>
      
      {/* Other dashboard components */}
      
      <section className="practice-feedback-section">
        <PracticeUploadFeedback userEmail={user.email} />
      </section>
    </div>
  );
};
```

### Tutorial Page Integration

```jsx
import React from 'react';
import PracticeUploadFeedback from './components/PracticeUploadFeedback';

const TutorialPage = ({ tutorial, user }) => {
  return (
    <div className="tutorial-page">
      {/* Tutorial content */}
      
      <section className="practice-section">
        <h2>Your Practice Submissions</h2>
        <PracticeUploadFeedback 
          userEmail={user.email}
          tutorialId={tutorial.id}
        />
      </section>
    </div>
  );
};
```

## Testing

### Test Page
Open `test-feedback-module.html` in your browser to test the API:

1. Enter user email
2. Optionally filter by tutorial or upload ID
3. Click "Load Feedback"
4. View structured feedback display

### API Test
```bash
# Test with curl
curl "http://localhost/my_little_thingz/backend/api/pro/practice-upload-feedback.php?email=user@example.com" \
  -H "X-Tutorial-Email: user@example.com"
```

### Database Query
```sql
-- Check recent uploads with feedback
SELECT 
  pu.id,
  pu.status,
  pu.craft_validation_status,
  cv.predicted_category,
  cv.prediction_confidence,
  cv.ai_risk_score,
  cv.ai_risk_level
FROM practice_uploads pu
LEFT JOIN craft_image_validation_v2 cv ON CONCAT(pu.id, '_0') = cv.image_id
WHERE pu.user_id = 19
ORDER BY pu.id DESC
LIMIT 5;
```

## Transparency & Research

### Academic Demonstration Features

1. **Complete Decision Trail**
   - All AI decisions visible
   - Confidence scores shown
   - Evidence from each layer

2. **Explainable AI**
   - Clear reasoning for decisions
   - Multiple validation layers explained
   - Risk scoring methodology visible

3. **Reproducible Results**
   - Deterministic algorithms
   - Documented thresholds
   - Logged evidence

### Research Use Cases

- Study AI decision-making transparency
- Analyze learner response to feedback
- Evaluate validation accuracy
- Improve detection algorithms
- Train educators on AI systems

## Customization

### Styling
Modify `PracticeUploadFeedback.css` to match your design system:
- Colors
- Typography
- Spacing
- Responsive breakpoints

### Messages
Customize feedback messages in the API:
- Edit `buildFeedbackMessages()` function
- Adjust tone and language
- Add localization support

### Thresholds
Adjust validation thresholds in:
- `CraftImageValidationServiceV2.php`
- `ai_image_detector.py`

## Troubleshooting

### No Feedback Showing
- Check user email is correct
- Verify uploads exist in database
- Check API endpoint is accessible

### Missing AI Detection Data
- AI detector may not be enabled
- Install OpenCV: `pip install opencv-python`
- Restart Flask API

### Incorrect Status Display
- Check database status values
- Verify validation results stored
- Review API response structure

## Support

For issues or questions:
1. Check test page: `test-feedback-module.html`
2. Review API response in browser console
3. Check database records
4. Verify Flask API is running

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-17  
**Status**: Production Ready for Academic Demonstration
