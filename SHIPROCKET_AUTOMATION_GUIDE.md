# 🤖 Shiprocket Automation Guide

## ✅ **YES! It's Now Fully Automatic!**

Your Shiprocket integration now **automatically processes shipments** when customers place orders and complete payment via Razorpay.

---

## 🔄 **Automatic Workflow**

### **What Happens After Payment:**

1. **Customer completes Razorpay payment** ✅
2. **Payment verified successfully** ✅
3. **🤖 AUTOMATION STARTS:**
   - ✅ **Shipment created** in Shiprocket
   - ✅ **Courier automatically assigned** (cheapest by default)
   - ✅ **AWB tracking number generated**
   - ⚠️ **Pickup scheduling** (optional, disabled by default)

### **Timeline:**
```
Order Placed → Payment Success → [Instant] Shipment Created → [Instant] Courier Assigned → Ready for Pickup
```

**Total Time: ~2-5 seconds after payment confirmation!**

---

## ⚙️ **Configuration**

All automation settings are in: `backend/config/shiprocket_automation.php`

### **Current Settings:**

```php
'auto_create_shipment' => true,        // ✅ Enabled
'auto_assign_courier' => true,         // ✅ Enabled
'auto_schedule_pickup' => false,       // ⚠️ Disabled (you can enable)
'courier_selection_strategy' => 'cheapest',  // Selects cheapest courier
```

### **Available Courier Selection Strategies:**

| Strategy | Description |
|----------|-------------|
| `cheapest` | Selects the courier with lowest rate (default) |
| `fastest` | Selects courier with shortest delivery time |
| `recommended` | Uses Shiprocket's recommendation |
| `specific` | Always use a specific courier (set `preferred_courier_id`) |

### **Example: Change to Fastest Courier**
```php
'courier_selection_strategy' => 'fastest',
```

### **Example: Always Use Delhivery**
```php
'courier_selection_strategy' => 'specific',
'preferred_courier_id' => 1,  // Delhivery's ID
```

---

## 🎯 **How It Works**

### **Step 1: Payment Verification**
File: `backend/api/customer/razorpay-verify.php`

When Razorpay payment is verified:
```php
// Payment marked as 'paid'
// Order status changed to 'processing'
// 🤖 Automation triggered automatically
```

### **Step 2: Shipment Creation**
Service: `backend/services/ShiprocketAutomation.php`

The automation service:
1. Extracts customer details from order
2. Parses shipping address (pincode, city, state, phone)
3. Calculates package weight based on items
4. Creates shipment in Shiprocket
5. Saves `shiprocket_order_id` and `shiprocket_shipment_id` to database

### **Step 3: Courier Assignment**
1. Fetches available couriers for the delivery pincode
2. Selects courier based on your strategy (cheapest/fastest/etc.)
3. Assigns courier and generates AWB tracking number
4. Saves `courier_id`, `courier_name`, `awb_code` to database

### **Step 4: Pickup Scheduling (Optional)**
If enabled:
1. Schedules pickup with the courier
2. Generates pickup token
3. Saves `pickup_scheduled_date` and `pickup_token_number`

---

## 📋 **What Gets Saved to Database**

After automation completes, your `orders` table will have:

| Field | Example Value | Description |
|-------|---------------|-------------|
| `shiprocket_order_id` | 12345678 | Shiprocket's order ID |
| `shiprocket_shipment_id` | 87654321 | Shiprocket's shipment ID |
| `courier_id` | 1 | Courier company ID |
| `courier_name` | "Delhivery Air" | Courier company name |
| `awb_code` | "ABC123456789" | Tracking number |
| `shipping_charges` | 140.50 | Actual shipping cost |
| `weight` | 0.5 | Package weight in kg |
| `status` | "processing" | Order status |

---

## 🎛️ **Customization Options**

### **1. Weight Calculation**

**Current:** 0.5 kg per item (minimum 0.5 kg)

**Change it:**
```php
'weight_calculation' => 'per_item',  // Options: 'fixed', 'per_item', 'custom'
'weight_per_item' => 0.5,            // kg per item
'minimum_weight' => 0.5,             // minimum package weight
```

