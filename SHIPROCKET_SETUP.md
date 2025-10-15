# Shiprocket Courier Service Integration

This document provides a complete guide to the Shiprocket courier service integration in My Little Thingz.

## Table of Contents
1. [Overview](#overview)
2. [Database Setup](#database-setup)
3. [Configuration](#configuration)
4. [API Endpoints](#api-endpoints)
5. [Workflow](#workflow)
6. [Testing](#testing)
7. [Troubleshooting](#troubleshooting)

## Overview

The Shiprocket integration provides complete courier service functionality including:
- Creating shipments for orders
- Getting available courier services
- Assigning AWB (Air Waybill) numbers
- Scheduling pickups
- Generating shipping labels and manifests
- Real-time shipment tracking
- Calculating shipping charges

## Database Setup

### 1. Run the Migration

Execute the migration file to add Shiprocket fields to your database:

```bash
mysql -u root -p my_little_thingz < backend/database/migrations_shiprocket.sql
```

Or run it through phpMyAdmin by importing the file.

### 2. New Database Fields

The migration adds the following fields to the `orders` table:
- `shiprocket_order_id` - Shiprocket order ID
- `shiprocket_shipment_id` - Shiprocket shipment ID
- `courier_id` - Courier company ID
- `courier_name` - Courier company name
- `awb_code` - AWB tracking code
- `pickup_scheduled_date` - Scheduled pickup date
- `pickup_token_number` - Pickup token number
- `label_url` - Shipping label URL
- `manifest_url` - Manifest URL
- `shipping_charges` - Actual shipping charges
- `weight` - Package weight in kg
- `length`, `breadth`, `height` - Package dimensions in cm

### 3. New Tables

- `courier_serviceability_cache` - Caches courier availability data
- `shipment_tracking_history` - Stores tracking history for orders

## Configuration

Your Shiprocket credentials are already configured in:
```
backend/config/shiprocket.php
```

The configuration includes:
- Company ID: 7748131
- Email: fathima686231@gmail.com
- Token: (Pre-issued JWT token)
- Base URL: https://apiv2.shiprocket.in/v1/external

## API Endpoints

### Admin Endpoints

#### 1. Create Shipment
**Endpoint:** `POST /backend/api/admin/create-shipment.php`

Creates a shipment in Shiprocket for an order.

**Request:**
```json
{
  "order_id": 123,
  "weight": 0.5,
  "length": 10,
  "breadth": 10,
  "height": 10
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Shipment created successfully",
  "data": {
    "shiprocket_order_id": 456789,
    "shiprocket_shipment_id": 789012,
    "order_number": "ORD-20250125-123456"
  }
}
```

#### 2. Get Available Couriers
**Endpoint:** `GET /backend/api/admin/assign-courier.php?order_id=123`

Gets available courier services for an order.

**Response:**
```json
{
  "status": "success",
  "couriers": [
    {
      "id": 12,
      "courier_name": "Delhivery",
      "rate": 45.50,
      "estimated_delivery_days": "3-5",
      "rating": 4.5
    }
  ],
  "order_id": 123,
  "shipment_id": 789012
}
```

#### 3. Assign Courier & Generate AWB
**Endpoint:** `POST /backend/api/admin/assign-courier.php`

Assigns a courier and generates AWB for the shipment.

**Request:**
```json
{
  "order_id": 123,
  "courier_id": 12
}
```

**Response:**
```json
{
  "status": "success",
  "message": "AWB assigned successfully",
  "data": {
    "awb_code": "1234567890",
    "courier_name": "Delhivery",
    "courier_id": 12
  }
}
```

#### 4. Schedule Pickup
**Endpoint:** `POST /backend/api/admin/schedule-pickup.php`

Schedules pickup and generates shipping label.

**Request:**
```json
{
  "order_id": 123
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Pickup scheduled successfully",
  "data": {
    "pickup_scheduled_date": "2025-01-26 10:00:00",
    "pickup_token_number": "PKP123456",
    "label_url": "https://shiprocket.co/label/123456.pdf",
    "awb_code": "1234567890"
  }
}
```

#### 5. Generate Manifest
**Endpoint:** `POST /backend/api/admin/generate-manifest.php`

Generates manifest for multiple shipments.

**Request:**
```json
{
  "order_ids": [123, 124, 125]
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Manifest generated successfully",
  "data": {
    "manifest_url": "https://shiprocket.co/manifest/123456.pdf",
    "order_count": 3,
    "orders": [...]
  }
}
```

#### 6. View All Shipments
**Endpoint:** `GET /backend/api/admin/shipments.php`

Gets all orders with shipment details.

**Query Parameters:**
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 20)
- `status` - Filter by order status
- `search` - Search by order number, AWB, customer name/email

**Response:**
```json
{
  "status": "success",
  "data": {
    "orders": [...],
    "pagination": {
      "page": 1,
      "per_page": 20,
      "total": 100,
      "total_pages": 5
    },
    "statistics": {
      "total_orders": 100,
      "shipments_created": 80,
      "awb_assigned": 75,
      "pickup_scheduled": 70,
      "shipped": 60,
      "delivered": 50
    }
  }
}
```

### Customer Endpoints

#### 1. Track Shipment
**Endpoint:** `GET /backend/api/customer/track-shipment.php?order_id=123`

Tracks shipment for a customer order.

**Headers:**
- `X-User-ID: {user_id}`

**Response:**
```json
{
  "status": "success",
  "order": {
    "order_number": "ORD-20250125-123456",
    "status": "shipped",
    "awb_code": "1234567890",
    "courier_name": "Delhivery",
    "pickup_scheduled_date": "2025-01-26 10:00:00",
    "estimated_delivery": "2025-01-30"
  },
  "tracking_available": true,
  "tracking_data": {
    "current_status": "In Transit",
    "shipment_track": [
      {
        "date": "2025-01-26 10:30:00",
        "status": "Picked Up",
        "location": "Kanjirapally Hub"
      }
    ]
  },
  "tracking_history": [...]
}
```

#### 2. Calculate Shipping
**Endpoint:** `POST /backend/api/customer/calculate-shipping.php`

Calculates shipping charges for a pincode.

**Request:**
```json
{
  "pincode": "686508",
  "weight": 0.5,
  "cod": 0
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Shipping rates retrieved",
  "couriers": [
    {
      "id": 12,
      "name": "Delhivery",
      "rate": 45.50,
      "estimated_delivery_days": "3-5",
      "etd": "2025-01-30",
      "rating": 4.5
    }
  ],
  "cheapest": {
    "id": 12,
    "name": "Delhivery",
    "rate": 45.50
  }
}
```

## Workflow

### Complete Order Fulfillment Flow

1. **Customer Places Order**
   - Order is created with status `pending`
   - Payment is processed via Razorpay
   - Order status changes to `processing` after payment

2. **Admin Creates Shipment**
   - Call `create-shipment.php` with order ID
   - Shiprocket order and shipment are created
   - Order is updated with `shiprocket_order_id` and `shiprocket_shipment_id`

3. **Admin Selects Courier**
   - Call `assign-courier.php` (GET) to see available couriers
   - Admin selects best courier based on rate and delivery time
   - Call `assign-courier.php` (POST) to assign courier
   - AWB is generated and stored

4. **Admin Schedules Pickup**
   - Call `schedule-pickup.php` with order ID
   - Pickup is scheduled with courier
   - Shipping label is generated
   - Order status remains `processing`

5. **Generate Manifest (Optional)**
   - Call `generate-manifest.php` with multiple order IDs
   - Manifest is generated for batch pickup
   - Manifest URL is stored for all orders

6. **Courier Picks Up Package**
   - Courier picks up package on scheduled date
   - Admin can manually update order status to `shipped`

7. **Customer Tracks Order**
   - Customer calls `track-shipment.php` with order ID
   - Real-time tracking information is displayed
   - Tracking history is stored in database

8. **Order Delivered**
   - Admin updates order status to `delivered`
   - Customer receives delivery confirmation

## Testing

### Test the Integration

1. **Create a Test Order:**
```bash
# Use the existing checkout flow to create an order
# Or use the admin panel to create a test order
```

2. **Create Shipment:**
```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/create-shipment.php \
  -H "Content-Type: application/json" \
  -H "X-User-ID: 5" \
  -d '{"order_id": 2, "weight": 0.5, "length": 10, "breadth": 10, "height": 10}'
```

3. **Get Available Couriers:**
```bash
curl -X GET "http://localhost/my_little_thingz/backend/api/admin/assign-courier.php?order_id=2" \
  -H "X-User-ID: 5"
```

4. **Assign Courier:**
```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/assign-courier.php \
  -H "Content-Type: application/json" \
  -H "X-User-ID: 5" \
  -d '{"order_id": 2, "courier_id": 12}'
```

5. **Schedule Pickup:**
```bash
curl -X POST http://localhost/my_little_thingz/backend/api/admin/schedule-pickup.php \
  -H "Content-Type: application/json" \
  -H "X-User-ID: 5" \
  -d '{"order_id": 2}'
```

6. **Track Shipment:**
```bash
curl -X GET "http://localhost/my_little_thingz/backend/api/customer/track-shipment.php?order_id=2" \
  -H "X-User-ID: 1"
```

## Troubleshooting

### Common Issues

1. **Authentication Failed**
   - Check if token is valid in `config/shiprocket.php`
   - Token expires after a certain period
   - Generate new token from Shiprocket dashboard if needed

2. **No Couriers Available**
   - Check if pickup and delivery pincodes are valid
   - Verify warehouse address in `config/warehouse.php`
   - Check if weight and dimensions are reasonable

3. **AWB Generation Failed**
   - Ensure shipment is created first
   - Check if courier ID is valid
   - Verify order details are complete

4. **Pickup Scheduling Failed**
   - Ensure AWB is assigned first
   - Check if pickup location is configured in Shiprocket
   - Verify pickup address matches warehouse address

5. **Tracking Not Available**
   - AWB must be assigned first
   - Tracking data may take time to appear after pickup
   - Check if AWB code is correct

### Debug Mode

Enable error reporting in PHP files for debugging:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Shiprocket API Response

All API endpoints return the raw Shiprocket response in case of errors:
```json
{
  "status": "error",
  "message": "Failed to create shipment",
  "response": {
    // Raw Shiprocket API response
  }
}
```

## Additional Features

### Shiprocket Model Methods

The `Shiprocket` class provides additional methods:

- `getOrders($filters)` - Get all orders from Shiprocket
- `getShipment($shipmentId)` - Get shipment details
- `trackShipment($shipmentId)` - Track by shipment ID
- `getPickupLocations()` - Get configured pickup locations
- `updateOrderStatus($orderId, $status)` - Update order status
- `cancelOrder($orderId)` - Cancel an order

### Caching

Courier serviceability data is cached for 24 hours to improve performance and reduce API calls.

### Tracking History

All tracking updates are stored in `shipment_tracking_history` table for historical reference.

## Support

For Shiprocket API documentation, visit:
https://apidocs.shiprocket.in/

For issues with this integration, check the error logs or contact your development team.