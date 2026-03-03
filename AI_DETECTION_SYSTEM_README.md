# AI-Generated Image Detection System

## Overview

Multi-layer AI-generated image detection module integrated into the CraftImageValidationService. This system uses a weighted risk scoring mechanism to detect AI-generated images through multiple detection layers, providing explainable and probabilistic risk assessment suitable for academic research demonstration.

## Key Features

- **Multi-Layer Detection**: Combines 4 independent detection layers
- **Weighted Risk Scoring**: Cumulative score (0-100) determines final decision
- **Explainable Results**: Full evidence logging for transparency
- **Non-Binary Detection**: Risk-based approach (high/medium/low) instead of binary yes/no
- **Academic Research Ready**: Deterministic, explainable, and well-documented

## Detection Layers

### Layer 1: Metadata Analysis (Weight: 50 points)
**Purpose**: Inspect image metadata for AI generator keywords

**Detection Method**:
- Scans PIL info dictionary and EXIF data
- Searches for known AI generator keywords:
  - Stable Diffusion, DALL-E, Midjourney, Firefly
  - Leonardo.ai, Craiyon, NightCafe, etc.
- Records which fields contain keywords

**Risk Contribution**:
- **50 points** if AI keywords found (strong signal)
- **0 points** if no keywords found

**Evidence Logged**:
- List of keywords found
- Metadata fields containing keywords
- Value snippets

### Layer 2: EXIF Camera Metadata Analysis (Weight: 20 points)
**Purpose**: Check for presence of camera-related EXIF data

**Detection Method**:
- Checks for camera metadata fields:
  - Make, Model (camera manufacturer/model)
  - ISO, ExposureTime, FNumber (camera settings)
  - LensModel, FocalLength (lens information)
  - Flash, WhiteBalance
- Real photos typically have this metadata
- AI-generated images typically lack it

**Risk Contribution**:
- **20 points** if no camera metadata found (moderate signal)
- **0 points** if camera metadata present

**Evidence Logged**:
- Camera fields found/missing
- Specific metadata values

### Layer 3: Texture Smoothness Analysis (Weight: 25 points)
**Purpose**: Detect unnaturally smooth, synthetic-looking textures

**Detection Method**:
- Uses Laplacian variance to measure edge sharpness
- Converts image to grayscale
- Applies Laplacian operator
- Calculates variance of result
- Low variance = smooth/synthetic appearance

**Threshold**: Laplacian variance < 100.0

**Risk Contribution**:
- **25 points** if variance below threshold (moderate signal)
- **0 points** if normal texture variance

**Evidence Logged**:
- Laplacian variance value
- Smoothness assessment

### Layer 4: Watermark Detection (Weight: 15 points)
**Purpose**: Detect known AI platform watermarks (optional, weak signal)

**Detection Method**:
- Examines bottom-right corner (last 15% of width/height)
- Checks for high-contrast patterns typical of watermarks
- Calculates standard deviation and mean intensity
- High std dev + bright region = possible watermark

**Risk Contribution**:
- **15 points** if watermark pattern detected (weak signal)
- **0 points** if no watermark detected

**Evidence Logged**:
- Watermark location
- Detection confidence
- Pattern characteristics

## Risk Scoring System

### Cumulative Score Calculation
```
Total Risk Score = Metadata (0-50) + EXIF (0-20) + Texture (0-25) + Watermark (0-15)
Maximum Possible Score: 100
```

### Risk Levels and Decisions

| Risk Score | Risk Level | Decision | Action |
|------------|-----------|----------|--------|
| 70-100 | High | Reject | Auto-reject as AI-generated |
| 40-69 | Medium | Flag | Send to admin review (AI_Flagged) |
| 0-39 | Low | Pass | Allow through AI detection |

### Decision Logic

1. **High Risk (≥70)**: Strong evidence of AI generation
   - Multiple detection layers triggered
   - Automatic rejection
   - Clear explanation provided

2. **Medium Risk (40-69)**: Moderate evidence
   - Some detection layers triggered
   - Requires manual admin review
   - Flagged as "AI_Flagged" status

3. **Low Risk (<40)**: Minimal evidence
   - Few or no detection layers triggered
   - Passes AI detection
   - Continues to craft category validation

## Integration with Craft Validation

### Validation Flow

```
1. Image Upload
   ↓
2. AI Detection Layer (if enabled)
   ↓
   ├─ High Risk → Auto-Reject (AI-generated)
   ├─ Medium Risk → Flag for Review (AI_Flagged)
   └─ Low Risk → Continue to Craft Validation
      ↓
3. Craft Category Classification
   ↓
4. Category Matching Validation
   ↓
5. Final Decision (auto-approve/auto-reject/flag)
```

