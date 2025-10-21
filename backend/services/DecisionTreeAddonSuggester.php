<?php
/**
 * Decision Tree Add-on Suggester
 * 
 * Recommends add-ons (Greeting Card, Ribbon) based on gift price
 * using a simple decision tree rule engine.
 */

class DecisionTreeAddonSuggester
{
    /**
     * Define all available add-ons with properties
     */
    private const ADDONS = [
        'greeting_card' => [
            'id' => 'greeting_card',
            'name' => 'Greeting Card',
            'description' => 'A personalized greeting card to express your feelings',
            'price' => 150,
            'icon' => '🎴'
        ],
        'ribbon' => [
            'id' => 'ribbon',
            'name' => 'Decorative Ribbon',
            'description' => 'Beautiful ribbon to enhance the gift presentation',
            'price' => 75,
            'icon' => '🎀'
        ]
    ];

    /**
     * Decision Tree Rules
     * 
     * Rules are evaluated in order until a match is found.
     * Each rule contains conditions and suggested add-ons.
     */
    private const RULES = [
        // Rule 1: Premium gifts (>= 1000) → Suggest both add-ons
        [
            'name' => 'Premium Gift Bundle',
            'conditions' => [
                'gift_price' => ['operator' => '>=', 'value' => 1000]
            ],
            'suggestions' => ['greeting_card', 'ribbon']
        ],
        // Rule 2: Mid-range gifts (500-999) → Suggest Greeting Card
        [
            'name' => 'Mid-Range Greeting',
            'conditions' => [
                'gift_price' => ['operator' => '>=', 'value' => 500],
                'gift_price_max' => ['operator' => '<', 'value' => 1000]
            ],
            'suggestions' => ['greeting_card']
        ],
        // Rule 3: Budget gifts (<500) → Suggest Ribbon
        [
            'name' => 'Budget Friendly',
            'conditions' => [
                'gift_price' => ['operator' => '<', 'value' => 500]
            ],
            'suggestions' => ['ribbon']
        ]
    ];

    /**
     * Evaluate decision tree and return suggested add-ons
     * 
     * @param float $cartTotal Total price of all items in cart
     * @param array $cartItems Array of cart items with prices
     * @return array Suggested add-ons
     */
    public static function suggestAddons($cartTotal, $cartItems = [])
    {
        $suggestions = [];
        $appliedRule = null;

        // Evaluate each rule in order
        foreach (self::RULES as $rule) {
            if (self::evaluateRule($rule, $cartTotal, $cartItems)) {
                $suggestions = $rule['suggestions'];
                $appliedRule = $rule['name'];
                break; // Stop at first matching rule
            }
        }

        // Build response with addon details
        $result = [];
        foreach ($suggestions as $addonKey) {
            if (isset(self::ADDONS[$addonKey])) {
                $result[] = self::ADDONS[$addonKey];
            }
        }

        return [
            'success' => true,
            'suggested_addons' => $result,
            'applied_rule' => $appliedRule,
            'reasoning' => self::getReasoningMessage($appliedRule, $cartTotal)
        ];
    }

    /**
     * Check if a rule's conditions are met
     * 
     * @param array $rule Rule definition
     * @param float $cartTotal Total price
     * @param array $cartItems Cart items
     * @return bool True if rule conditions are met
     */
    private static function evaluateRule($rule, $cartTotal, $cartItems)
    {
        if (empty($rule['conditions'])) {
            return true; // No conditions = always true
        }

        foreach ($rule['conditions'] as $field => $condition) {
            $value = null;

            // Get the value to compare based on field
            if ($field === 'gift_price') {
                $value = $cartTotal;
            } elseif ($field === 'gift_price_max') {
                $value = $cartTotal;
            } elseif (strpos($field, 'item_') === 0) {
                // Could be used for item-specific logic later
                continue;
            }

            // Perform comparison
            if ($value !== null && !self::compareValues($value, $condition)) {
                return false; // Condition not met
            }
        }

        return true; // All conditions met
    }

    /**
     * Compare value against condition
     * 
     * @param mixed $value Value to test
     * @param array $condition Condition with 'operator' and 'value'
     * @return bool Result of comparison
     */
    private static function compareValues($value, $condition)
    {
        $operator = $condition['operator'] ?? '==';
        $condValue = $condition['value'] ?? null;

        switch ($operator) {
            case '>=':
                return $value >= $condValue;
            case '>':
                return $value > $condValue;
            case '<=':
                return $value <= $condValue;
            case '<':
                return $value < $condValue;
            case '==':
                return $value == $condValue;
            case '!=':
                return $value != $condValue;
            case 'in':
                return in_array($value, (array)$condValue);
            default:
                return true;
        }
    }

    /**
     * Get a human-readable reasoning message
     * 
     * @param string $ruleName Name of applied rule
     * @param float $cartTotal Cart total
     * @return string Reasoning message
     */
    private static function getReasoningMessage($ruleName, $cartTotal)
    {
        $messages = [
            'Premium Gift Bundle' => "Great choice! Your premium gift (₹$cartTotal) deserves a complete presentation with both a greeting card and ribbon.",
            'Mid-Range Greeting' => "Your gift (₹$cartTotal) would be beautifully enhanced with a personalized greeting card.",
            'Budget Friendly' => "A decorative ribbon would make your gift (₹$cartTotal) look even more special!"
        ];

        return $messages[$ruleName] ?? "Consider adding these special touches to your gift.";
    }

    /**
     * Get all available add-ons
     */
    public static function getAllAddons()
    {
        return self::ADDONS;
    }

    /**
     * Get price of specific addon
     */
    public static function getAddonPrice($addonKey)
    {
        return self::ADDONS[$addonKey]['price'] ?? 0;
    }
}
?>