**Example: Fixed 1kg for all orders**
```php
'weight_calculation' => 'fixed',
'fixed_weight' => 1.0,
```

### **2. Package Dimensions**

**Current:** 10cm x 10cm x 10cm

**Change it:**
```php
'default_dimensions' => [
    'length' => 15,   // cm
    'breadth' => 15,  // cm
    'height' => 5     // cm
],
```

### **3. Pickup Location**

**Current:** "Purathel"

**Change it:**
```php
'pickup_location' => 'Your Location Name',
```

⚠️ **Important:** Must match exactly with pickup location name in Shiprocket dashboard!

### **4. Enable Auto Pickup Scheduling**

**Current:** Disabled

**Enable it:**
```php
'auto_schedule_pickup' => true,
```

This will automatically schedule pickup after courier assignment.

---

## 📊 **Monitoring & Logs**

### **Log File Location:**
```
backend/logs/shiprocket_automation.log
```

### **What Gets Logged:**
- ✅ Shipment creation success/failure
- ✅ Courier assignment details
- ✅ Pickup scheduling status
- ❌ Any errors during automation

### **Example Log Entry:**
```
2025-01-15 14:30:45 [info] Shipment created for order #123
2025-01-15 14:30:47 [info] Courier assigned for order #123: Delhivery Air
```

### **View Logs:**
```bash
# Windows
type backend\logs\shiprocket_automation.log

# Or open in text editor
notepad backend\logs\shiprocket_automation.log
```

---

## 🧪 **Testing the Automation**

### **Test with Real Order:**

1. **Place a test order** on your website
2. **Complete Razorpay payment**
3. **Check the database:**
   ```sql
   SELECT order_number, shiprocket_order_id, courier_name, awb_code, status 
   FROM orders 
   ORDER BY id DESC 
   LIMIT 1;
   ```
4. **Expected Result:**
   - `shiprocket_order_id`: Should have a value
   - `courier_name`: Should show courier name (e.g., "India Post")
   - `awb_code`: Should have tracking number
   - `status`: Should be "processing"

