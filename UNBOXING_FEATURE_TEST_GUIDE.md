# 📹 Unboxing Feature Testing Guide

## ✅ Setup Complete

**Order marked as delivered for testing:**
- **Order ID:** 66
- **Order Number:** ORD-20260121-091647-4e9980
- **Customer:** Fathima (fathimashibu15@gmail.com)
- **Amount:** ₹160.00
- **Status:** DELIVERED (just marked)

## 🎬 How to Test the Unboxing Feature

### Step 1: Customer Login
1. **Login as customer:** `fathimashibu15@gmail.com`
2. Navigate to **Orders/Dashboard** page
3. Look for order `ORD-20260121-091647-4e9980`

### Step 2: Find Unboxing Section
The unboxing feature appears in two places:
1. **Order Card View:** Look for "Unboxing Video Verification" section
2. **Order Detail Modal:** Click on order to see detailed view

### Step 3: Submit Unboxing Request
1. Click **"Report Issue with This Order"** button
2. Fill out the form:
   - **Issue Type:** Select from dropdown (damaged, missing items, etc.)
   - **Description:** Describe the issue
   - **Video Upload:** Upload test video (MP4, MOV, AVI - Max 100MB)
3. Click **"Submit Request"**

### Step 4: Admin Review
1. **Login as admin**
2. Go to **Admin Dashboard**
3. Click **"📹 Unboxing Review"** tab
4. Review submitted requests
5. **Approve/Reject** with admin notes

## 🔧 Technical Implementation

### Frontend Components
- `UnboxingVideoRequest.jsx` - Full modal component
- `UnboxingVideoRequestCard.jsx` - Compact card component
- `UnboxingVideoReview.jsx` - Admin review component
- `OrderTracking.jsx` - Integrates unboxing components

### Backend APIs
- `backend/api/customer/unboxing-requests.php` - Customer submissions
- `backend/api/admin/unboxing-review.php` - Admin review system

### Database Tables
- `unboxing_requests` - Stores customer requests
- `unboxing_request_history` - Tracks status changes

## 📋 Feature Requirements

### Customer Side
- ✅ Only shows for orders with `status = 'delivered'`
- ✅ Must be submitted within 48 hours of delivery
- ✅ One request per order limit
- ✅ Video upload with size/format validation
- ✅ Real-time upload progress
- ✅ Request status tracking

### Admin Side
- ✅ View all unboxing requests
- ✅ Filter by status (pending, approved, rejected)
- ✅ Video player for review
- ✅ Approve/reject with notes
- ✅ Request history tracking
- ✅ Statistics dashboard

## 🧪 Test Scenarios

### Scenario 1: Valid Request
- Login as `fathimashibu15@gmail.com`
- Find delivered order `ORD-20260121-091647-4e9980`
- Submit unboxing request with video
- Verify request appears in admin dashboard

### Scenario 2: Time Limit Check
- Try submitting request for older delivered orders
- Should show "48-hour limit exceeded" message

### Scenario 3: Duplicate Prevention
- Submit one request successfully
- Try submitting another for same order
- Should show "already submitted" message

### Scenario 4: Admin Workflow
- Login as admin
- Review pending requests
- Approve/reject with notes
- Verify status updates in customer view

## 🎯 Expected Behavior

### Customer Experience
1. **Order Card:** Shows unboxing section for delivered orders
2. **Expandable Form:** Click to show/hide request form
3. **Upload Progress:** Real-time video upload feedback
4. **Status Display:** Shows current request status
5. **Time Validation:** Prevents late submissions

### Admin Experience
1. **Dashboard Tab:** Dedicated unboxing review section
2. **Request List:** All requests with filtering
3. **Video Player:** Built-in video review
4. **Action Buttons:** Approve/reject with notes
5. **Statistics:** Request counts by status

## 🔍 Troubleshooting

### If Unboxing Section Doesn't Appear
- Check order status is exactly 'delivered'
- Verify delivery timestamp is within 48 hours
- Check browser console for JavaScript errors

### If Video Upload Fails
- Check file size (max 100MB)
- Verify file format (MP4, MOV, AVI)
- Check server upload limits in php.ini

### If Admin Review Doesn't Load
- Verify admin authentication
- Check API endpoints are accessible
- Review browser network tab for errors

## 📊 Additional Test Orders

Other delivered orders available for testing:
- Order 52: `ORD202601198329` - Admin User (fathima470077@gmail.com)
- Order 51: `ORD202601195392` - Admin User (fathima470077@gmail.com)
- Order 19: `ORD-20250917-082534-7c7848` - Fathima (fathimashibu15@gmail.com)

## 🎉 Success Criteria

The unboxing feature is working correctly if:
- ✅ Unboxing section appears only for delivered orders
- ✅ Video upload works with progress indication
- ✅ Form validation prevents invalid submissions
- ✅ Admin can review and approve/reject requests
- ✅ Status updates reflect in customer view
- ✅ Time limits are enforced (48 hours)
- ✅ Duplicate requests are prevented

---

**Ready to test!** Login as `fathimashibu15@gmail.com` and look for order `ORD-20260121-091647-4e9980` to start testing the unboxing feature.