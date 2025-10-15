# Shiprocket Frontend Integration Guide

This guide helps frontend developers integrate Shiprocket courier functionality into the UI.

## Overview

The Shiprocket integration provides:
- Real-time shipping cost calculation
- Order tracking for customers
- Admin shipment management dashboard
- Courier selection and AWB generation
- Pickup scheduling and label generation

## Customer Features

### 1. Calculate Shipping Charges (Checkout Page)

Add shipping calculator to your checkout page:

```javascript
// Calculate shipping when user enters pincode
async function calculateShipping(pincode, weight = 0.5) {
  try {
    const response = await fetch('http://localhost/my_little_thingz/backend/api/customer/calculate-shipping.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        pincode: pincode,
        weight: weight,
        cod: 0 // 0 for prepaid, 1 for COD
      })
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      // Display available couriers
      console.log('Available couriers:', data.couriers);
      console.log('Cheapest option:', data.cheapest);
      
      // Update UI with shipping charges
      displayShippingOptions(data.couriers);
      
      // Use cheapest rate for checkout
      updateShippingCost(data.cheapest.rate);
    } else {
      console.error('Error:', data.message);
      alert('Unable to calculate shipping for this pincode');
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Example usage
calculateShipping('686508', 0.5);
```

**UI Component Example:**
```jsx
// React component for shipping calculator
function ShippingCalculator({ cartWeight }) {
  const [pincode, setPincode] = useState('');
  const [couriers, setCouriers] = useState([]);
  const [loading, setLoading] = useState(false);
  
  const handleCalculate = async () => {
    setLoading(true);
    const data = await calculateShipping(pincode, cartWeight);
    if (data.status === 'success') {
      setCouriers(data.couriers);
    }
    setLoading(false);
  };
  
  return (
    <div className="shipping-calculator">
      <h3>Calculate Shipping</h3>
      <input 
        type="text" 
        placeholder="Enter Pincode"
        value={pincode}
        onChange={(e) => setPincode(e.target.value)}
        maxLength={6}
      />
      <button onClick={handleCalculate} disabled={loading}>
        {loading ? 'Calculating...' : 'Calculate'}
      </button>
      
      {couriers.length > 0 && (
        <div className="courier-options">
          <h4>Available Shipping Options:</h4>
          {couriers.map((courier, index) => (
            <div key={index} className="courier-option">
              <span>{courier.name}</span>
              <span>₹{courier.rate}</span>
              <span>{courier.estimated_delivery_days} days</span>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

### 2. Track Order (Order Details Page)

Add order tracking to customer order details page:

```javascript
// Track order shipment
async function trackOrder(orderId, userId) {
  try {
    const response = await fetch(
      `http://localhost/my_little_thingz/backend/api/customer/track-shipment.php?order_id=${orderId}`,
      {
        headers: {
          'X-User-ID': userId
        }
      }
    );
    
    const data = await response.json();
    
    if (data.status === 'success') {
      if (data.tracking_available) {
        // Display tracking information
        displayTrackingInfo(data);
      } else {
        // Show message that shipment is not yet created
        showMessage('Your order is being processed. Tracking will be available soon.');
      }
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

function displayTrackingInfo(data) {
  const order = data.order;
  const tracking = data.tracking_data;
  
  console.log('Order Number:', order.order_number);
  console.log('AWB Code:', order.awb_code);
  console.log('Courier:', order.courier_name);
  console.log('Status:', order.status);
  
  if (tracking && tracking.shipment_track) {
    console.log('Tracking History:');
    tracking.shipment_track.forEach(track => {
      console.log(`${track.date} - ${track.status} at ${track.location}`);
    });
  }
}
```

**UI Component Example:**
```jsx
// React component for order tracking
function OrderTracking({ orderId, userId }) {
  const [trackingData, setTrackingData] = useState(null);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    loadTrackingData();
  }, [orderId]);
  
  const loadTrackingData = async () => {
    const response = await fetch(
      `http://localhost/my_little_thingz/backend/api/customer/track-shipment.php?order_id=${orderId}`,
      { headers: { 'X-User-ID': userId } }
    );
    const data = await response.json();
    setTrackingData(data);
    setLoading(false);
  };
  
  if (loading) return <div>Loading tracking information...</div>;
  
  if (!trackingData.tracking_available) {
    return (
      <div className="tracking-not-available">
        <p>Your order is being processed.</p>
        <p>Tracking information will be available once the shipment is created.</p>
      </div>
    );
  }
  
  const { order, tracking_data } = trackingData;
  
  return (
    <div className="order-tracking">
      <h3>Track Your Order</h3>
      
      <div className="tracking-summary">
        <div className="info-row">
          <span>Order Number:</span>
          <strong>{order.order_number}</strong>
        </div>
        <div className="info-row">
          <span>AWB Code:</span>
          <strong>{order.awb_code}</strong>
        </div>
        <div className="info-row">
          <span>Courier:</span>
          <strong>{order.courier_name}</strong>
        </div>
        <div className="info-row">
          <span>Status:</span>
          <strong className={`status-${order.status}`}>
            {order.status.toUpperCase()}
          </strong>
        </div>
      </div>
      
      {tracking_data && tracking_data.shipment_track && (
        <div className="tracking-timeline">
          <h4>Tracking History</h4>
          {tracking_data.shipment_track.map((track, index) => (
            <div key={index} className="timeline-item">
              <div className="timeline-date">{track.date}</div>
              <div className="timeline-status">{track.status}</div>
              <div className="timeline-location">{track.location}</div>
              {track.remarks && (
                <div className="timeline-remarks">{track.remarks}</div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

## Admin Features

### 1. Shipment Management Dashboard

Create an admin dashboard to manage all shipments:

```javascript
// Get all orders with shipment details
async function getShipments(page = 1, status = null, search = null) {
  try {
    let url = `http://localhost/my_little_thingz/backend/api/admin/shipments.php?page=${page}&per_page=20`;
    
    if (status) url += `&status=${status}`;
    if (search) url += `&search=${encodeURIComponent(search)}`;
    
    const response = await fetch(url, {
      headers: {
        'X-User-ID': adminUserId // Your admin user ID
      }
    });
    
    const data = await response.json();
    
    if (data.status === 'success') {
      displayShipments(data.data.orders);
      displayStatistics(data.data.statistics);
      updatePagination(data.data.pagination);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

**UI Component Example:**
```jsx
// React component for shipment dashboard
function ShipmentDashboard() {
  const [shipments, setShipments] = useState([]);
  const [stats, setStats] = useState(null);
  const [page, setPage] = useState(1);
  const [filter, setFilter] = useState('all');
  
  useEffect(() => {
    loadShipments();
  }, [page, filter]);
  
  const loadShipments = async () => {
    const response = await fetch(
      `http://localhost/my_little_thingz/backend/api/admin/shipments.php?page=${page}`,
      { headers: { 'X-User-ID': adminUserId } }
    );
    const data = await response.json();
    setShipments(data.data.orders);
    setStats(data.data.statistics);
  };
  
  return (
    <div className="shipment-dashboard">
      <h2>Shipment Management</h2>
      
      {/* Statistics Cards */}
      {stats && (
        <div className="stats-grid">
          <div className="stat-card">
            <h3>{stats.total_orders}</h3>
            <p>Total Orders</p>
          </div>
          <div className="stat-card">
            <h3>{stats.shipments_created}</h3>
            <p>Shipments Created</p>
          </div>
          <div className="stat-card">
            <h3>{stats.pickup_scheduled}</h3>
            <p>Pickup Scheduled</p>
          </div>
          <div className="stat-card">
            <h3>{stats.shipped}</h3>
            <p>Shipped</p>
          </div>
        </div>
      )}
      
      {/* Shipments Table */}
      <table className="shipments-table">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Status</th>
            <th>AWB Code</th>
            <th>Courier</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {shipments.map(shipment => (
            <tr key={shipment.id}>
              <td>{shipment.order_number}</td>
              <td>{shipment.customer_name}</td>
              <td>
                <span className={`badge badge-${shipment.shipment_status}`}>
                  {shipment.shipment_status}
                </span>
              </td>
              <td>{shipment.awb_code || '-'}</td>
              <td>{shipment.courier_name || '-'}</td>
              <td>
                <ShipmentActions shipment={shipment} onUpdate={loadShipments} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

### 2. Create Shipment

```javascript
// Create shipment for an order
async function createShipment(orderId, dimensions = {}) {
  try {
    const response = await fetch(
      'http://localhost/my_little_thingz/backend/api/admin/create-shipment.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': adminUserId
        },
        body: JSON.stringify({
          order_id: orderId,
          weight: dimensions.weight || 0.5,
          length: dimensions.length || 10,
          breadth: dimensions.breadth || 10,
          height: dimensions.height || 10
        })
      }
    );
    
    const data = await response.json();
    
    if (data.status === 'success') {
      alert('Shipment created successfully!');
      console.log('Shiprocket Order ID:', data.data.shiprocket_order_id);
      console.log('Shipment ID:', data.data.shiprocket_shipment_id);
      
      // Proceed to courier selection
      selectCourier(orderId);
    } else {
      alert('Error: ' + data.message);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### 3. Select Courier and Assign AWB

```javascript
// Get available couriers
async function getAvailableCouriers(orderId) {
  try {
    const response = await fetch(
      `http://localhost/my_little_thingz/backend/api/admin/assign-courier.php?order_id=${orderId}`,
      {
        headers: {
          'X-User-ID': adminUserId
        }
      }
    );
    
    const data = await response.json();
    
    if (data.status === 'success') {
      return data.couriers;
    }
  } catch (error) {
    console.error('Error:', error);
  }
}

// Assign selected courier
async function assignCourier(orderId, courierId) {
  try {
    const response = await fetch(
      'http://localhost/my_little_thingz/backend/api/admin/assign-courier.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': adminUserId
        },
        body: JSON.stringify({
          order_id: orderId,
          courier_id: courierId
        })
      }
    );
    
    const data = await response.json();
    
    if (data.status === 'success') {
      alert('AWB assigned successfully!');
      console.log('AWB Code:', data.data.awb_code);
      console.log('Courier:', data.data.courier_name);
      
      // Proceed to schedule pickup
      schedulePickup(orderId);
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### 4. Schedule Pickup

```javascript
// Schedule pickup for shipment
async function schedulePickup(orderId) {
  try {
    const response = await fetch(
      'http://localhost/my_little_thingz/backend/api/admin/schedule-pickup.php',
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-User-ID': adminUserId
        },
        body: JSON.stringify({
          order_id: orderId
        })
      }
    );
    
    const data = await response.json();
    
    if (data.status === 'success') {
      alert('Pickup scheduled successfully!');
      console.log('Pickup Date:', data.data.pickup_scheduled_date);
      console.log('Token:', data.data.pickup_token_number);
      console.log('Label URL:', data.data.label_url);
      
      // Download shipping label
      if (data.data.label_url) {
        window.open(data.data.label_url, '_blank');
      }
    }
  } catch (error) {
    console.error('Error:', error);
  }
}
```

### 5. Complete Workflow Component

```jsx
// Complete shipment creation workflow
function ShipmentWorkflow({ order }) {
  const [step, setStep] = useState(1);
  const [couriers, setCouriers] = useState([]);
  const [selectedCourier, setSelectedCourier] = useState(null);
  
  const handleCreateShipment = async () => {
    const result = await createShipment(order.id);
    if (result.status === 'success') {
      setStep(2);
      loadCouriers();
    }
  };
  
  const loadCouriers = async () => {
    const courierList = await getAvailableCouriers(order.id);
    setCouriers(courierList);
  };
  
  const handleAssignCourier = async () => {
    const result = await assignCourier(order.id, selectedCourier);
    if (result.status === 'success') {
      setStep(3);
    }
  };
  
  const handleSchedulePickup = async () => {
    const result = await schedulePickup(order.id);
    if (result.status === 'success') {
      setStep(4);
    }
  };
  
  return (
    <div className="shipment-workflow">
      <div className="steps">
        <div className={`step ${step >= 1 ? 'active' : ''}`}>1. Create Shipment</div>
        <div className={`step ${step >= 2 ? 'active' : ''}`}>2. Select Courier</div>
        <div className={`step ${step >= 3 ? 'active' : ''}`}>3. Schedule Pickup</div>
        <div className={`step ${step >= 4 ? 'active' : ''}`}>4. Complete</div>
      </div>
      
      {step === 1 && (
        <div className="step-content">
          <h3>Create Shipment</h3>
          <p>Order: {order.order_number}</p>
          <button onClick={handleCreateShipment}>Create Shipment</button>
        </div>
      )}
      
      {step === 2 && (
        <div className="step-content">
          <h3>Select Courier</h3>
          <div className="courier-list">
            {couriers.map(courier => (
              <div 
                key={courier.id}
                className={`courier-card ${selectedCourier === courier.id ? 'selected' : ''}`}
                onClick={() => setSelectedCourier(courier.id)}
              >
                <h4>{courier.courier_name}</h4>
                <p>Rate: ₹{courier.rate}</p>
                <p>Delivery: {courier.estimated_delivery_days} days</p>
              </div>
            ))}
          </div>
          <button 
            onClick={handleAssignCourier}
            disabled={!selectedCourier}
          >
            Assign Courier
          </button>
        </div>
      )}
      
      {step === 3 && (
        <div className="step-content">
          <h3>Schedule Pickup</h3>
          <button onClick={handleSchedulePickup}>Schedule Pickup</button>
        </div>
      )}
      
      {step === 4 && (
        <div className="step-content">
          <h3>Shipment Created Successfully!</h3>
          <p>✓ Shipment created</p>
          <p>✓ Courier assigned</p>
          <p>✓ Pickup scheduled</p>
        </div>
      )}
    </div>
  );
}
```

## CSS Styling Examples

```css
/* Shipping Calculator */
.shipping-calculator {
  padding: 20px;
  border: 1px solid #ddd;
  border-radius: 8px;
  margin: 20px 0;
}

.courier-option {
  display: flex;
  justify-content: space-between;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

/* Order Tracking */
.tracking-timeline {
  margin-top: 20px;
}

.timeline-item {
  padding: 15px;
  border-left: 3px solid #007bff;
  margin-left: 10px;
  margin-bottom: 15px;
}

.timeline-date {
  font-size: 12px;
  color: #666;
}

.timeline-status {
  font-weight: bold;
  margin: 5px 0;
}

/* Shipment Dashboard */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  text-align: center;
}

.stat-card h3 {
  font-size: 32px;
  margin: 0;
  color: #007bff;
}

/* Status Badges */
.badge {
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.badge-not_created { background: #ffc107; color: #000; }
.badge-created { background: #17a2b8; color: #fff; }
.badge-awb_assigned { background: #007bff; color: #fff; }
.badge-pickup_scheduled { background: #28a745; color: #fff; }
.badge-shipped { background: #6c757d; color: #fff; }
.badge-delivered { background: #28a745; color: #fff; }
```

## Testing

Test the integration in your browser console:

```javascript
// Test shipping calculation
calculateShipping('686508', 0.5);

// Test order tracking
trackOrder(2, 1);

// Test admin functions (requires admin user ID)
getShipments(1);
createShipment(2);
```

## Notes

- All API endpoints require proper authentication (X-User-ID header)
- Admin endpoints require admin role verification
- Customer endpoints verify order ownership
- Error handling should be implemented for all API calls
- Loading states should be shown during API requests

For complete API documentation, see `SHIPROCKET_SETUP.md`