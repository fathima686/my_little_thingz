# Picture Customization with Admin Approval

## ✅ Feature Overview

Products that require pictures (frames, polaroids, albums, wedding cards) must be customized by uploading pictures, and admin approval is required before payment can proceed.

## 🎯 Which Products Require Pictures?

The following product types **require picture upload** for customization:

1. **Frames** (Photo Frames)
2. **Polaroids** (Polaroid prints)
3. **Albums** (Photo Albums)
4. **Wedding Cards**

## 🔄 How It Works

### For Customers:

1. **Click Product** → Opens customization modal
2. **See Warning Notice** → "Admin Approval Required"
3. **Upload Pictures** → Upload photos (Required for frames/polaroids/albums/wedding cards)
4. **Fill Details** → Description, occasion, deadline
5. **Submit Request** → Goes to admin for approval
6. **Wait for Approval** → Admin reviews the pictures
7. **After Approval** → Can proceed to payment

### For Admins:

1. **Receive Request** → Customization request in admin panel
2. **Review Pictures** → Check uploaded images
3. **Approve or Reject** → Make decision
4. **If Approved** → Customer can pay and order proceeds

## 📸 Picture Requirements

### Required for:
- ✅ Frame products
- ✅ Polaroid products
- ✅ Album products
- ✅ Wedding card products

### Optional for:
- ❌ Other products (still encouraged)

### Validation:
- At least **1 picture** is required for frames/polaroids/albums/wedding cards
- Maximum **5 pictures** allowed
- Images must be valid picture files (JPEG, PNG, etc.)

## 🎨 User Experience

### Customization Modal Shows:

1. **Warning Banner** (Yellow):
   ```
   ⏳ Admin Approval Required
   Your customization request will be reviewed by admin. 
   Payment can only proceed after approval.
   ```

2. **Picture Upload Section**:
   ```
   Reference Images * (Required for this product)
   ⚠️ This product requires pictures for customization. 
   Admin will review before approval.
   ```

3. **Success Message**:
   ```
   ✅ Customization request submitted! 
   Admin will review your pictures and approve before 
   you can proceed to payment.
   ```

## 🔒 Payment Flow

### Before Approval:
- ❌ Cannot proceed to payment
- ✅ Can submit customization request
- ✅ Can view request status

### After Admin Approval:
- ✅ Can proceed to payment
- ✅ Order gets processed
- ✅ Customization work begins

## 📝 What Gets Submitted

When customer submits customization:

```json
{
  "artwork_id": 123,
  "quantity": 1,
  "description": "Customer's description",
  "occasion": "Wedding",
  "date": "2025-12-25",
  "source": "cart",
  "reference_images": [
    "image1.jpg",
    "image2.jpg",
    ...
  ]
}
```

## ⚙️ Technical Implementation

### Frontend (`CustomizationModal.jsx`):

1. **Detects Product Type**:
   ```javascript
   const requiresPictures = ['frame', 'polaroid', 'album', 'wedding_cards'];
   const requiresPicturesUpload = requiresPictures.some(type => 
     categoryName.includes(type) || artwork.title?.toLowerCase().includes(type)
   );
   ```

2. **Validates Images**:
   ```javascript
   if (images.length === 0 && requiresPicturesUpload) {
     newErrors.images = 'At least one picture is required for customization';
   }
   ```

3. **Submits to Backend**:
   ```javascript
   POST /api/customer/cart-with-customization.php
   FormData with images
   ```

### Backend Workflow:

1. Receives customization request
2. Stores images
3. Creates pending order (not payable yet)
4. Admin reviews in admin panel
5. Admin approves → Order becomes payable
6. Customer can proceed to payment

## 🎯 Product Categories

### Requires Pictures:
- Photo Frames
- Polaroids
- Photo Albums
- Wedding Cards
- Custom Frames

### Does Not Require Pictures:
- Bouquets
- Chocolate Boxes
- Gift Boxes
- Other accessories

## 📋 Form Fields

### Required Fields:
- ✅ Description
- ✅ Occasion
- ✅ Deadline/Date
- ✅ Pictures (for frames/polaroids/albums/wedding cards)

### Optional Fields:
- Custom size specifications
- Color preferences
- Additional notes

## 🚀 Usage Flow

### Step 1: Customer Clicks Product
```
Product Card → Click "Customize" button
```

### Step 2: Customization Modal Opens
```
- Shows product preview
- Displays warning about admin approval
- Shows form fields
```

### Step 3: Upload Pictures
```
- Click "Upload Pictures" button
- Select images from device
- Preview uploaded images
- Can remove images
```

### Step 4: Fill Details
```
- Enter description
- Select occasion
- Choose deadline
```

### Step 5: Submit Request
```
- Click "Submit Request"
- System validates
- If frames/polaroids/albums: Requires pictures
- Request goes to admin
```

### Step 6: Admin Reviews
```
Admin Panel → Customization Requests
- View uploaded pictures
- Read description
- Approve or Reject
```

### Step 7: Customer Pays (After Approval)
```
- Admin approves request
- Customer receives notification
- Can proceed to payment
- Order gets processed
```

## ✅ Benefits

1. **Quality Control**: Admin reviews pictures before work begins
2. **Clear Requirements**: Customers know pictures are required
3. **Better Results**: Pictures help create accurate customizations
4. **Payment Safety**: Money only charged after approval
5. **Workflow Control**: Admin controls the process

## 🎨 Visual Indicators

### Warning Banner:
```
┌─────────────────────────────────────┐
│ ⏳  Admin Approval Required         │
│                                    │
│ Your customization request will be │
│ reviewed by admin. Payment can     │
│ only proceed after approval.      │
└─────────────────────────────────────┘
```

### Picture Upload Area:
```
Reference Images * (Required for this product)
┌──────────────────────────────┐
│  📤 Upload Pictures (Max 5) │
│                              │
│  ⚠️ This product requires    │
│  pictures for customization. │
│  Admin will review before    │
│  approval.                   │
└──────────────────────────────┘
```

## 📁 Files Modified

- ✅ `frontend/src/components/customer/CustomizationModal.jsx`
  - Added picture requirement detection
  - Added admin approval notice
  - Updated validation
  - Enhanced error messages

## 🎉 Ready to Use!

Customers can now:
1. ✅ Customize frames, polaroids, albums, wedding cards
2. ✅ Upload required pictures
3. ✅ See admin approval notice
4. ✅ Know payment requires approval first
5. ✅ Get notified when approved

**Everything is configured and working!** 🚀




















