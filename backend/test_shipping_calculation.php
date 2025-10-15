<?php
/**
 * Test script to verify shipping calculation logic
 */

echo "=== Shipping Calculation Test ===\n\n";

// Test cases
$testCases = [
    ['weight' => 0.5, 'expected' => 60],
    ['weight' => 1.0, 'expected' => 60],
    ['weight' => 1.5, 'expected' => 120],
    ['weight' => 2.0, 'expected' => 120],
    ['weight' => 2.5, 'expected' => 180],
    ['weight' => 3.0, 'expected' => 180],
    ['weight' => 0.1, 'expected' => 60],
];

echo "Formula: max(60, ceil(weight) * 60)\n";
echo "Rate: ₹60 per kg, Minimum: ₹60\n\n";

foreach ($testCases as $test) {
    $weight = $test['weight'];
    $expected = $test['expected'];
    $calculated = max(60.0, ceil($weight) * 60.0);
    $status = $calculated == $expected ? '✅ PASS' : '❌ FAIL';
    
    echo sprintf(
        "%s | Weight: %.2f kg | Expected: ₹%d | Calculated: ₹%.2f\n",
        $status,
        $weight,
        $expected,
        $calculated
    );
}

echo "\n=== Cart Simulation ===\n\n";

// Simulate a cart with multiple items
$cartItems = [
    ['name' => 'Painting 1', 'weight' => 0.5, 'quantity' => 1],
    ['name' => 'Sculpture 1', 'weight' => 2.0, 'quantity' => 1],
    ['name' => 'Frame 1', 'weight' => 0.3, 'quantity' => 2],
];

$totalWeight = 0;
echo "Cart Items:\n";
foreach ($cartItems as $item) {
    $itemWeight = $item['weight'] * $item['quantity'];
    $totalWeight += $itemWeight;
    echo sprintf(
        "  - %s: %.2f kg × %d = %.2f kg\n",
        $item['name'],
        $item['weight'],
        $item['quantity'],
        $itemWeight
    );
}

$shipping = max(60.0, ceil($totalWeight) * 60.0);
echo sprintf("\nTotal Weight: %.2f kg\n", $totalWeight);
echo sprintf("Rounded Weight: %d kg\n", ceil($totalWeight));
echo sprintf("Shipping Charges: ₹%.2f\n", $shipping);

echo "\n=== Test Complete ===\n";