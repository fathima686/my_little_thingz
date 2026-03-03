# 🎯 Decision Logic Reference Card

## Quick Reference for Image Authenticity System V2

### 📊 Evaluation Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Student Uploads Image                     │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              Get Tutorial Category (Ground Truth)            │
│              ✓ Use selected tutorial category                │
│              ✗ Never auto-detect from image                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                  Generate pHash (32x32 DCT)                  │
│                  ✓ Perceptual hash only                      │
│                  ✗ No aHash, dHash, wavelet                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              Extract EXIF Metadata (Optional)                │
│              • Camera make/model                             │
│              • Software used                                 │
│              • Date taken                                    │
│              • Dimensions                                    │
│              ⚠️ Used for admin reference only                │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│         Google Vision API - Label Detection (Optional)       │
│         • Detect: person, landscape, animal, object          │
│         • Confidence threshold: ≥ 0.80                       │
│         • Result: possibly_unrelated flag                    │
│         ⚠️ Warning only, not auto-rejection                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│         Compare pHash with Same Category Images Only         │
│         • Get approved images in same category               │
│         • Calculate Hamming distance                         │
│         • Threshold: distance ≤ 5 → similar                  │
│         ✓ Category-specific comparison                       │
│         ✗ No cross-category comparison                       │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    DECISION LOGIC                            │
│                                                              │
│  IF (possibly_unrelated == true OR phash_distance ≤ 5)      │
│      → evaluation_status = 'needs_admin_review'              │
│      → requires_review = true                                │
│      → Add to admin review queue                             │
│  ELSE                                                        │
│      → evaluation_status = 'unique'                          │
│      → requires_review = false                               │
│      → Auto-approve and update progress                      │
│                                                              │
│  ✗ NEVER auto-reject                                         │
│  ✓ Admin is final authority                                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                    Store Results                             │
│                    • image_authenticity_v2                   │
│                    • admin_review_v2 (if flagged)            │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                  Update Learning Progress                    │
│                                                              │
│  IF (requires_review == false)                               │
│      → practice_completed = 1                                │
│      → practice_admin_approved = 1                           │
│  ELSE                                                        │
│      → practice_uploaded = 1                                 │
│      → practice_completed = 0                                │
│      → practice_admin_approved = 0                           │
│      → Wait for admin decision                               │
└─────────────────────────────────────────────────────────────┘
```

## 🔍 Evaluation Status Values

| Status | Meaning | Action | Progress Update |
|--------|---------|--------|-----------------|
| `unique` | No similar images found, no AI warnings | ✅ Auto-approve | Immediate |
| `possible_reuse` | pHash distance ≤ 5 in same category | ⏸️ Admin review | After approval |
| `possibly_unrelated` | AI detected unrelated content (≥80% confidence) | ⏸️ Admin review | After approval |
| `needs_admin_review` | Flagged for any reason | ⏸️ Admin review | After approval |

## 🤖 AI Content Detection

### Unrelated Content Labels (Confidence ≥ 0.80):

| Category | Labels Detected |
|----------|----------------|
| **People** | person, people, human, face, portrait |
| **Scenery** | landscape, scenery, nature, outdoor |
| **Animals** | animal, pet, dog, cat, bird |
| **Food** | food, meal, dish, restaurant |
| **Vehicles** | vehicle, car, automobile, transportation |
| **Buildings** | building, architecture, city, urban |

### Example:
```json
{
    "ai_warning": "Image may contain unrelated content: person (confidence: 92.3%)",
    "possibly_unrelated": true,
    "requires_admin_review": true
}
```

## 📏 Similarity Thresholds

### pHash Hamming Distance:

| Distance | Interpretation | Action |
|----------|---------------|--------|
| 0-2 | Nearly identical | Flag for review |
| 3-5 | Very similar | Flag for review |
| 6-10 | Somewhat similar | Consider unique |
| 11+ | Different | Unique |

**System Threshold**: ≤ 5 → Flag for review

### Why pHash Distance ≤ 5?

- **0-2**: Exact duplicates or minor edits
- **3-5**: Same image with different compression/resize
- **6+**: Different images (even if same subject)

## 👨‍💼 Admin Decision Flow

```
┌─────────────────────────────────────────────────────────────┐
│              Admin Reviews Flagged Image                     │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                  Admin Sees Context:                         │
│                  • Original image                            │
│                  • AI warning (if any)                       │
│                  • Similar image (if found)                  │
│                  • EXIF metadata                             │
│                  • Student information                       │
│                  • Tutorial category                         │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────────────┐
│              Admin Makes Decision:                           │
│              • Approve (with optional notes)                 │
│              • Reject (with optional notes)                  │
└─────────────────────┬───────────────────────────────────────┘
                      │
        ┌─────────────┴─────────────┐
        │                           │
        ▼                           ▼
