# AI Detection System - Quick Start Guide

## 5-Minute Setup

### Step 1: Install Python Dependencies (1 minute)
```bash
pip install opencv-python Pillow numpy
```

### Step 2: Run Database Migration (1 minute)
```bash
php backend/migrations/add_ai_detection_columns.php
```

Expected output:
```
✓ Added column 'ai_risk_score'
✓ Added column 'ai_risk_level'
✓ Added column 'ai_detection_decision'
...
✅ Migration completed successfully!
```

### Step 3: Restart Flask API (1 minute)
```bash
cd python_ml_service
python craft_flask_api.py
```

Look for:
```
✓ Craft classifier initialized successfully
✓ AI image detector initialized successfully
```

### Step 4: Verify Installation (2 minutes)
```bash
php test-ai-detection-system.php
```

Expected output:
```
✓ All required packages installed
✓ AI Detector module working
✓ Flask API service running
✓ All AI detection columns present
✅ System ready!
```

## Quick Test

### Test AI Detection on an Image

**Python (standalone)**:
```bash
python python_ml_service/ai_image_detector.py path/to/image.jpg
```

**API (integrated)**:
```bash
curl -X POST http://localhost:5001/detect-ai-image \
  -H "Content-Type: application/json" \
  -d '{"image_path": "/absolute/path/to/image.jpg"}'
```

### Expected Response
```json
{
  "success": true,
  "ai_risk_score": 45,
  "risk_level": "medium",
  "decision": "flag",
  "explanation": "Medium AI risk score (45/100) - requires admin review",
  "detection_evidence": {
    "metadata_analysis": {
      "has_ai_signature": false,
      "risk_contribution": 0
    },
    "exif_analysis": {
      "has_camera_metadata": false,
      "risk_contribution": 20
    },
    "texture_analysis": {
      "laplacian_variance": 85.5,
      "is_overly_smooth": true,
      "risk_contribution": 25
    },
    "watermark_analysis": {
      "watermark_detected": false,
      "risk_contribution": 0
    }
  }
}
```

## Understanding Results

### Risk Score Interpretation

| Score | Level | Meaning | Action |
|-------|-------|---------|--------|
| 0-39 | Low | Likely authentic | ✅ Pass |
| 40-69 | Medium | Uncertain | ⚠️ Review |
| 70-100 | High | Likely AI-generated | ❌ Reject |

### Detection Layers

1. **Metadata** (50 pts): AI generator keywords found?
2. **EXIF** (20 pts): Camera metadata missing?
3. **Texture** (25 pts): Unnaturally smooth?
4. **Watermark** (15 pts): AI platform watermark?

## Common Scenarios

### Scenario 1: Real Photo from Camera
```
✓ Metadata: No AI keywords (0 pts)
✓ EXIF: Camera data present (0 pts)
✓ Texture: Normal variance (0 pts)
✓ Watermark: None detected (0 pts)
→ Score: 0/100 (Low) → PASS
```

### Scenario 2: AI-Generated with Metadata
```
❌ Metadata: "Stable Diffusion" found (50 pts)
⚠️ EXIF: No camera data (20 pts)
⚠️ Texture: Overly smooth (25 pts)
✓ Watermark: None (0 pts)
→ Score: 95/100 (High) → REJECT
```

### Scenario 3: Edited Photo (EXIF Stripped)
```
✓ Metadata: No AI keywords (0 pts)
⚠️ EXIF: No camera data (20 pts)
✓ Texture: Normal variance (0 pts)
✓ Watermark: None (0 pts)
→ Score: 20/100 (Low) → PASS
```

### Scenario 4: AI Image, No Metadata
```
✓ Metadata: No keywords (0 pts)
⚠️ EXIF: No camera data (20 pts)
⚠️ Texture: Overly smooth (25 pts)
⚠️ Watermark: Detected (15 pts)
→ Score: 60/100 (Medium) → FLAG
```

## Integration with Craft Validation

### Validation Flow
```
Upload → AI Detection → Craft Classification → Final Decision
```

### Combined Decision Matrix

