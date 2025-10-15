<?php
/**
 * Shiprocket Automation Service
 * Handles automatic shipment creation, courier assignment, and pickup scheduling
 */

require_once __DIR__ . '/../models/Shiprocket.php';
require_once __DIR__ . '/../config/database.php';

class ShiprocketAutomation {
    private $db;
    private $shiprocket;
    private $config;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->shiprocket = new Shiprocket();
        $this->config = require __DIR__ . '/../config/shiprocket_automation.php';
    }
    
    /**
     * Main automation function - called after successful payment
     * @param int $orderId Local order ID
     * @param int $userId User ID
     * @return array Result with status and details
     */
    public function processOrder($orderId, $userId) {
        $result = [
            'status' => 'success',
            'shipment_created' => false,
            'courier_assigned' => false,
            'pickup_scheduled' => false,
            'errors' => []
        ];
        
        try {
            // Step 1: Create Shipment
            if ($this->config['auto_create_shipment']) {
                $shipmentResult = $this->createShipment($orderId, $userId);
                if ($shipmentResult['status'] === 'success') {
                    $result['shipment_created'] = true;
                    $result['shiprocket_order_id'] = $shipmentResult['shiprocket_order_id'];
                    $result['shiprocket_shipment_id'] = $shipmentResult['shiprocket_shipment_id'];
                    
                    $this->log("Shipment created for order #$orderId");
                } else {
                    $result['errors'][] = 'Shipment creation failed: ' . $shipmentResult['message'];
                    $this->log("Shipment creation failed for order #$orderId: " . $shipmentResult['message'], 'error');
                    return $result;
                }
            }
            
            // Step 2: Assign Courier
            if ($this->config['auto_assign_courier'] && $result['shipment_created']) {
                $courierResult = $this->assignCourier($orderId);
                if ($courierResult['status'] === 'success') {
                    $result['courier_assigned'] = true;
                    $result['awb_code'] = $courierResult['awb_code'];
                    $result['courier_name'] = $courierResult['courier_name'];
                    
                    $this->log("Courier assigned for order #$orderId: " . $courierResult['courier_name']);
                } else {
                    $result['errors'][] = 'Courier assignment failed: ' . $courierResult['message'];
                    $this->log("Courier assignment failed for order #$orderId: " . $courierResult['message'], 'error');
                }
            }
            
            // Step 3: Schedule Pickup
            if ($this->config['auto_schedule_pickup'] && $result['courier_assigned']) {
                $pickupResult = $this->schedulePickup($orderId);
                if ($pickupResult['status'] === 'success') {
                    $result['pickup_scheduled'] = true;
                    $result['pickup_date'] = $pickupResult['pickup_date'];
                    
                    $this->log("Pickup scheduled for order #$orderId");
                } else {
                    $result['errors'][] = 'Pickup scheduling failed: ' . $pickupResult['message'];
                    $this->log("Pickup scheduling failed for order #$orderId: " . $pickupResult['message'], 'error');
                }
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['errors'][] = $e->getMessage();
            $this->log("Automation failed for order #$orderId: " . $e->getMessage(), 'error');
        }
        
        return $result;
    }
    
    /**
     * Create shipment in Shiprocket
     */
    private function createShipment($orderId, $userId) {
        try {
            // Get order details
            $stmt = $this->db->prepare("SELECT o.*, u.first_name, u.last_name, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND o.user_id = ?");
            $stmt->execute([$orderId, $userId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['status' => 'error', 'message' => 'Order not found'];
            }
            
            // Parse shipping address
            $addressData = $this->parseAddress($order['shipping_address']);
            if (!$addressData['valid']) {
                return ['status' => 'error', 'message' => 'Invalid shipping address format'];
            }
            
            // Get order items
            $itemsStmt = $this->db->prepare("SELECT oi.*, a.title 
                FROM order_items oi 
                JOIN artworks a ON oi.artwork_id = a.id 
                WHERE oi.order_id = ?");
            $itemsStmt->execute([$orderId]);
            $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate weight
            $weight = $this->calculateWeight($orderItems);
            
            // Calculate shipping charges (₹60 per kg minimum)
            $shippingCharges = $this->calculateShippingCharges($weight);
            
            // Prepare customer name
            $customerName = trim($order['first_name'] . ' ' . $order['last_name']);
            if (!$customerName) $customerName = 'Customer';
            
            // Prepare Shiprocket order data
            $shiprocketData = [
                'order_id' => $order['order_number'],
                'order_date' => date('Y-m-d H:i', strtotime($order['created_at'])),
                'pickup_location' => $this->config['pickup_location'],
                'billing_customer_name' => $customerName,
                'billing_last_name' => '',
                'billing_address' => $addressData['address'],
                'billing_city' => $addressData['city'],
                'billing_pincode' => $addressData['pincode'],
                'billing_state' => $addressData['state'],
                'billing_country' => 'India',
                'billing_email' => $order['email'],
                'billing_phone' => $addressData['phone'],
                'shipping_is_billing' => true,
                'order_items' => [],
                'payment_method' => 'Prepaid',
                'sub_total' => $order['subtotal'] ?? $order['total_amount'],
                'length' => $this->config['default_dimensions']['length'],
                'breadth' => $this->config['default_dimensions']['breadth'],
                'height' => $this->config['default_dimensions']['height'],
                'weight' => $weight
            ];
            
            // Add order items
            foreach ($orderItems as $item) {
                $shiprocketData['order_items'][] = [
                    'name' => $item['title'] ?? 'Artwork',
                    'sku' => 'ART-' . $item['artwork_id'],
                    'units' => $item['quantity'],
                    'selling_price' => $item['price'],
                    'discount' => 0,
                    'tax' => 0,
                    'hsn' => 442110
                ];
            }
            
            // Create order in Shiprocket
            $response = $this->shiprocket->createOrder($shiprocketData);
            
            if (isset($response['order_id']) && isset($response['shipment_id'])) {
                // Update local database with shipping charges
                $updateStmt = $this->db->prepare("UPDATE orders 
                    SET shiprocket_order_id = ?, 
                        shiprocket_shipment_id = ?,
                        weight = ?,
                        length = ?,
                        breadth = ?,
                        height = ?,
                        shipping_charges = ?
                    WHERE id = ?");
                $updateStmt->execute([
                    $response['order_id'],
                    $response['shipment_id'],
                    $weight,
                    $this->config['default_dimensions']['length'],
                    $this->config['default_dimensions']['breadth'],
                    $this->config['default_dimensions']['height'],
                    $shippingCharges,
                    $orderId
                ]);
                
                return [
                    'status' => 'success',
                    'shiprocket_order_id' => $response['order_id'],
                    'shiprocket_shipment_id' => $response['shipment_id']
                ];
            } else {
                return ['status' => 'error', 'message' => 'Invalid response from Shiprocket'];
            }
            
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Assign courier based on configured strategy
     */
    private function assignCourier($orderId) {
        try {
            // Get order details
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order || !$order['shiprocket_shipment_id']) {
                $this->log("Courier assignment failed: Order or shipment not found for order #$orderId", 'error');
                return ['status' => 'error', 'message' => 'Order or shipment not found'];
            }
            
            // Extract delivery pincode
            $deliveryPincode = $this->extractPincode($order['shipping_address']);
            if (!$deliveryPincode) {
                $this->log("Courier assignment failed: Could not extract pincode from address for order #$orderId", 'error');
                return ['status' => 'error', 'message' => 'Invalid delivery pincode'];
            }
            
            // Calculate weight
            $weight = $order['weight'] ?? 0.5;
            if ($weight < 0.5) $weight = 0.5; // Minimum weight
            
            $this->log("Fetching courier serviceability for order #$orderId (Pickup: 686508, Delivery: $deliveryPincode, Weight: {$weight}kg)");
            
            // Get available couriers
            $couriers = $this->shiprocket->getCourierServiceability([
                'pickup_postcode' => '686508', // Your warehouse pincode
                'delivery_postcode' => $deliveryPincode,
                'weight' => $weight,
                'cod' => 0 // Prepaid
            ]);
            
            // Log the response for debugging
            $this->log("Courier serviceability response: " . json_encode($couriers));
            
            if (!isset($couriers['data']['available_courier_companies']) || 
                empty($couriers['data']['available_courier_companies'])) {
                $errorMsg = isset($couriers['message']) ? $couriers['message'] : 'No couriers available';
                $this->log("No couriers available for order #$orderId: $errorMsg", 'error');
                return ['status' => 'error', 'message' => "No couriers available: $errorMsg"];
            }
            
            $availableCount = count($couriers['data']['available_courier_companies']);
            $this->log("Found $availableCount available couriers for order #$orderId");
            
            // Select courier based on strategy
            $selectedCourier = $this->selectCourier(
                $couriers['data']['available_courier_companies'],
                $this->config['courier_selection_strategy']
            );
            
            if (!$selectedCourier) {
                $this->log("Could not select courier for order #$orderId", 'error');
                return ['status' => 'error', 'message' => 'Could not select courier'];
            }
            
            $courierName = $selectedCourier['courier_name'] ?? 'Unknown';
            $courierId = $selectedCourier['courier_company_id'] ?? null;
            $rate = $selectedCourier['rate'] ?? 0;
            
            $this->log("Selected courier for order #$orderId: $courierName (ID: $courierId, Rate: ₹$rate)");
            
            // Assign courier and generate AWB
            $this->log("Assigning courier and generating AWB for order #$orderId...");
            $assignResponse = $this->shiprocket->assignCourier(
                $order['shiprocket_shipment_id'],
                $courierId
            );
            
            // Log the assignment response
            $this->log("AWB assignment response: " . json_encode($assignResponse));
            
            if (isset($assignResponse['awb_assign_status']) && 
                $assignResponse['awb_assign_status'] == 1) {
                
                $awbCode = $assignResponse['response']['data']['awb_code'] ?? null;
                
                if (!$awbCode) {
                    $this->log("AWB code not found in response for order #$orderId", 'error');
                    return ['status' => 'error', 'message' => 'AWB code not generated'];
                }
                
                $this->log("AWB code generated for order #$orderId: $awbCode");
                
                // Update database with courier info and mark as shipped
                $updateStmt = $this->db->prepare("UPDATE orders 
                    SET courier_id = ?,
                        courier_name = ?,
                        awb_code = ?,
                        shipping_charges = ?,
                        status = 'shipped',
                        shipment_status = 'PICKUP_SCHEDULED',
                        current_status = 'Shipment is ready for pickup',
                        shipped_at = NOW()
                    WHERE id = ?");
                $updateStmt->execute([
                    $courierId,
                    $courierName,
                    $awbCode,
                    $rate,
                    $orderId
                ]);
                
                $this->log("Database updated successfully for order #$orderId with AWB: $awbCode, Status: shipped");
                
                return [
                    'status' => 'success',
                    'courier_name' => $courierName,
                    'awb_code' => $awbCode,
                    'rate' => $rate
                ];
            } else {
                $errorMsg = isset($assignResponse['message']) ? $assignResponse['message'] : 'AWB assignment failed';
                $this->log("AWB assignment failed for order #$orderId: $errorMsg", 'error');
                return ['status' => 'error', 'message' => "AWB assignment failed: $errorMsg"];
            }
            
        } catch (Exception $e) {
            $this->log("Exception in courier assignment for order #$orderId: " . $e->getMessage(), 'error');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Schedule pickup
     */
    private function schedulePickup($orderId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order || !$order['shiprocket_shipment_id']) {
                return ['status' => 'error', 'message' => 'Order or shipment not found'];
            }
            
            $pickupResponse = $this->shiprocket->schedulePickup($order['shiprocket_shipment_id']);
            
            if (isset($pickupResponse['pickup_status']) && $pickupResponse['pickup_status'] == 1) {
                $pickupDate = $pickupResponse['response']['pickup_scheduled_date'] ?? null;
                $pickupToken = $pickupResponse['response']['pickup_token_number'] ?? null;
                
                $updateStmt = $this->db->prepare("UPDATE orders 
                    SET pickup_scheduled_date = ?,
                        pickup_token_number = ?
                    WHERE id = ?");
                $updateStmt->execute([$pickupDate, $pickupToken, $orderId]);
                
                return [
                    'status' => 'success',
                    'pickup_date' => $pickupDate,
                    'pickup_token' => $pickupToken
                ];
            } else {
                return ['status' => 'error', 'message' => 'Pickup scheduling failed'];
            }
            
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Select courier based on strategy
     */
    private function selectCourier($couriers, $strategy) {
        if (empty($couriers)) return null;
        
        switch ($strategy) {
            case 'cheapest':
                usort($couriers, function($a, $b) {
                    return $a['rate'] <=> $b['rate'];
                });
                return $couriers[0];
                
            case 'fastest':
                usort($couriers, function($a, $b) {
                    return $a['estimated_delivery_days'] <=> $b['estimated_delivery_days'];
                });
                return $couriers[0];
                
            case 'recommended':
                // Use Shiprocket's recommendation (usually first in list)
                return $couriers[0];
                
            case 'balanced':
                // Balance between cost and speed
                // Calculate a score: lower is better
                usort($couriers, function($a, $b) {
                    // Normalize rate (assume max rate is 200)
                    $rateA = ($a['rate'] ?? 100) / 200;
                    $rateB = ($b['rate'] ?? 100) / 200;
                    
                    // Normalize delivery days (assume max is 7 days)
                    $daysA = ($a['estimated_delivery_days'] ?? 3) / 7;
                    $daysB = ($b['estimated_delivery_days'] ?? 3) / 7;
                    
                    // Weighted score: 60% cost, 40% speed
                    $scoreA = (0.6 * $rateA) + (0.4 * $daysA);
                    $scoreB = (0.6 * $rateB) + (0.4 * $daysB);
                    
                    return $scoreA <=> $scoreB;
                });
                return $couriers[0];
                
            case 'specific':
                if ($this->config['preferred_courier_id']) {
                    foreach ($couriers as $courier) {
                        if ($courier['courier_company_id'] == $this->config['preferred_courier_id']) {
                            return $courier;
                        }
                    }
                }
                // Fallback to first courier if preferred not found
                return $couriers[0];
                
            default:
                return $couriers[0];
        }
    }
    
    /**
     * Calculate package weight
     */
    private function calculateWeight($orderItems) {
        $totalQuantity = array_sum(array_column($orderItems, 'quantity'));
        
        switch ($this->config['weight_calculation']) {
            case 'fixed':
                return $this->config['fixed_weight'];
                
            case 'per_item':
                $weight = $totalQuantity * $this->config['weight_per_item'];
                return max($weight, $this->config['minimum_weight']);
                
            case 'custom':
                // Implement custom logic here
                return max(0.5, $totalQuantity * 0.5);
                
            default:
                return $this->config['minimum_weight'];
        }
    }
    
    /**
     * Calculate shipping charges (₹60 per kg minimum)
     */
    private function calculateShippingCharges($weight) {
        $ratePerKg = 60; // ₹60 per kg
        $charges = $weight * $ratePerKg;
        
        // Minimum charge is ₹60 (for 1kg or less)
        return max($charges, 60);
    }
    
    /**
     * Update order tracking status from Shiprocket
     * @param int $orderId Local order ID
     * @return array Result with status
     */
    public function updateTrackingStatus($orderId) {
        try {
            // Get order details
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order || !$order['shiprocket_shipment_id']) {
                return ['status' => 'error', 'message' => 'Order or shipment not found'];
            }
            
            // Get tracking info from Shiprocket
            $trackingData = $this->shiprocket->trackShipment($order['shiprocket_shipment_id']);
            
            if (!isset($trackingData['tracking_data'])) {
                return ['status' => 'error', 'message' => 'No tracking data available'];
            }
            
            $tracking = $trackingData['tracking_data'];
            $shipmentStatus = $tracking['shipment_status'] ?? null;
            $currentStatus = $tracking['shipment_track'][0]['current_status'] ?? null;
            $deliveredDate = $tracking['delivered_date'] ?? null;
            
            // Map Shiprocket status to local order status
            $localStatus = $this->mapShiprocketStatus($shipmentStatus);
            
            // Update database
            $updateStmt = $this->db->prepare("UPDATE orders 
                SET shipment_status = ?,
                    current_status = ?,
                    status = ?,
                    tracking_updated_at = NOW(),
                    delivered_at = ?
                WHERE id = ?");
            $updateStmt->execute([
                $shipmentStatus,
                $currentStatus,
                $localStatus,
                $deliveredDate,
                $orderId
            ]);
            
            // Store tracking history
            $this->storeTrackingHistory($orderId, $tracking);
            
            $this->log("Tracking updated for order #$orderId: $shipmentStatus -> $localStatus");
            
            return [
                'status' => 'success',
                'shipment_status' => $shipmentStatus,
                'local_status' => $localStatus,
                'current_status' => $currentStatus
            ];
            
        } catch (Exception $e) {
            $this->log("Failed to update tracking for order #$orderId: " . $e->getMessage(), 'error');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Map Shiprocket status to local order status
     */
    private function mapShiprocketStatus($shiprocketStatus) {
        $statusMap = [
            'PICKUP_SCHEDULED' => 'shipped',
            'PICKED_UP' => 'shipped',
            'IN_TRANSIT' => 'shipped',
            'OUT_FOR_DELIVERY' => 'shipped',
            'DELIVERED' => 'delivered',
            'RTO' => 'cancelled',
            'CANCELLED' => 'cancelled',
            'LOST' => 'cancelled',
            'DAMAGED' => 'cancelled'
        ];
        
        return $statusMap[$shiprocketStatus] ?? 'processing';
    }
    
    /**
     * Store tracking history in database
     */
    private function storeTrackingHistory($orderId, $trackingData) {
        try {
            $awbCode = $trackingData['awb_code'] ?? null;
            if (!$awbCode) return;
            
            // Get shipment track activities
            $activities = $trackingData['shipment_track_activities'] ?? [];
            
            foreach ($activities as $activity) {
                $status = $activity['activity'] ?? $activity['sr-status-label'] ?? 'Unknown';
                $location = $activity['location'] ?? null;
                $date = $activity['date'] ?? null;
                
                // Check if this activity already exists
                $checkStmt = $this->db->prepare("SELECT id FROM shipment_tracking_history 
                    WHERE order_id = ? AND awb_code = ? AND status = ? AND tracking_date = ?");
                $checkStmt->execute([$orderId, $awbCode, $status, $date]);
                
                if (!$checkStmt->fetch()) {
                    // Insert new tracking record
                    $insertStmt = $this->db->prepare("INSERT INTO shipment_tracking_history 
                        (order_id, awb_code, status, location, tracking_date) 
                        VALUES (?, ?, ?, ?, ?)");
                    $insertStmt->execute([$orderId, $awbCode, $status, $location, $date]);
                }
            }
        } catch (Exception $e) {
            $this->log("Failed to store tracking history for order #$orderId: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Update all pending shipments tracking status
     * Call this from a cron job
     */
    public function updateAllPendingShipments() {
        try {
            // Get all orders that are shipped but not delivered
            $stmt = $this->db->query("SELECT id, order_number FROM orders 
                WHERE status IN ('shipped', 'processing') 
                AND shiprocket_shipment_id IS NOT NULL 
                AND awb_code IS NOT NULL
                ORDER BY created_at DESC
                LIMIT 50");
            
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $updated = 0;
            $errors = 0;
            
            foreach ($orders as $order) {
                $result = $this->updateTrackingStatus($order['id']);
                if ($result['status'] === 'success') {
                    $updated++;
                    
                    // If delivered, log it
                    if ($result['local_status'] === 'delivered') {
                        $this->log("Order #{$order['order_number']} marked as DELIVERED");
                    }
                } else {
                    $errors++;
                }
                
                // Sleep to avoid rate limiting
                usleep(500000); // 0.5 seconds
            }
            
            $this->log("Bulk tracking update completed: $updated updated, $errors errors");
            
            return [
                'status' => 'success',
                'updated' => $updated,
                'errors' => $errors,
                'total' => count($orders)
            ];
            
        } catch (Exception $e) {
            $this->log("Bulk tracking update failed: " . $e->getMessage(), 'error');
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Parse shipping address
     */
    private function parseAddress($address) {
        $result = [
            'valid' => false,
            'address' => '',
            'pincode' => null,
            'phone' => null,
            'state' => null,
            'city' => null
        ];
        
        // Split address into lines
        $lines = array_filter(array_map('trim', explode("\n", $address)));
        
        // Extract phone - look for line with "Phone:" or standalone 10 digits
        foreach ($lines as $line) {
            if (preg_match('/Phone:\s*(\d{10})/i', $line, $match)) {
                $result['phone'] = $match[1];
                break;
            } elseif (preg_match('/^(\d{10})$/', $line, $match)) {
                $result['phone'] = $match[1];
                break;
            }
        }
        
        // Extract pincode - look for 6 digits NOT part of a 10-digit number
        foreach ($lines as $line) {
            // Skip lines that contain phone numbers
            if (preg_match('/\d{10}/', $line)) continue;
            
            // Look for 6-digit pincode
            if (preg_match('/\b(\d{6})\b/', $line, $match)) {
                $result['pincode'] = $match[1];
                break;
            }
        }
        
        // Extract state
        $states = ['Kerala', 'Karnataka', 'Tamil Nadu', 'Maharashtra', 'Delhi', 'Gujarat', 
                   'Rajasthan', 'Punjab', 'Haryana', 'Uttar Pradesh', 'West Bengal', 
                   'Andhra Pradesh', 'Telangana', 'Madhya Pradesh', 'Bihar', 'Odisha',
                   'Assam', 'Jharkhand', 'Chhattisgarh', 'Goa', 'Himachal Pradesh',
                   'Jammu and Kashmir', 'Uttarakhand', 'Meghalaya', 'Manipur', 'Mizoram',
                   'Nagaland', 'Sikkim', 'Tripura', 'Arunachal Pradesh'];
        
        foreach ($states as $state) {
            if (stripos($address, $state) !== false) {
                $result['state'] = $state;
                break;
            }
        }
        
        // Extract city - look in the line containing state and pincode
        foreach ($lines as $line) {
            if ($result['state'] && stripos($line, $result['state']) !== false) {
                // Try to extract city from pattern: "City, State, Pincode" or "City, State - Pincode"
                if (preg_match('/([A-Za-z\s]+)\s*,\s*' . preg_quote($result['state'], '/') . '/i', $line, $match)) {
                    $cityCandidate = trim($match[1]);
                    // Remove any trailing commas or dashes
                    $cityCandidate = trim($cityCandidate, " ,\t\n\r\0\x0B-");
                    if ($cityCandidate) {
                        $result['city'] = $cityCandidate;
                    }
                }
                break;
            }
        }
        
        // Build clean address (exclude phone line and last line with city/state/pincode)
        $addressLines = [];
        foreach ($lines as $line) {
            // Skip phone line
            if (preg_match('/Phone:/i', $line) || preg_match('/^\d{10}$/', $line)) continue;
            // Skip line with state and pincode (usually last line)
            if ($result['state'] && $result['pincode'] && 
                stripos($line, $result['state']) !== false && 
                strpos($line, $result['pincode']) !== false) continue;
            // Skip "India" line
            if (strtolower(trim($line)) === 'india') continue;
            
            $addressLines[] = $line;
        }
        
        $result['address'] = implode(', ', $addressLines);
        
        // Fallback: if address is empty, use first line
        if (empty($result['address']) && !empty($lines)) {
            $result['address'] = $lines[0];
        }
        
        $result['valid'] = ($result['pincode'] && $result['phone'] && $result['state'] && $result['city']);
        
        return $result;
    }
    
    /**
     * Extract pincode from address
     */
    private function extractPincode($address) {
        if (preg_match('/\b(\d{6})\b/', $address, $match)) {
            return $match[1];
        }
        return null;
    }
    
    /**
     * Log automation events
     */
    private function log($message, $level = 'info') {
        if (!$this->config['log_automation_events']) return;
        
        $logMessage = date('Y-m-d H:i:s') . " [$level] $message\n";
        
        // Ensure logs directory exists
        $logDir = dirname($this->config['log_file']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($this->config['log_file'], $logMessage, FILE_APPEND);
    }
}