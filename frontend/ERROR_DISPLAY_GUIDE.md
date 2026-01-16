# UI Error Display Guide

## Overview
The backend now returns structured error codes instead of silent failures. Update your UI to display these errors to users.

## Error Response Structure

### Success Response
```javascript
{
  status: "unique",
  explanation: "No similar images found in the platform",
  requires_admin_review: false,
  error: false
}
```

### Error Response
```javascript
{
  status: "error",
  error_code: "VISION_KEY_MISSING",
  error_message: "Google Vision API key is not configured...",
  explanation: "Processing failed - admin review required",
  requires_admin_review: true,
  error: true
}
```

## Detecting Errors in UI

```javascript
// Check if analysis result has an error
function hasError(analysisResult) {
  return analysisResult.error === true || 
         analysisResult.status === 'error' ||
         analysisResult.error_code !== null;
}

// Get user-friendly error message
function getErrorMessage(analysisResult) {
  if (analysisResult.error_message) {
    return analysisResult.error_message;
  }
  return "An unknown error occurred during image analysis";
}
```

## Error Display Examples

### Example 1: Missing API Key
```javascript
if (result.error_code === 'VISION_KEY_MISSING') {
  showError(
    "Configuration Error",
    "The image analysis service is not properly configured. " +
    "Please contact the administrator.",
    "warning"
  );
}
```

### Example 2: GD Extension Not Available
```javascript
if (result.error_code === 'GD_NOT_AVAILABLE') {
  showError(
    "System Error",
    "Image processing is currently unavailable. " +
    "Please contact the administrator.",
    "error"
  );
}
```

### Example 3: File Processing Error
```javascript
if (result.error_code === 'IMAGE_DECODE_FAILED') {
  showError(
    "Invalid Image",
    "The uploaded image appears to be corrupted or in an unsupported format. " +
    "Please try uploading a different image.",
    "warning"
  );
}
```

### Example 4: Vision API Error
```javascript
if (result.error_code === 'VISION_API_FAILED') {
  showError(
    "Analysis Error",
    "Image content analysis failed. Your upload has been flagged for manual review.",
    "info"
  );
}
```

## Complete Error Handling Function

```javascript
function displayAnalysisResult(result) {
  // Check for errors
  if (result.error === true || result.status === 'error') {
    const errorCode = result.error_code || 'UNKNOWN';
    const errorMessage = result.error_message || 'An unknown error occurred';
    
    // Map error codes to user-friendly messages
    const userMessages = {
      'VISION_KEY_MISSING': {
        title: 'Configuration Error',
        message: 'Image analysis service is not configured. Contact administrator.',
        severity: 'warning',
        action: 'Your upload will be reviewed manually.'
      },
      'GD_NOT_AVAILABLE': {
        title: 'System Error',
        message: 'Image processing is currently unavailable. Contact administrator.',
        severity: 'error',
        action: 'Please try again later.'
      },
      'FILE_NOT_FOUND': {
        title: 'Upload Error',
        message: 'The uploaded file could not be found.',
        severity: 'error',
        action: 'Please try uploading again.'
      },
      'IMAGE_DECODE_FAILED': {
        title: 'Invalid Image',
        message: 'The image appears to be corrupted or in an unsupported format.',
        severity: 'warning',
        action: 'Please upload a JPEG, PNG, or GIF image.'
      },
      'PHASH_FAILED': {
        title: 'Processing Error',
        message: 'Failed to analyze image similarity.',
        severity: 'warning',
        action: 'Your upload will be reviewed manually.'
      },
      'VISION_API_FAILED': {
        title: 'Analysis Error',
        message: 'Content analysis service is temporarily unavailable.',
        severity: 'info',
        action: 'Your upload will be reviewed manually.'
      },
      'DB_ERROR': {
        title: 'Database Error',
        message: 'Failed to save analysis results.',
        severity: 'error',
        action: 'Please contact administrator.'
      }
    };
    
    const userMsg = userMessages[errorCode] || {
      title: 'Processing Error',
      message: errorMessage,
      severity: 'warning',
      action: 'Your upload will be reviewed manually.'
    };
    
    // Display error to user
    showNotification(userMsg.title, userMsg.message, userMsg.severity);
    showActionMessage(userMsg.action);
    
    // Log for debugging
    console.error('Image analysis error:', {
      errorCode,
      errorMessage,
      result
    });
    
    return false; // Indicate failure
  }
  
  // Success - display normal result
  displaySuccessResult(result);
  return true;
}
```

## Updating Upload Response Display

