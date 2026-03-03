# AI Detection System Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     Image Upload Request                         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              CraftImageValidationServiceV2 (PHP)                 │
│                                                                   │
│  1. Receives image upload                                        │
│  2. Calls Flask API for validation                               │
│  3. Stores results in database                                   │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Flask API (Python)                              │
│                  craft_flask_api.py                              │
│                                                                   │
│  Endpoint: POST /validate-practice                               │
│  - enable_ai_detection: true                                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                ┌────────────┴────────────┐
                │                         │
                ▼                         ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│   AI Image Detector      │  │  Craft Classifier        │
│   ai_image_detector.py   │  │  craft_classifier.py     │
│                          │  │                          │
│  Multi-Layer Detection:  │  │  Keras Model:            │
│  1. Metadata Analysis    │  │  - Category prediction   │
│  2. EXIF Analysis        │  │  - Confidence scores     │
│  3. Texture Analysis     │  │  - Craft validation      │
│  4. Watermark Detection  │  │                          │
└────────────┬─────────────┘  └────────────┬─────────────┘
             │                             │
             └──────────────┬──────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Combined Validation Result                    │
│                                                                   │
│  {                                                                │
│    classification: { predicted_category, confidence },           │
│    ai_detection: { risk_score, risk_level, evidence },          │
│    validation: { decision_type, reasons }                        │
│  }                                                                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Decision Logic (Python)                         │
│                                                                   │
│  IF ai_detection.decision == 'reject':                           │
│    → Auto-Reject (AI-generated)                                  │
│  ELIF ai_detection.decision == 'flag':                           │
│    → Flag for Review (AI_Flagged)                                │
│  ELSE:                                                            │
│    → Continue to Craft Validation                                │
│      IF craft_match && confidence >= threshold:                  │
│        → Auto-Approve                                             │
│      ELIF craft_mismatch && confidence >= threshold:             │
│        → Auto-Reject (Category mismatch)                         │
│      ELSE:                                                        │
│        → Flag for Review                                          │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              Database Storage (MySQL)                            │
│              craft_image_validation_v2                           │
│                                                                   │
│  Stores:                                                          │
│  - Craft classification results                                  │
│  - AI detection evidence                                         │
│  - Risk scores and levels                                        │
│  - Detection layer details                                       │
│  - Final decision and reasons                                    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Admin Dashboard                               │
│                                                                   │
│  Displays:                                                        │
│  - AI risk score badge                                           │
│  - Detection layer summary                                       │
│  - Detailed evidence                                             │
│  - Recommendation                                                │
│                                                                   │
│  API: GET /backend/api/admin/ai-detection-evidence.php          │
└─────────────────────────────────────────────────────────────────┘
```

## AI Detection Layer Details

```
┌─────────────────────────────────────────────────────────────────┐
│                    AI Image Detector                             │
│                    ai_image_detector.py                          │
└─────────────────────────────────────────────────────────────────┘
                             │
                ┌────────────┼────────────┐
                │            │            │
                ▼            ▼            ▼
┌──────────────────┐ ┌──────────────┐ ┌──────────────┐
│  Layer 1:        │ │  Layer 2:    │ │  Layer 3:    │
│  Metadata        │ │  EXIF        │ │  Texture     │
│  Analysis        │ │  Analysis    │ │  Analysis    │
│                  │ │              │ │              │
│  Weight: 50 pts  │ │  Weight: 20  │ │  Weight: 25  │
│                  │ │              │ │              │
│  Checks:         │ │  Checks:     │ │  Checks:     │
│  - PIL info      │ │  - Make      │ │  - Laplacian │
│  - EXIF tags     │ │  - Model     │ │    variance  │
│  - AI keywords   │ │  - ISO       │ │  - Edge      │
│    * Stable      │ │  - Exposure  │ │    sharpness │
│      Diffusion   │ │  - Lens      │ │  - Texture   │
│    * DALL-E      │ │  - Flash     │ │    smoothness│
│    * Midjourney  │ │              │ │              │
│    * Firefly     │ │  Missing →   │ │  Low var →   │
│                  │ │  +20 points  │ │  +25 points  │
│  Found →         │ │              │ │              │
│  +50 points      │ │              │ │              │
└──────────────────┘ └──────────────┘ └──────────────┘
                             │
                             ▼
                    ┌──────────────┐
                    │  Layer 4:    │
                    │  Watermark   │
                    │  Detection   │
                    │              │
                    │  Weight: 15  │
                    │              │
                    │  Checks:     │
                    │  - Bottom-   │
                    │    right     │
                    │    corner    │
                    │  - High      │
                    │    contrast  │
                    │  - Bright    │
                    │    region    │
                    │              │
                    │  Found →     │
                    │  +15 points  │
                    └──────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  Risk Score Calculation                          │
│                                                                   │
│  Total = Metadata + EXIF + Texture + Watermark                   │
│  Range: 0-100 points                                             │
│                                                                   │
│  Risk Level:                                                      │
│  - High (70-100): Auto-reject                                    │
│  - Medium (40-69): Flag for review                               │
│  - Low (0-39): Pass                                              │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌─────────┐
│  Image  │
│  Upload │
└────┬────┘
     │
     ▼