### AI Detection Does NOT Override Craft Validation

- AI detection operates as an **additional validation layer**
- It does not replace craft category validation
- Both validations must pass for auto-approval
- Either validation can trigger rejection or flagging

## Database Schema

### New Columns in `craft_image_validation_v2`

```sql
ai_risk_score INT(11) DEFAULT 0
  -- Cumulative risk score (0-100)

ai_risk_level ENUM('low', 'medium', 'high', 'unknown') DEFAULT 'unknown'
  -- Risk level classification

ai_detection_decision ENUM('pass', 'flag', 'reject') DEFAULT 'pass'
  -- AI detection decision

ai_detection_evidence JSON DEFAULT NULL
  -- Full detection evidence from all layers

metadata_ai_keywords JSON DEFAULT NULL
  -- AI generator keywords found in metadata

exif_camera_present TINYINT(1) DEFAULT NULL
  -- Whether camera EXIF metadata is present

texture_laplacian_variance DECIMAL(10,2) DEFAULT NULL
  -- Laplacian variance for texture smoothness

watermark_detected TINYINT(1) DEFAULT 0
  -- Whether AI platform watermark detected
```

## API Endpoints

### 1. Standalone AI Detection
```
POST /detect-ai-image
```

**Request**:
```json
{
  "image_path": "/path/to/image.jpg"
}
```

**Response**:
```json
{
  "success": true,
  "ai_risk_score": 65,
  "risk_level": "medium",
  "is_likely_ai_generated": false,
  "decision": "flag",
  "explanation": "Medium AI risk score (65/100) - requires admin review",
  "detection_evidence": {
    "metadata_analysis": { ... },
    "exif_analysis": { ... },
    "texture_analysis": { ... },
    "watermark_analysis": { ... }
  }
}
```

### 2. Integrated Validation with AI Detection
```
POST /validate-practice
```

**Request**:
```json
{
  "image_path": "/path/to/image.jpg",
  "selected_category": "hand_embroidery",
  "tutorial_id": 123,
  "enable_ai_detection": true
}
```

**Response**:
```json
{
  "success": true,
  "classification": { ... },
  "validation": {
    "decision_type": "flag-for-review",
    "ai_detection_applied": true,
    "reasons": ["Possible AI-generated image (risk score: 65/100)"]
  },
  "ai_detection": {
    "ai_risk_score": 65,
    "risk_level": "medium",
    "decision": "flag",
    "detection_evidence": { ... }
  }
}
```

### 3. Admin Evidence Retrieval
```
GET /backend/api/admin/ai-detection-evidence.php?image_id=IMAGE_ID
```

**Response**:
```json
{
  "success": true,
  "validation_record": { ... },
  "summary": {
    "risk_assessment": {
      "score": 65,
      "level": "medium",
      "interpretation": "Moderate probability of AI generation"
    },
    "detection_layers": [
      {
        "layer": "Metadata Analysis",
        "status": "clean",
        "details": "No AI generator keywords found",
        "risk_contribution": "None"
      },
      {
        "layer": "EXIF Camera Metadata",
        "status": "missing",
        "details": "No camera metadata - typical of AI-generated images",
        "risk_contribution": "Moderate (20 points)"
      },
      ...
    ],
    "recommendation": {
      "action": "manual_review",
      "reason": "Moderate evidence of AI generation",
      "confidence": "medium"
    }
  }
}
```

## Installation & Setup

### 1. Install Python Dependencies
```bash
pip install opencv-python Pillow numpy
```

### 2. Run Database Migration
```bash
php backend/migrations/add_ai_detection_columns.php
```

### 3. Restart Flask API
```bash
cd python_ml_service
python craft_flask_api.py
```

### 4. Verify Installation
```bash
php test-ai-detection-system.php
```

## Admin Dashboard Integration

### Displaying AI Detection Evidence

The admin dashboard should display:

1. **Risk Score Badge**: Visual indicator (red/yellow/green)
2. **Risk Level**: High/Medium/Low
3. **Detection Layers Summary**: Which layers triggered
4. **Detailed Evidence**: Expandable section with full details
5. **Recommendation**: Suggested action based on evidence

### Example UI Elements