```javascript
// In your upload success handler
fetch('/backend/api/pro/practice-upload-v2.php', {
  method: 'POST',
  body: formData
})
.then(response => response.json())
.then(data => {
  if (data.status === 'success') {
    const analysis = data.authenticity_analysis;
    
    // Check for processing errors
    if (analysis.summary.processing_errors > 0) {
      showWarning(
        `${analysis.summary.processing_errors} image(s) had processing errors`,
        'These images will be reviewed manually by an administrator.'
      );
      
      // Display specific errors
      analysis.warnings.processing_errors.forEach(error => {
        console.error('Processing error:', error);
        showErrorDetail(
          `${error.file}: ${error.error_message}`,
          'error'
        );
      });
    }
    
    // Display analysis results for each image
    analysis.analysis_results.forEach(result => {
      if (result.error_code) {
        // Show error for this specific image
        showImageError(result.file_name, result.error_message);
      } else {
        // Show success for this image
        showImageSuccess(result.file_name, result.explanation);
      }
    });
    
    // Show overall status
    if (analysis.summary.requires_admin_review > 0) {
      showInfo(
        'Manual Review Required',
        `${analysis.summary.requires_admin_review} image(s) flagged for admin review.`
      );
    } else {
      showSuccess(
        'Upload Complete',
        'All images passed automatic verification!'
      );
    }
  } else {
    showError('Upload Failed', data.message);
  }
})
.catch(error => {
  showError('Network Error', 'Failed to upload images. Please try again.');
  console.error('Upload error:', error);
});
```

## Error Severity Levels

Use these severity levels for consistent UI feedback:

| Severity | Color | Icon | Use Case |
|----------|-------|------|----------|
| `error` | Red | ❌ | Critical errors (GD_NOT_AVAILABLE, DB_ERROR) |
| `warning` | Orange | ⚠️ | Recoverable issues (IMAGE_DECODE_FAILED, PHASH_FAILED) |
| `info` | Blue | ℹ️ | Informational (VISION_API_FAILED, manual review) |
| `success` | Green | ✓ | Successful processing |

## Admin Dashboard Updates

For the admin review dashboard, display error information:

```javascript
function renderReviewItem(item) {
  const hasError = item.error_code !== null;
  
  return `
    <div class="review-item ${hasError ? 'has-error' : ''}">
      <div class="image-preview">
        <img src="${item.image_url}" alt="${item.file_name}">
        ${hasError ? '<span class="error-badge">Processing Error</span>' : ''}
      </div>
      
      <div class="details">
        <h3>${item.file_name}</h3>
        
        ${hasError ? `
          <div class="error-info">
            <strong>Error:</strong> ${item.error_code}<br>
            <span>${item.error_message}</span>
          </div>
        ` : `
          <div class="analysis-info">
            <strong>Status:</strong> ${item.status}<br>
            <strong>Category:</strong> ${item.category}<br>
            <strong>Images Compared:</strong> ${item.images_compared}
          </div>
        `}
        
        ${item.flagged_reason ? `
          <div class="flag-reason">
            <strong>Flagged:</strong> ${item.flagged_reason}
          </div>
        ` : ''}
      </div>
      
      <div class="actions">
        <button onclick="approveImage('${item.image_id}')">Approve</button>
        <button onclick="rejectImage('${item.image_id}')">Reject</button>
        ${hasError ? `
          <button onclick="retryAnalysis('${item.image_id}')">Retry Analysis</button>
        ` : ''}
      </div>
    </div>
  `;
}
```

## CSS Styling Suggestions

```css
/* Error states */
.has-error {
  border-left: 4px solid #dc3545;
  background-color: #fff5f5;
}

.error-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #dc3545;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.error-info {
  background: #fff3cd;
  border: 1px solid #ffc107;
  padding: 12px;
  border-radius: 4px;
  margin: 10px 0;
}

.error-info strong {
  color: #856404;
}

/* Notification styles */
.notification {
  padding: 16px;
  border-radius: 8px;
  margin: 10px 0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.notification.error {
  background: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

.notification.warning {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  color: #856404;
}

.notification.info {
  background: #d1ecf1;
  border: 1px solid #bee5eb;
  color: #0c5460;
}

.notification.success {
  background: #d4edda;
  border: 1px solid #c3e6cb;
  color: #155724;
}
```

## Testing Error Display

Test your UI with these scenarios:

1. **Missing API Key**: Temporarily remove API key from `.env`
2. **Invalid Image**: Upload a corrupted image file
3. **Network Error**: Disconnect internet during upload
4. **Database Error**: Temporarily break database connection

## Summary

✓ Always check for `error` flag or `error_code` in responses
✓ Display user-friendly messages, not technical error codes
✓ Use appropriate severity levels (error, warning, info)
✓ Log technical details to console for debugging
✓ Provide actionable next steps to users
✓ Show admin review status clearly
✓ Handle both individual image errors and batch errors