┌──────────────┐           ┌──────────────┐
│   APPROVED   │           │   REJECTED   │
└──────┬───────┘           └──────┬───────┘
       │                          │
       ▼                          ▼
┌──────────────┐           ┌──────────────┐
│ Update       │           │ No progress  │
│ Progress:    │           │ update       │
│ • practice_  │           │              │
│   completed  │           │ Student can  │
│   = 1        │           │ re-upload    │
│ • practice_  │           │              │
│   admin_     │           │              │
│   approved   │           │              │
│   = 1        │           │              │
└──────────────┘           └──────────────┘
```

## 📈 Progress & Certificate Calculation

### Progress Update Logic:

```php
// Only update progress after admin approval
if ($admin_decision === 'approved') {
    UPDATE learning_progress 
    SET practice_completed = 1,
        practice_admin_approved = 1
    WHERE user_id = ? AND tutorial_id = ?
}
```

### Certificate Eligibility:

```php
// Calculate overall progress
$overallProgress = (
    $video_watched +           // 0 or 1
    $quiz_completed +          // 0 or 1
    $practice_admin_approved   // 0 or 1 (requires admin approval!)
) / 3;

// Certificate unlocks at 80%
if ($overallProgress >= 0.80) {
    // Unlock certificate
    // Example: 2/3 = 66.7% → No certificate
    //          3/3 = 100% → Certificate unlocked
}
```

### Important Notes:
- ✅ Practice must be **admin-approved** to count
- ✅ Certificate requires **80% overall progress**
- ✅ Auto-approved images count immediately
- ⏸️ Flagged images count only after admin approval

## 🎯 Category Enforcement

### How Categories Work:

1. **Student selects tutorial** → Category is determined
2. **System uses tutorial category** → Ground truth
3. **Comparison happens only within category** → No cross-category
4. **AI doesn't change category** → Category is fixed

### Example:

```
Tutorial: "Basic Embroidery Stitches"
Category: "embroidery"

✓ Compare with: Other embroidery practice images
✗ Don't compare with: Painting, drawing, crafts, etc.

Even if AI detects "textile" or "fabric", 
category remains "embroidery" (from tutorial)
```

## ⚠️ Important Constraints

### ✅ What System DOES:
- Detect similar images within same category
- Warn about possibly unrelated content
- Extract metadata for admin reference
- Require admin approval for flagged images
- Update progress after admin approval

### ❌ What System DOES NOT:
- ❌ Claim to detect Google images
- ❌ Claim to detect internet sources
- ❌ Auto-reject any images
- ❌ Train custom AI models
- ❌ Compare across different categories
- ❌ Use metadata for automatic decisions

## 🔧 Configuration

### Environment Variables:

```env
# Optional - System works without it
GOOGLE_VISION_API_KEY=your_api_key_here

# If not set:
# - AI warnings disabled
# - Only pHash similarity used
# - System still fully functional
```

### Thresholds (Hardcoded):

```php
// pHash similarity threshold
private const PHASH_DISTANCE_THRESHOLD = 5;

// AI confidence threshold
if ($confidence >= 0.80) {
    // Flag as possibly_unrelated
}
```

## 📊 Response Examples

### Clean Image (Auto-Approved):

```json
{
    "status": "unique",
    "requires_admin_review": false,
    "category": "embroidery",
    "images_compared": 45,
    "ai_warning": null,
    "similar_image": null
}
```

### Unrelated Content (Flagged):

```json
{
    "status": "possibly_unrelated",
    "requires_admin_review": true,
    "category": "embroidery",
    "images_compared": 45,
    "ai_warning": "Image may contain unrelated content: person (confidence: 92.3%)",
    "similar_image": null
}
```

### Similar Image (Flagged):

```json
{
    "status": "possible_reuse",
    "requires_admin_review": true,
    "category": "embroidery",
    "images_compared": 45,
    "ai_warning": null,
    "similar_image": {
        "image_id": "123_0",
        "distance": 3,
        "created_at": "2026-01-10 14:30:00"
    }
}
```

## 🎓 Best Practices

### For Admins:
1. ✅ Review flagged images daily
2. ✅ Provide clear feedback in notes
3. ✅ Consider context (tutorial difficulty, student level)
4. ✅ Check metadata for authenticity clues
5. ✅ Be consistent in decisions

### For Developers:
1. ✅ Monitor Google Vision API usage
2. ✅ Check error logs regularly
3. ✅ Backup database before updates
4. ✅ Test with various image types
5. ✅ Keep API key secure

### For Students:
1. ✅ Upload original practice work
2. ✅ Use images related to tutorial
3. ✅ Wait for admin review if flagged
4. ✅ Read feedback from admin
5. ✅ Re-upload if rejected

---

**Quick Reference Version**: 2.0
**Last Updated**: January 14, 2026
**Print-Friendly**: Yes