```html
<!-- Risk Score Badge -->
<div class="ai-risk-badge risk-medium">
  AI Risk: 65/100 (Medium)
</div>

<!-- Detection Layers -->
<div class="detection-layers">
  <div class="layer">
    <span class="layer-name">Metadata Analysis</span>
    <span class="layer-status clean">✓ Clean</span>
  </div>
  <div class="layer">
    <span class="layer-name">EXIF Camera Data</span>
    <span class="layer-status suspicious">⚠ Missing</span>
    <span class="risk-points">+20 points</span>
  </div>
  <div class="layer">
    <span class="layer-name">Texture Smoothness</span>
    <span class="layer-status suspicious">⚠ Overly Smooth</span>
    <span class="risk-points">+25 points</span>
  </div>
  <div class="layer">
    <span class="layer-name">Watermark Detection</span>
    <span class="layer-status suspicious">⚠ Detected</span>
    <span class="risk-points">+15 points</span>
  </div>
</div>

<!-- Recommendation -->
<div class="recommendation">
  <strong>Recommendation:</strong> Manual review required
  <p>Moderate evidence of AI generation detected</p>
</div>
```

## Testing

### Test with Known AI-Generated Images

1. Download sample AI-generated images from:
   - Stable Diffusion outputs
   - Midjourney exports
   - DALL-E generations

2. Test detection:
```bash
python python_ml_service/ai_image_detector.py path/to/ai_image.jpg
```

3. Verify high risk scores for AI images

### Test with Real Photos

1. Use photos from real cameras with EXIF data
2. Verify low risk scores
3. Check that camera metadata is detected

### Test Edge Cases

1. Screenshots (no EXIF, but not AI-generated)
2. Edited photos (EXIF may be stripped)
3. Scanned images (no camera metadata)
4. Phone camera photos (should have EXIF)

## Important Notes

### Probabilistic Detection

- AI detection is **probabilistic**, not absolute
- No detection system is 100% accurate
- Risk scores indicate probability, not certainty
- Manual review is recommended for medium-risk cases

### False Positives

Legitimate images may trigger detection if:
- EXIF data was stripped during editing
- Image was screenshot or screen-captured
- Image was heavily processed/filtered
- Image is a scan or digital artwork

### False Negatives

AI-generated images may pass detection if:
- AI generator doesn't add metadata
- Image was post-processed to add fake EXIF
- Texture is intentionally made rough
- Watermark was removed

### Ethical Considerations

- System is designed for educational validation, not surveillance
- Results should be used to guide review, not make final decisions
- Users should be informed about AI detection policies
- Appeals process should be available for disputed cases

## Academic Research Documentation

### Methodology

This system implements a multi-layer detection approach based on:
1. Metadata forensics
2. EXIF analysis
3. Texture analysis (Laplacian variance)
4. Pattern recognition (watermark detection)

### Explainability

All detection decisions include:
- Detailed evidence from each layer
- Risk score breakdown
- Reasoning for final decision
- Confidence levels

### Reproducibility

- Deterministic algorithms (no randomness)
- Documented thresholds
- Open-source implementation
- Comprehensive logging

### Limitations

- Detection accuracy depends on image quality
- Some AI generators may evade detection
- Post-processing can affect results
- Cultural and artistic styles may trigger false positives

## Troubleshooting

### AI Detector Not Available

**Symptom**: `ai_detector_available: false` in health check

**Solutions**:
1. Check Python dependencies: `pip list`
2. Install missing packages: `pip install opencv-python Pillow numpy`
3. Restart Flask API

### Low Detection Rates

**Symptom**: AI images passing with low scores

**Solutions**:
1. Lower thresholds in `ai_image_detector.py`
2. Add more AI generator keywords
3. Adjust Laplacian variance threshold
4. Enable additional detection layers

### High False Positive Rate

**Symptom**: Real photos flagged as AI

**Solutions**:
1. Raise thresholds (especially THRESHOLD_MEDIUM_RISK)
2. Reduce weight of EXIF layer (many edited photos lack EXIF)
3. Adjust texture smoothness threshold
4. Review watermark detection sensitivity

## Future Enhancements

### Potential Improvements

1. **Deep Learning Detection**: Train CNN to detect AI artifacts
2. **Frequency Analysis**: Analyze frequency domain patterns
3. **GAN Fingerprinting**: Detect specific GAN architectures
4. **Blockchain Verification**: Verify image provenance
5. **Reverse Image Search**: Check for AI image databases

### Research Opportunities

1. Comparative study of detection methods
2. Accuracy benchmarking across AI generators
3. Cultural bias analysis in detection
4. User perception of AI detection systems

## Support & Contact

For issues, questions, or contributions:
- Review test results: `php test-ai-detection-system.php`
- Check logs: `python_ml_service/craft_validation.log`
- Verify database: Check `craft_image_validation_v2` table
- API health: `curl http://localhost:5001/health`

## License & Attribution

This AI detection system is designed for educational and research purposes. When using or citing this system:

1. Acknowledge the multi-layer detection approach
2. Document any modifications to thresholds or weights
3. Report accuracy metrics in your context
4. Share findings to improve detection methods

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-17  
**Status**: Production Ready for Academic Demonstration
