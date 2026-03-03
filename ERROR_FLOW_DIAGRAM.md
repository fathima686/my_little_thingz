# Image Analysis Error Flow Diagram

## Complete Processing Flow with Error Handling

```
┌─────────────────────────────────────────────────────────────────┐
│                     Image Upload Request                         │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────────────┐
│              Load Environment Variables                          │
│              (EnvLoader::load())                                 │
└─────────────────────────┬───────────────────────────────────────┘
                          │
                          ▼
                    ┌─────────┐
                    │ Success?│
                    └────┬────┘
                         │
            ┌────────────┴────────────┐
            │                         │
           YES                       NO
            │                         │
            ▼                         ▼
    ┌───────────────┐         ┌──────────────┐
    │ Continue      │         │ Log Warning  │
    └───────┬───────┘         └──────┬───────┘
            │                        │
            │                        └──────────┐
            ▼                                   │
┌─────────────────────────────────────────┐    │
│     Initialize Service                   │    │
│     (Check GD Extension)                 │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ GD OK?  │                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │                 ┌──────────────────┐  │
        │                 │ Return Error:    │  │
        │                 │ GD_NOT_AVAILABLE │  │
        │                 └──────────────────┘  │
        │                         │             │
        ▼                         │             │
┌───────────────────────────┐    │             │
│  File Upload Processing   │    │             │
└───────────┬───────────────┘    │             │
            │                    │             │
            ▼                    │             │
┌───────────────────────────┐    │             │
│  For Each Uploaded File   │    │             │
└───────────┬───────────────┘    │             │
            │                    │             │
            ▼                    │             │
┌─────────────────────────────────────────┐    │
│         evaluateImage()                  │    │
│                                          │    │
│  Step 1: Validate File Exists           │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ Exists? │                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │                 ┌──────────────────┐  │
        │                 │ Return Error:    │  │
        │                 │ FILE_NOT_FOUND   │  │
        │                 └──────────────────┘  │
        │                         │             │
        ▼                         │             │
┌─────────────────────────────────────────┐    │
│  Step 2: Generate pHash                  │    │
│  - Check GD extension                    │    │
│  - Read file                             │    │
│  - Decode image                          │    │
│  - Re-encode to JPEG                     │    │
│  - Resize to 32x32                       │    │
│  - Calculate hash                        │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ Success?│                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │         ┌───────────────────────────┐ │
        │         │ Return Error:             │ │
        │         │ - GD_NOT_AVAILABLE        │ │
        │         │ - FILE_READ_FAILED        │ │
        │         │ - IMAGE_DECODE_FAILED     │ │
        │         │ - IMAGE_REENCODE_FAILED   │ │
        │         │ - IMAGE_RESIZE_FAILED     │ │
        │         │ - PHASH_FAILED            │ │
        │         └───────────────────────────┘ │
        │                         │             │
        ▼                         │             │
┌─────────────────────────────────────────┐    │
│  Step 3: Extract EXIF Metadata           │    │
│  (Non-critical, continues on error)      │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
┌─────────────────────────────────────────┐    │
│  Step 4: Analyze with Vision API         │    │
│  - Check API key configured              │    │
│  - Validate file exists                  │    │
│  - Encode to base64                      │    │
│  - Call Vision API                       │    │
│  - Validate response                     │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ Success?│                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │         ┌───────────────────────────┐ │
        │         │ Return Error:             │ │
        │         │ - VISION_KEY_MISSING      │ │
        │         │ - VISION_API_NETWORK_ERROR│ │
        │         │ - VISION_API_FAILED       │ │
        │         │ - VISION_API_INVALID_RESP │ │
        │         │ - VISION_API_NO_LABELS    │ │
        │         │ - VISION_API_EXCEPTION    │ │
        │         └───────────────────────────┘ │
        │                         │             │
        ▼                         │             │
┌─────────────────────────────────────────┐    │
│  Step 5: Check Similarity in Category    │    │
│  - Query database for same category      │    │
│  - Calculate Hamming distance            │    │
│  - Find best match                       │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ Success?│                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │                 ┌──────────────────┐  │
        │                 │ Return Error:    │  │
        │                 │ DB_ERROR         │  │
        │                 └──────────────────┘  │
        │                         │             │
        ▼                         │             │
┌─────────────────────────────────────────┐    │
│  Step 6: Make Evaluation Decision        │    │
│  - Check AI warnings                     │    │
│  - Check pHash distance                  │    │
│  - Determine status                      │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
┌─────────────────────────────────────────┐    │
│  Step 7: Store Evaluation Result         │    │
│  - Insert into database                  │    │
│  - Add to review queue if flagged        │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      ▼                         │
                ┌─────────┐                     │
                │ Success?│                     │
                └────┬────┘                     │
                     │                          │
        ┌────────────┴────────────┐             │
        │                         │             │
       YES                       NO             │
        │                         │             │
        │                         ▼             │
        │                 ┌──────────────────┐  │
        │                 │ Return Error:    │  │
        │                 │ DB_ERROR         │  │
        │                 └──────────────────┘  │
        │                         │             │
        ▼                         │             │
┌─────────────────────────────────────────┐    │
│         Return Success Result            │    │
│  {                                       │    │
│    status: "unique" | "possible_reuse"   │    │
│           | "possibly_unrelated",        │    │
│    explanation: "...",                   │    │
│    requires_admin_review: true/false,    │    │
│    category: "...",                      │    │
│    images_compared: N,                   │    │
│    metadata_notes: "...",                │    │
│    error: false                          │    │
│  }                                       │    │
└─────────────────────┬───────────────────┘    │
                      │                         │
                      └─────────────────────────┤
                                                │
                      ┌─────────────────────────┘
                      │
                      ▼
┌─────────────────────────────────────────────────────┐
│              Return Error Result                     │
│  {                                                   │
│    status: "error",                                  │
│    error_code: "SPECIFIC_ERROR_CODE",                │
│    error_message: "Human-readable message",          │
│    explanation: "Processing failed - admin review",  │
│    requires_admin_review: true,                      │
│    error: true                                       │
│  }                                                   │
└─────────────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────┐
│              Aggregate Results                       │
│  - Collect all image results                         │
│  - Count errors                                      │
│  - Determine overall status                          │
└─────────────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────┐
│              Return API Response                     │
│  {                                                   │
│    status: "success",                                │
│    authenticity_analysis: {                          │
│      analysis_results: [...],                        │
│      summary: {                                      │
│        processing_errors: N                          │
│      },                                              │
│      warnings: {                                     │
│        processing_errors: [...]                      │
│      },                                              │
│      error_codes: {...}                              │
│    }                                                 │
│  }                                                   │
└─────────────────────────────────────────────────────┘
```