┌─────────────────────────────────────────────────────────────────┐
│                        PHP Backend                               │
│  backend/services/CraftImageValidationServiceV2.php             │
│                                                                   │
│  validatePracticeImageSync($filePath, $userId, $tutorialId,     │
│                            $selectedCategory)                    │
└────┬────────────────────────────────────────────────────────────┘
     │
     │ HTTP POST
     ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Python Flask API                            │
│  python_ml_service/craft_flask_api.py                           │
│                                                                   │
│  POST /validate-practice                                         │
│  {                                                                │
│    "image_path": "/path/to/image.jpg",                          │
│    "selected_category": "hand_embroidery",                       │
│    "enable_ai_detection": true                                   │
│  }                                                                │
└────┬────────────────────────────────────────────────────────────┘
     │
     ├─────────────────────┬─────────────────────┐
     │                     │                     │
     ▼                     ▼                     ▼
┌──────────┐      ┌──────────────┐      ┌──────────────┐
│   PIL    │      │   OpenCV     │      │    Keras     │
│ (Pillow) │      │   (cv2)      │      │   Model      │
│          │      │              │      │              │
│ Metadata │      │   Texture    │      │   Craft      │
│ & EXIF   │      │   Analysis   │      │   Category   │
└────┬─────┘      └──────┬───────┘      └──────┬───────┘
     │                   │                     │
     └───────────────────┴─────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Combined Results                              │
│                                                                   │
│  {                                                                │
│    "classification": {                                            │
│      "predicted_category": "hand_embroidery",                    │
│      "confidence": 0.85                                           │
│    },                                                             │
│    "ai_detection": {                                              │
│      "ai_risk_score": 25,                                        │
│      "risk_level": "low",                                         │
│      "decision": "pass",                                          │
│      "detection_evidence": { ... }                               │
│    },                                                             │
│    "validation": {                                                │
│      "decision_type": "auto-approve",                            │
│      "ai_detection_applied": true                                │
│    }                                                              │
│  }                                                                │
└────┬────────────────────────────────────────────────────────────┘
     │
     │ HTTP Response
     ▼
┌─────────────────────────────────────────────────────────────────┐
│                        PHP Backend                               │
│  Receives validation result                                      │
│  Calls storeValidationResult()                                   │
└────┬────────────────────────────────────────────────────────────┘
     │
     │ SQL INSERT
     ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL Database                                │
│  craft_image_validation_v2                                       │
│                                                                   │
│  INSERT INTO craft_image_validation_v2 (                         │
│    image_id, predicted_category, prediction_confidence,          │
│    ai_risk_score, ai_risk_level, ai_detection_decision,         │
│    ai_detection_evidence, metadata_ai_keywords,                  │
│    exif_camera_present, texture_laplacian_variance,             │
│    watermark_detected, ...                                       │
│  )                                                                │
└────┬────────────────────────────────────────────────────────────┘
     │
     │ Query
     ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Admin Dashboard                               │
│  backend/api/admin/ai-detection-evidence.php                    │
│                                                                   │
│  GET /ai-detection-evidence.php?image_id=IMAGE_ID               │
│                                                                   │
│  Returns:                                                         │
│  - Risk assessment summary                                       │
│  - Detection layer details                                       │
│  - Recommendation                                                │
└─────────────────────────────────────────────────────────────────┘
```

## Decision Tree

```
                        Image Upload
                             │
                             ▼
                    ┌────────────────┐
                    │ AI Detection   │
                    │ Enabled?       │
                    └───┬────────┬───┘
                        │        │
                   Yes  │        │ No
                        │        │
                        ▼        └──────────────┐
                ┌───────────────┐               │
                │ Run AI        │               │
                │ Detection     │               │
                └───┬───────────┘               │
                    │                           │
        ┌───────────┼───────────┐               │
        │           │           │               │
        ▼           ▼           ▼               │
    ┌──────┐   ┌────────┐  ┌──────┐            │
    │ High │   │ Medium │  │ Low  │            │
    │ Risk │   │ Risk   │  │ Risk │            │
    │≥70   │   │40-69   │  │<40   │            │
    └──┬───┘   └───┬────┘  └───┬──┘            │
       │           │           │               │
       ▼           ▼           └───────────────┤
   ┌────────┐  ┌────────┐                      │
   │ REJECT │  │  FLAG  │                      │
   │  (AI)  │  │  (AI)  │                      │
   └────────┘  └────────┘                      │
                                               │
                                               ▼
                                    ┌──────────────────┐
                                    │ Craft            │
                                    │ Classification   │
                                    └────────┬─────────┘
                                             │
                        ┌────────────────────┼────────────────────┐
                        │                    │                    │
                        ▼                    ▼                    ▼
                ┌───────────────┐    ┌──────────────┐    ┌──────────────┐
                │ High Conf     │    │ Medium Conf  │    │ Low Conf     │
                │ Category      │    │ Category     │    │ Category     │
                │ Match         │    │ Match        │    │ Mismatch     │
                └───┬───────────┘    └──────┬───────┘    └──────┬───────┘
                    │                       │                    │
                    ▼                       ▼                    ▼
                ┌────────┐            ┌────────┐            ┌────────┐
                │APPROVE │            │  FLAG  │            │ REJECT │
                │        │            │(Craft) │            │(Craft) │
                └────────┘            └────────┘            └────────┘