### **Check Shiprocket Dashboard:**
1. Login to [Shiprocket Dashboard](https://app.shiprocket.in/)
2. Go to **Orders**
3. You should see your order listed
4. Courier should be assigned
5. AWB should be generated

---

## ⚠️ **Important Requirements**

### **For Automation to Work:**

1. ✅ **Valid Shiprocket token** (already configured)
2. ✅ **Pickup location added** in Shiprocket dashboard
3. ✅ **Proper shipping address format:**
   - Must include: Full address, City, State, Pincode (6 digits), Phone (10 digits)
   - Example: "123 Main Street, Kanjirapally, Kerala - 686508, Phone: 9495470077"

### **Address Format Examples:**

✅ **Good:**
```
John Doe
123, MG Road, Near City Mall
Bangalore, Karnataka - 560001
Phone: 9876543210
```

✅ **Good:**
```
Jane Smith, 456 Park Avenue, Mumbai, Maharashtra 400001, 9123456789
```

❌ **Bad (missing pincode):**
```
John Doe, Bangalore, Karnataka
```

❌ **Bad (missing phone):**
```
123 Main Street, Bangalore, Karnataka - 560001
```

---

## 🔧 **Troubleshooting**

### **Issue: Shipment not created automatically**

**Check:**
1. Is `auto_create_shipment` set to `true`?
2. Does the order have a valid shipping address?
3. Check logs: `backend/logs/shiprocket_automation.log`
4. Check PHP error log: `xampp/apache/logs/error.log`

**Solution:**
- Ensure shipping address has pincode, phone, city, and state
- Verify Shiprocket token is valid
- Check if pickup location exists in Shiprocket dashboard

### **Issue: Courier not assigned**

**Check:**
1. Is `auto_assign_courier` set to `true`?
2. Was shipment created successfully?
3. Are couriers available for the delivery pincode?

**Solution:**
- Test courier availability using test UI
- Check if pincode is serviceable
- Try changing courier selection strategy

### **Issue: "No pickup location found"**

**Solution:**
1. Login to Shiprocket dashboard
2. Go to **Settings → Pickup Addresses**
3. Add your warehouse address
4. Use exact same name in `shiprocket_automation.php`

---

## 🎨 **Frontend Integration**

### **Show Automation Status to Customer:**

After payment success, you can show:

```javascript
// After successful payment
fetch('/backend/api/customer/orders.php?order_id=' + orderId)
  .then(res => res.json())
  .then(data => {
    if (data.order.awb_code) {
      // Shipment was created and courier assigned!
      showMessage(`
        ✅ Order confirmed!
        📦 Tracking Number: ${data.order.awb_code}
        🚚 Courier: ${data.order.courier_name}
      `);
    } else {
      // Shipment being processed
      showMessage('✅ Order confirmed! Shipment will be created shortly.');
    }
  });
```

### **Show in Order Details:**

```jsx
function OrderDetails({ order }) {
  return (
    <div>
      <h3>Order #{order.order_number}</h3>
      <p>Status: {order.status}</p>
      
      {order.awb_code && (
        <div className="shipment-info">
          <h4>Shipment Details</h4>
          <p>Courier: {order.courier_name}</p>
          <p>Tracking: {order.awb_code}</p>
          <button onClick={() => trackShipment(order.id)}>
            Track Order
          </button>
        </div>
      )}
    </div>
  );
}
```

---

## 📈 **Performance**

### **Automation Speed:**
- Shipment creation: ~1-2 seconds
- Courier assignment: ~1-2 seconds
- Total: ~2-5 seconds after payment

### **API Calls:**
- 1 call to create shipment
- 1 call to get available couriers
- 1 call to assign courier
- (Optional) 1 call to schedule pickup

### **Caching:**
Courier serviceability is cached for 24 hours to reduce API calls.

---

## 🎯 **Best Practices**

### **1. Test Before Going Live**
- Place test orders with different addresses
- Verify shipments appear in Shiprocket dashboard
- Check AWB generation

### **2. Monitor Logs**
- Check automation logs daily
- Set up alerts for failures
- Review error patterns

### **3. Address Validation**
- Add address validation in checkout
- Ensure pincode and phone are mandatory
- Validate pincode format (6 digits)

### **4. Backup Plan**
- If automation fails, admin can still create shipments manually
- Use admin API endpoints as fallback
- Monitor failed automations

### **5. Customer Communication**
- Send email with tracking number
- Show tracking link in order details
- Provide estimated delivery date

---

## 🔄 **Manual Override**

If you need to disable automation temporarily:

```php
// In shiprocket_automation.php
'auto_create_shipment' => false,  // Disable automation
'auto_assign_courier' => false,
```

Then use admin APIs manually:
1. `POST /api/admin/create-shipment.php`
2. `POST /api/admin/assign-courier.php`
3. `POST /api/admin/schedule-pickup.php`

---

## 📞 **Support**

### **Check Status:**
```
http://localhost/my_little_thingz/backend/shiprocket_test_ui.html
```

### **View Logs:**
```
backend/logs/shiprocket_automation.log
```

### **Test Connection:**
```
http://localhost/my_little_thingz/backend/test_shiprocket.php
```

---

## ✨ **Summary**

✅ **Fully Automatic** - No manual intervention needed  
✅ **Fast** - Completes in 2-5 seconds  
✅ **Reliable** - Error handling and logging  
✅ **Configurable** - Multiple strategies and options  
✅ **Safe** - Payment never fails if shipment fails  
✅ **Monitored** - Complete logging system  

**Your customers will get:**
- ✅ Instant shipment creation
- ✅ Automatic tracking number
- ✅ Courier assignment
- ✅ Real-time tracking

**You get:**
- ✅ Zero manual work
- ✅ Faster order processing
- ✅ Better customer experience
- ✅ Complete automation logs

---

**🎉 Congratulations! Your e-commerce platform now has fully automatic courier service integration!**