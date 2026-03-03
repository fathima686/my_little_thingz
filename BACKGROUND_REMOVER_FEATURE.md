# Background Remover Feature - Design Editor

## Overview

Added a professional background removal feature to the design editor that allows users to remove backgrounds from uploaded images with a single click.

## Features Implemented

### 1. Remove Background Button
- Added to Image Properties Panel
- Appears when an image is selected
- Icon: Magic wand (fa-magic)
- Position: Above "Replace Image" button

### 2. Dual Processing Methods

**Method A: API-Based (Professional)**
- Uses remove.bg API for high-quality results
- Requires API key configuration
- Best for production use

**Method B: Client-Side (Fallback)**
- Basic canvas-based processing
- No API key required
- Works offline
- Good for simple backgrounds

### 3. Smart Fallback System
- Automatically detects if API key is configured
- Falls back to client-side if API unavailable
- User-friendly error messages

## Files Modified/Created

### Created Files
1. `backend/api/admin/remove-background.php` - Background removal API endpoint

### Modified Files
1. `frontend/admin/design-editor.html` - Added remove background button
2. `frontend/admin/js/design-editor.js` - Added background removal logic

## Setup Instructions

### Option 1: Using remove.bg API (Recommended)

1. **Get API Key**
   - Sign up at https://remove.bg/api
   - Free tier: 50 images/month
   - Paid plans available for more

2. **Configure API Key**
   
   Add to `.env` file:
   ```
   REMOVE_BG_API_KEY=your_api_key_here
   ```

3. **Test**
   - Upload an image in the editor
   - Select the image
   - Click "Remove Background"
   - Background will be removed professionally

### Option 2: Client-Side Processing (No Setup)

- Works automatically if no API key configured
- Basic white background removal
- Adjust threshold in code if needed

## Usage

1. **Upload Image**
   - Click "Add Image" button
   - Select image file

2. **Select Image**
   - Click on the uploaded image

3. **Remove Background**
   - Click "Remove Background" button in properties panel
   - Wait for processing (2-5 seconds)
   - Background removed!

4. **Adjust if Needed**
   - Use client-side method for simple backgrounds
   - Use API method for complex backgrounds

## Technical Details

### API Endpoint
```
POST /backend/api/admin/remove-background.php
```

**Request**:
- `image_base64`: Base64 encoded image
- OR `image_url`: URL to image
- OR `image`: File upload

**Response**:
```json
{
  "success": true,
  "image": "data:image/png;base64,...",
  "format": "png"
}
```

### JavaScript Methods

**removeBackground()** - Main method
- Gets selected image
- Calls API
- Replaces image with result

**removeBackgroundClientSide()** - Fallback
- Canvas-based processing
- Makes white pixels transparent
- Adjustable threshold

## Customization

### Adjust Client-Side Threshold

In `design-editor.js`, line ~950:
```javascript
const threshold = 240; // Adjust 0-255
```

- Lower value: More aggressive removal
- Higher value: More conservative

### Change API Settings

In `remove-background.php`:
```php
$postFields = [
    'size' => 'auto',  // or 'preview', 'full', 'medium'
    'format' => 'png'  // or 'jpg', 'zip'
];
```

## Error Handling

### Common Errors

**"API key not configured"**
- Solution: Add REMOVE_BG_API_KEY to environment

**"Failed to remove background"**
- Check internet connection
- Verify API key is valid
- Check API quota

**"Please select an image first"**
- Select an image before clicking button

## Cost Considerations

### remove.bg API Pricing
- Free: 50 images/month
- Subscription: $9/month for 500 images
- Pay-as-you-go: $0.20 per image

### Client-Side Processing
- Free
- Unlimited
- Lower quality

## Future Enhancements

Possible improvements:
1. Advanced edge detection
2. Manual refinement tools
3. Batch processing
4. Preview before/after
5. Undo background removal
6. Save original image

## Testing

Test with different image types:
- ✓ Simple white background
- ✓ Complex backgrounds
- ✓ Transparent PNGs
- ✓ JPEGs
- ✓ Large images (up to 5MB)

## Support

For issues:
1. Check browser console for errors
2. Verify API key configuration
3. Test with client-side method
4. Check image file format

---

**Status**: ✅ Complete and Ready to Use  
**Version**: 1.0.0  
**Date**: 2026-02-25
