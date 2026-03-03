# Quick Start: Background Remover

## What Was Added

✅ **Remove Background Button** in image properties panel  
✅ **Professional API Integration** (remove.bg)  
✅ **Client-Side Fallback** (works without API)  
✅ **Smart Auto-Detection** (chooses best method)  

## How to Use

### Step 1: Upload Image
1. Click "Add Image" button
2. Select your image file
3. Image appears on canvas

### Step 2: Remove Background
1. Click on the image to select it
2. Look at right sidebar (Image Properties)
3. Click "Remove Background" button
4. Wait 2-5 seconds
5. Done! Background removed

## Setup (Optional - For Better Results)

### Get remove.bg API Key (Free)

1. Go to https://remove.bg/api
2. Sign up (free account)
3. Get your API key
4. Add to `.env` file:
   ```
   REMOVE_BG_API_KEY=your_key_here
   ```

**Free Tier**: 50 images/month  
**Paid Plans**: From $9/month for 500 images

### Without API Key

- Works automatically with basic processing
- Good for simple white backgrounds
- No setup needed
- Unlimited use

## What You'll See

### With API Key
- High-quality background removal
- Works on complex backgrounds
- Professional results
- Message: "Background removed successfully!"

### Without API Key
- Basic background removal
- Works on simple backgrounds
- Message: "Background removed (basic processing)"

## Tips

1. **Best Results**: Use remove.bg API for complex images
2. **Simple Backgrounds**: Client-side works fine for white/solid backgrounds
3. **Image Quality**: Upload high-quality images for best results
4. **File Size**: Keep images under 5MB

## Troubleshooting

**Button not showing?**
- Make sure you selected an image (click on it)

**Not working?**
- Check browser console for errors
- Try refreshing the page
- Verify image is selected

**Poor results?**
- Get remove.bg API key for better quality
- Try different image
- Adjust threshold in code (for developers)

## Files Changed

- `frontend/admin/design-editor.html` - Added button
- `frontend/admin/js/design-editor.js` - Added functionality
- `backend/api/admin/remove-background.php` - API endpoint

---

**Ready to use!** Just upload an image and click "Remove Background"