```

## Database Schema Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│              craft_image_validation_v2                           │
├─────────────────────────────────────────────────────────────────┤
│ id (PK)                          INT AUTO_INCREMENT              │
│ image_id                         VARCHAR(255) UNIQUE             │
│ image_type                       ENUM('practice_upload', ...)   │
│ user_id                          INT                             │
│ tutorial_id                      INT                             │
├─────────────────────────────────────────────────────────────────┤
│ CRAFT CLASSIFICATION                                             │
├─────────────────────────────────────────────────────────────────┤
│ predicted_category               VARCHAR(50)                     │
│ prediction_confidence            DECIMAL(5,4)                    │
│ category_matches                 TINYINT(1)                      │
│ all_predictions                  JSON                            │
│ classification_data              JSON                            │
├─────────────────────────────────────────────────────────────────┤
│ AI DETECTION - RISK ASSESSMENT                                   │
├─────────────────────────────────────────────────────────────────┤
│ ai_risk_score                    INT (0-100)                     │
│ ai_risk_level                    ENUM('low','medium','high')    │
│ ai_detection_decision            ENUM('pass','flag','reject')   │
│ ai_detection_evidence            JSON (full evidence)            │
├─────────────────────────────────────────────────────────────────┤
│ AI DETECTION - LAYER 1: METADATA                                 │
├─────────────────────────────────────────────────────────────────┤
│ metadata_ai_keywords             JSON (keywords found)           │
├─────────────────────────────────────────────────────────────────┤
│ AI DETECTION - LAYER 2: EXIF                                     │
├─────────────────────────────────────────────────────────────────┤
│ exif_camera_present              TINYINT(1)                      │
├─────────────────────────────────────────────────────────────────┤
│ AI DETECTION - LAYER 3: TEXTURE                                  │
├─────────────────────────────────────────────────────────────────┤
│ texture_laplacian_variance       DECIMAL(10,2)                   │
├─────────────────────────────────────────────────────────────────┤
│ AI DETECTION - LAYER 4: WATERMARK                                │
├─────────────────────────────────────────────────────────────────┤
│ watermark_detected               TINYINT(1)                      │
├─────────────────────────────────────────────────────────────────┤
│ VALIDATION DECISION                                              │
├─────────────────────────────────────────────────────────────────┤
│ ai_decision                      ENUM('auto-approve', ...)      │
│ requires_review                  TINYINT(1)                      │
│ decision_reasons                 JSON                            │
├─────────────────────────────────────────────────────────────────┤
│ ADMIN REVIEW                                                     │
├─────────────────────────────────────────────────────────────────┤
│ admin_decision                   ENUM('approved','rejected')    │
│ admin_notes                      TEXT                            │
│ reviewed_by                      INT                             │
│ reviewed_at                      TIMESTAMP                       │
├─────────────────────────────────────────────────────────────────┤
│ TIMESTAMPS                                                       │
├─────────────────────────────────────────────────────────────────┤
│ created_at                       TIMESTAMP                       │
│ updated_at                       TIMESTAMP                       │
└─────────────────────────────────────────────────────────────────┘

Indexes:
- idx_ai_decision (ai_decision)
- idx_requires_review (requires_review)
- idx_predicted_category (predicted_category)
- idx_user_tutorial (user_id, tutorial_id)
- idx_ai_risk_level (ai_risk_level)
- idx_ai_detection_decision (ai_detection_decision)
```

## Component Interaction Sequence

```
User → PHP → Flask API → AI Detector → Database → Admin
 │      │        │            │           │         │
 │      │        │            │           │         │
 1      2        3            4           5         6

1. User uploads image
2. PHP validates and calls Flask API
3. Flask API orchestrates detection
4. AI Detector analyzes image (4 layers)
5. Results stored in database
6. Admin reviews flagged cases
```

## File Structure

```
project/
├── python_ml_service/
│   ├── ai_image_detector.py          ← Core AI detection module
│   ├── craft_flask_api.py            ← Flask API with AI integration
│   └── craft_classifier.py           ← Craft category classifier
│
├── backend/
│   ├── services/
│   │   └── CraftImageValidationServiceV2.php  ← PHP validation service
│   ├── api/
│   │   └── admin/
│   │       └── ai-detection-evidence.php      ← Admin evidence API
│   └── migrations/
│       └── add_ai_detection_columns.php       ← Database migration
│
├── test-ai-detection-system.php      ← Testing script
├── AI_DETECTION_SYSTEM_README.md     ← Full documentation
├── AI_DETECTION_QUICK_START.md       ← Quick start guide
└── AI_DETECTION_ARCHITECTURE.md      ← This file
```

---

**Architecture Version**: 1.0.0  
**Last Updated**: 2026-02-17
