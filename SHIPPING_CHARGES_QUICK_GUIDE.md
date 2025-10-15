# 🚢 Shipping Charges - Quick Guide

## What Changed?

### BEFORE ❌
```
Price Summary:
├─ Subtotal: ₹400
└─ Grand Total: ₹400
   (No shipping charges!)
```

### AFTER ✅
```
Price Summary:
├─ Subtotal: ₹400
├─ Shipping: ₹60
└─ Grand Total: ₹460
   (Shipping included!)
```

---

## How It Works

### 1. **Weight-Based Calculation**
Every artwork now has a weight (default: 0.5 kg)

### 2. **Shipping Rate**
- ₹60 per kilogram
- Minimum charge: ₹60

### 3. **Formula**
```
Shipping = max(₹60, ceil(total_weight) × ₹60)
```

### 4. **Examples**

| Cart Weight | Shipping Charge |
|-------------|-----------------|
| 0.5 kg      | ₹60             |
| 1.0 kg      | ₹60             |
| 1.5 kg      | ₹120            |
| 2.5 kg      | ₹180            |
| 3.1 kg      | ₹240            |

---

## What You'll See

### 1. **Cart Page**
When you click "Pay Securely", the cart summary will update to show:
- Subtotal (items total)
- Shipping charges (calculated)
- Grand Total (subtotal + shipping)

### 2. **Razorpay Checkout**
The payment modal will show the **full amount including shipping**

### 3. **Order Confirmation**
Your order will include:
- Subtotal
- Shipping cost
- Total weight
- Total amount paid

---

## Testing Your Order

### Step-by-Step
1. ✅ Add items to cart
2. ✅ Go to cart page
3. ✅ Fill shipping address
4. ✅ Click "Pay Securely"
5. ✅ **Look for shipping charges in cart summary**
6. ✅ **Verify Grand Total = Subtotal + Shipping**
7. ✅ Razorpay modal opens with correct total
8. ✅ Complete payment

### What to Verify
- [ ] Shipping charges appear (not "Calculated at checkout")
- [ ] Grand Total includes shipping
- [ ] Razorpay shows correct amount
- [ ] Payment processes successfully

---

## Files Changed

### Backend
- `backend/api/customer/razorpay-create-order.php` - Calculates shipping
- `backend/database/add_artwork_weight.php` - Adds weight to artworks

### Frontend
- `frontend/src/pages/CartPage.jsx` - Displays shipping charges

---

## Quick Test

### Test with Sample Cart
```
Item: Artwork (₹400, 0.5 kg)
Quantity: 1

Expected Result:
├─ Subtotal: ₹400
├─ Shipping: ₹60 (0.5 kg → 1 kg × ₹60)
└─ Grand Total: ₹460
```

---

## Need Help?

### Check These
1. Browser console for errors
2. Network tab for API response
3. Verify shipping appears before Razorpay opens
4. Confirm payment amount matches Grand Total

### Common Issues
- **Shipping not showing:** Refresh page and try again
- **Wrong amount:** Check if backend is running
- **Payment fails:** Verify Razorpay credentials

---

## Summary

✅ **Shipping charges are now calculated automatically**
✅ **Based on product weight (₹60/kg minimum)**
✅ **Displayed before payment**
✅ **Included in Razorpay checkout**
✅ **Stored in order database**

**You're all set!** 🎉

---

*For detailed technical documentation, see: SHIPPING_CHARGES_IMPLEMENTATION.md*