## Error Code Decision Tree

```
Image Processing Error?
│
├─ Environment/Configuration
│  ├─ API key missing? → VISION_KEY_MISSING
│  └─ GD not loaded? → GD_NOT_AVAILABLE
│
├─ File Operations
│  ├─ File not found? → FILE_NOT_FOUND
│  └─ Can't read file? → FILE_READ_FAILED
│
├─ Image Processing
│  ├─ Can't decode? → IMAGE_DECODE_FAILED
│  ├─ Can't re-encode? → IMAGE_REENCODE_FAILED
│  ├─ Can't resize? → IMAGE_RESIZE_FAILED
│  └─ Hash failed? → PHASH_FAILED
│
├─ Vision API
│  ├─ Network error? → VISION_API_NETWORK_ERROR
│  ├─ API error? → VISION_API_FAILED
│  ├─ Invalid response? → VISION_API_INVALID_RESPONSE
│  ├─ No labels? → VISION_API_NO_LABELS
│  └─ Exception? → VISION_API_EXCEPTION
│
├─ Database
│  └─ DB operation failed? → DB_ERROR
│
└─ General
   └─ Unknown error? → EVALUATION_FAILED
```

## Success Flow (No Errors)

```
Upload → Load Env → Init Service → Process File
   ↓
Validate File → Generate pHash → Extract EXIF
   ↓
Call Vision API → Check Similarity → Make Decision
   ↓
Store Result → Return Success
   ↓
{
  status: "unique",
  explanation: "No similar images found",
  requires_admin_review: false,
  error: false
}
```

## Error Flow Example (Missing API Key)

```
Upload → Load Env → Init Service → Process File
   ↓
Validate File → Generate pHash → Extract EXIF
   ↓
Call Vision API
   ↓
Check API Key → NOT FOUND
   ↓
Return Error
   ↓
{
  status: "error",
  error_code: "VISION_KEY_MISSING",
  error_message: "Google Vision API key is not configured...",
  requires_admin_review: true,
  error: true
}
```

## Error Flow Example (GD Not Available)

```
Upload → Load Env → Init Service → Process File
   ↓
Validate File → Generate pHash
   ↓
Check GD Extension → NOT LOADED
   ↓
Return Error
   ↓
{
  status: "error",
  error_code: "GD_NOT_AVAILABLE",
  error_message: "PHP GD extension is not enabled...",
  requires_admin_review: true,
  error: true
}
```

## Error Flow Example (Corrupted Image)

```
Upload → Load Env → Init Service → Process File
   ↓
Validate File → Generate pHash
   ↓
Read File → Decode Image → FAILED
   ↓
Return Error
   ↓
{
  status: "error",
  error_code: "IMAGE_DECODE_FAILED",
  error_message: "Failed to decode image. File may be corrupted...",
  requires_admin_review: true,
  error: true
}
```

## Key Improvements

### Before Fix
```
Any Error → Return { score: 0, message: "Processing error occurred" }
```
❌ No specific error code
❌ No actionable message
❌ No way to debug

### After Fix
```
Specific Error → Return {
  status: "error",
  error_code: "SPECIFIC_CODE",
  error_message: "Clear explanation",
  error: true
}
```
✅ Specific error code
✅ Actionable message
✅ Easy to debug

## Error Handling Principles

1. **Fail Fast**: Stop processing at first error
2. **Be Specific**: Return exact error code
3. **Be Clear**: Provide human-readable message
4. **Be Actionable**: Tell user what to do
5. **Log Everything**: Log errors for debugging
6. **Never Silent**: Always return error state
7. **No Fake Data**: Never return score: 0

## Monitoring Points

```
┌─────────────────────────────────────┐
│         Error Monitoring             │
├─────────────────────────────────────┤
│ • Count errors by code               │
│ • Track error frequency              │
│ • Alert on repeated errors           │
│ • Monitor API quota usage            │
│ • Track processing success rate      │
│ • Log all errors with context        │
└─────────────────────────────────────┘
```

## Summary

✅ **Every step validated**
✅ **Every error has a code**
✅ **Every error has a message**
✅ **Every error is logged**
✅ **Every error is returned**
✅ **No silent failures**
✅ **No fake scores**
