<?php
/**
 * BayesianCourierScorer
 *
 * Lightweight, pluggable scorer that estimates the probability of a successful shipment
 * (on-time and non-RTO) for each available courier option using a naive Bayes-style model.
 *
 * Supports two modes:
 * 1) External scorer API (if configured via bayesian_scorer_url)
 * 2) Local heuristic scorer (fallback) that blends cost/speed/signals into a probability
 */

class BayesianCourierScorer {
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    /**
     * Score and sort couriers in-place, highest score first.
     *
     * @param array $order              Order row from DB
     * @param string $deliveryPincode   6-digit destination pincode
     * @param array $couriers           Shiprocket available couriers array
     * @return array                    Couriers sorted by descending score with '_bayes_score'
     */
    public function scoreAndSortCouriers(array $order, $deliveryPincode, array $couriers) {
        // Try external scorer first if provided
        if (!empty($this->config['bayesian_scorer_url'])) {
            $scored = $this->tryExternalScorer($order, $deliveryPincode, $couriers);
            if (!empty($scored)) {
                return $scored;
            }
        }

        // Fallback: local heuristic "naive Bayes-like" scoring
        $scored = [];
        foreach ($couriers as $c) {
            $scored[] = $this->attachHeuristicScore($order, $deliveryPincode, $c);
        }

        usort($scored, function($a, $b) {
            $sa = $a['_bayes_score'] ?? 0.0;
            $sb = $b['_bayes_score'] ?? 0.0;
            // Descending order
            if ($sa == $sb) return 0;
            return ($sa < $sb) ? 1 : -1;
        });

        return $scored;
    }

    private function tryExternalScorer(array $order, $deliveryPincode, array $couriers) {
        $url = $this->config['bayesian_scorer_url'];
        $timeout = $this->config['bayesian_scorer_timeout_seconds'] ?? 2;

        $payload = [
            'order' => [
                'id' => $order['id'] ?? null,
                'payment_method' => $order['payment_method'] ?? 'Prepaid',
                'weight' => $order['weight'] ?? null,
                'shipping_address' => $order['shipping_address'] ?? null,
                'subtotal' => $order['subtotal'] ?? null,
                'total_amount' => $order['total_amount'] ?? null,
                'category' => $order['category'] ?? null
            ],
            'delivery_pincode' => $deliveryPincode,
            'couriers' => $couriers
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        $resp = curl_exec($ch);
        if ($resp === false) {
            curl_close($ch);
            return [];
        }
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code < 200 || $code >= 300) {
            return [];
        }

        $data = json_decode($resp, true);
        if (!is_array($data) || empty($data['couriers'])) {
            return [];
        }

        // Expect the external scorer to return the same list with '_bayes_score'
        // If not provided, ignore.
        $scored = [];
        foreach ($data['couriers'] as $c) {
            if (!isset($c['_bayes_score'])) {
                // Skip if score missing; we need consistent ordering
                return [];
            }
            $scored[] = $c;
        }

        usort($scored, function($a, $b) {
            $sa = $a['_bayes_score'] ?? 0.0;
            $sb = $b['_bayes_score'] ?? 0.0;
            if ($sa == $sb) return 0;
            return ($sa < $sb) ? 1 : -1;
        });

        return $scored;
    }

    private function attachHeuristicScore(array $order, $deliveryPincode, array $courier) {
        $rate = (float)($courier['rate'] ?? 0.0);
        $days = (float)($courier['estimated_delivery_days'] ?? 3.0);
        $name = strtolower($courier['courier_name'] ?? '');

        // Normalize features
        $normRate = $this->normalizePositive($rate, 50.0, 250.0); // assume 50-250 typical INR range
        $normDays = $this->normalizePositive($days, 1.0, 7.0);    // assume 1-7 days typical

        // Likelihoods (heuristic): lower rate and fewer days increase success
        $pCost = 1.0 - $normRate; // cheaper -> higher
        $pSpeed = 1.0 - $normDays; // faster -> higher

        // Brand prior bump for reputed carriers
        $brandBump = 0.0;
        if (strpos($name, 'bluedart') !== false || strpos($name, 'blue dart') !== false) $brandBump += 0.05;
        if (strpos($name, 'delhivery') !== false) $brandBump += 0.05;
        if (strpos($name, 'xpressbees') !== false) $brandBump += 0.03;

        // If order is COD, slightly penalize (even though current flow is prepaid)
        $isCod = strtolower($order['payment_method'] ?? 'prepaid') === 'cod';
        $codPenalty = $isCod ? 0.05 : 0.0;

        // Naive Bayes-style combination (independent features assumption)
        $p = $this->combineIndependent([max(0.0, $pCost), max(0.0, $pSpeed)]);
        $p = min(1.0, max(0.0, $p + $brandBump - $codPenalty));

        // Blend with optional weights from config
        $w = $this->config['bayesian_blend'] ?? ['model' => 1.0, 'cost' => 0.0, 'speed' => 0.0];
        $blend = (
            ($w['model'] ?? 1.0) * $p +
            ($w['cost'] ?? 0.0) * (1.0 - $normRate) +
            ($w['speed'] ?? 0.0) * (1.0 - $normDays)
        );

        $courier['_bayes_score'] = $blend;
        return $courier;
    }

    private function normalizePositive($value, $min, $max) {
        if ($max <= $min) return 0.0;
        $v = ($value - $min) / ($max - $min);
        if ($v < 0.0) return 0.0;
        if ($v > 1.0) return 1.0;
        return $v;
    }

    private function combineIndependent(array $probs) {
        // Combine independent probabilities P = 1 - Π(1 - pi)
        $product = 1.0;
        foreach ($probs as $p) {
            $p = max(0.0, min(1.0, $p));
            $product *= (1.0 - $p);
        }
        return 1.0 - $product;
    }
}



























