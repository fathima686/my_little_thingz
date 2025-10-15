<?php

class Shiprocket {
    private $email;
    private $password;
    private $baseUrl;
    private $token;
    private $tokenExpiry;

    public function __construct() {
        $config = require __DIR__ . '/../config/shiprocket.php';
        $this->email = isset($config['email']) ? $config['email'] : null;
        $this->password = isset($config['password']) ? $config['password'] : null;
        $this->baseUrl = $config['base_url'];
        $this->token = isset($config['token']) && $config['token'] ? $config['token'] : null;
        $this->tokenExpiry = null;

        // If pre-issued token provided, set expiry from config or parse JWT `exp`
        if ($this->token) {
            if (!empty($config['token_expiry']) && is_numeric($config['token_expiry'])) {
                $this->tokenExpiry = (int)$config['token_expiry'];
            } else {
                $parsedExp = $this->parseJwtExpiry($this->token);
                if ($parsedExp) {
                    $this->tokenExpiry = $parsedExp;
                }
            }
        }
    }

    // Parse JWT and return exp (unix epoch) if available; otherwise null
    private function parseJwtExpiry($jwt) {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }
        $payload = $parts[1];
        $payload = strtr($payload, '-_', '+/');
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $json = base64_decode($payload);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (is_array($data) && isset($data['exp']) && is_numeric($data['exp'])) {
            return (int)$data['exp'];
        }
        return null;
    }

    private function authenticate() {
        // If we already have a valid token (pre-issued or previously fetched), keep using it
        if ($this->token && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return;
        }

        $url = $this->baseUrl . '/auth/login';
        $data = [
            'email' => $this->email,
            'password' => $this->password,
        ];

        $response = $this->makeRequest('POST', $url, $data);

        if ($response && isset($response['token'])) {
            $this->token = $response['token'];
            // Prefer explicit exp from JWT; fallback to 23 hours safety window
            $exp = $this->parseJwtExpiry($this->token);
            if ($exp) {
                $this->tokenExpiry = $exp;
            } else {
                $this->tokenExpiry = time() + (23 * 3600);
            }
        } else {
            throw new Exception('Shiprocket authentication failed: ' . json_encode($response));
        }
    }

    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = ['Content-Type: application/json'];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            return ['error' => $response, 'http_code' => $httpCode];
        }
    }

    /**
     * Create a new order in Shiprocket
     * @param array $orderData Order details
     * @return array Response from Shiprocket API
     */
    public function createOrder($orderData) {
        $this->authenticate();

        $url = $this->baseUrl . '/orders/create/adhoc';
        return $this->makeRequest('POST', $url, $orderData);
    }

    /**
     * Track order by AWB number
     * @param string $awb AWB tracking number
     * @return array Tracking information
     */
    public function trackOrder($awb) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/track/awb/' . $awb;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get order details by Shiprocket order ID
     * @param int $orderId Shiprocket order ID
     * @return array Order details
     */
    public function getOrder($orderId) {
        $this->authenticate();

        $url = $this->baseUrl . '/orders/show/' . $orderId;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Cancel an order
     * @param int $orderId Shiprocket order ID
     * @return array Response from API
     */
    public function cancelOrder($orderId) {
        $this->authenticate();

        $url = $this->baseUrl . '/orders/cancel';
        $data = ['ids' => [$orderId]];
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Get available courier services for a shipment
     * @param array $shipmentData Shipment details (pickup_postcode, delivery_postcode, weight, cod, etc.)
     * @return array Available courier services
     */
    public function getCourierServiceability($shipmentData) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/serviceability';
        return $this->makeRequest('GET', $url . '?' . http_build_query($shipmentData));
    }

    /**
     * Assign courier and generate AWB for shipment
     * @param int $shipmentId Shiprocket shipment ID
     * @param int $courierId Courier company ID
     * @return array AWB details
     */
    public function assignCourier($shipmentId, $courierId) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/assign/awb';
        $data = [
            'shipment_id' => $shipmentId,
            'courier_id' => $courierId
        ];
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Generate AWB for shipment (alias for assignCourier)
     * @param int $shipmentId Shiprocket shipment ID
     * @param int $courierId Courier company ID
     * @return array AWB details
     */
    public function generateAWB($shipmentId, $courierId) {
        return $this->assignCourier($shipmentId, $courierId);
    }

    /**
     * Schedule pickup for shipment
     * @param int $shipmentId Shiprocket shipment ID
     * @return array Pickup schedule response
     */
    public function schedulePickup($shipmentId) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/generate/pickup';
        $data = ['shipment_id' => [$shipmentId]];
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Request pickup for shipment (alias for schedulePickup)
     * @param int $shipmentId Shiprocket shipment ID
     * @return array Pickup request response
     */
    public function requestPickup($shipmentId) {
        return $this->schedulePickup($shipmentId);
    }

    /**
     * Generate shipping label
     * @param array $shipmentIds Array of shipment IDs
     * @return array Label URL
     */
    public function generateLabel($shipmentIds) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/generate/label';
        $data = ['shipment_id' => $shipmentIds];
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Generate manifest for shipments
     * @param array $shipmentIds Array of shipment IDs
     * @return array Manifest URL
     */
    public function generateManifest($shipmentIds) {
        $this->authenticate();

        $url = $this->baseUrl . '/manifests/generate';
        $data = ['shipment_id' => $shipmentIds];
        return $this->makeRequest('POST', $url, $data);
    }

    /**
     * Get all orders with filters
     * @param array $filters Optional filters (page, per_page, status, etc.)
     * @return array Orders list
     */
    public function getOrders($filters = []) {
        $this->authenticate();

        $queryString = !empty($filters) ? '?' . http_build_query($filters) : '';
        $url = $this->baseUrl . '/orders' . $queryString;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get shipment details by shipment ID
     * @param int $shipmentId Shiprocket shipment ID
     * @return array Shipment details
     */
    public function getShipment($shipmentId) {
        $this->authenticate();

        $url = $this->baseUrl . '/shipments/' . $shipmentId;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Track shipment by shipment ID
     * @param int $shipmentId Shiprocket shipment ID
     * @return array Tracking information
     */
    public function trackShipment($shipmentId) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/track/shipment/' . $shipmentId;
        return $this->makeRequest('GET', $url);
    }

    /**
     * Get pickup locations
     * @return array List of pickup locations
     */
    public function getPickupLocations() {
        $this->authenticate();

        $url = $this->baseUrl . '/settings/company/pickup';
        return $this->makeRequest('GET', $url);
    }

    /**
     * Calculate shipping charges
     * @param array $params Shipping parameters
     * @return array Shipping charges
     */
    public function calculateShipping($params) {
        $this->authenticate();

        $url = $this->baseUrl . '/courier/serviceability?' . http_build_query($params);
        return $this->makeRequest('GET', $url);
    }

    /**
     * Update order status
     * @param int $orderId Shiprocket order ID
     * @param string $status New status
     * @return array Response
     */
    public function updateOrderStatus($orderId, $status) {
        $this->authenticate();

        $url = $this->baseUrl . '/orders/update/' . $orderId;
        $data = ['status' => $status];
        return $this->makeRequest('PATCH', $url, $data);
    }
}
?>