| AI Detection | Craft Validation | Final Result |
|--------------|------------------|--------------|
| Reject (High) | Any | ❌ Reject (AI) |
| Flag (Medium) | Any | ⚠️ Review (AI) |
| Pass (Low) | Auto-Approve | ✅ Approve |
| Pass (Low) | Auto-Reject | ❌ Reject (Craft) |
| Pass (Low) | Flag | ⚠️ Review (Craft) |

## Admin Dashboard Usage

### Viewing AI Detection Evidence

1. Navigate to flagged submissions
2. Look for "AI Risk Score" badge
3. Click "View AI Evidence" for details
4. Review each detection layer
5. Make informed decision

### API Endpoint for Evidence
```bash
curl "http://localhost/backend/api/admin/ai-detection-evidence.php?image_id=IMAGE_ID"
```

## Troubleshooting

### Problem: "AI detector not available"
**Solution**: Install dependencies
```bash
pip install opencv-python Pillow numpy
```

### Problem: "Module not found: cv2"
**Solution**: Install OpenCV
```bash
pip install opencv-python
```

### Problem: "Table columns missing"
**Solution**: Run migration
```bash
php backend/migrations/add_ai_detection_columns.php
```

### Problem: "Flask API not responding"
**Solution**: Start the service
```bash
cd python_ml_service
python craft_flask_api.py
```

### Problem: "All images flagged as AI"
**Solution**: Adjust thresholds in `ai_image_detector.py`
```python
THRESHOLD_HIGH_RISK = 70  # Increase to 80
THRESHOLD_MEDIUM_RISK = 40  # Increase to 50
```

## Adjusting Sensitivity

### More Strict (Catch More AI Images)
Edit `python_ml_service/ai_image_detector.py`:
```python
# Lower thresholds
THRESHOLD_HIGH_RISK = 60  # Was 70
THRESHOLD_MEDIUM_RISK = 30  # Was 40

# Increase weights
WEIGHT_METADATA_AI_KEYWORD = 60  # Was 50
WEIGHT_NO_EXIF_CAMERA = 25  # Was 20
```

### More Permissive (Reduce False Positives)
```python
# Raise thresholds
THRESHOLD_HIGH_RISK = 80  # Was 70
THRESHOLD_MEDIUM_RISK = 50  # Was 40

# Decrease weights
WEIGHT_NO_EXIF_CAMERA = 10  # Was 20
WEIGHT_SMOOTH_TEXTURE = 15  # Was 25
```

## Testing with Sample Images

### Get Test Images

**AI-Generated** (should score high):
- Download from Midjourney, Stable Diffusion
- Look for images with metadata intact

**Real Photos** (should score low):
- Use phone camera photos
- Ensure EXIF data is present

**Edge Cases** (should score medium):
- Screenshots (no EXIF)
- Heavily edited photos
- Scanned images

### Run Tests
```bash
# Test AI image
python python_ml_service/ai_image_detector.py ai_generated.jpg

# Test real photo
python python_ml_service/ai_image_detector.py real_photo.jpg

# Test screenshot
python python_ml_service/ai_image_detector.py screenshot.png
```

## Next Steps

1. ✅ System installed and working
2. 📊 Test with your image dataset
3. 🎯 Adjust thresholds based on results
4. 👥 Train admins on reviewing flagged cases
5. 📈 Monitor detection accuracy over time

## Key Files Reference

| File | Purpose |
|------|---------|
| `python_ml_service/ai_image_detector.py` | Core detection module |
| `python_ml_service/craft_flask_api.py` | API integration |
| `backend/services/CraftImageValidationServiceV2.php` | PHP service |
| `backend/migrations/add_ai_detection_columns.php` | Database setup |
| `backend/api/admin/ai-detection-evidence.php` | Admin API |
| `test-ai-detection-system.php` | Testing script |

## Support

**Check system status**:
```bash
curl http://localhost:5001/health
```

**View logs**:
```bash
tail -f python_ml_service/craft_validation.log
```

**Database check**:
```sql
SELECT ai_risk_score, ai_risk_level, COUNT(*) 
FROM craft_image_validation_v2 
GROUP BY ai_risk_level;
```

---

**Ready to use!** The AI detection system is now integrated and operational.
