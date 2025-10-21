<?php
/**
 * Shiprocket Automation Configuration
 * Control automatic shipment creation and courier assignment
 */

return [
    // Enable/disable automatic shipment creation after payment
    'auto_create_shipment' => true,
    
    // Enable/disable automatic courier assignment
    'auto_assign_courier' => true,
    
    // Courier selection strategy
    // Options: 'cheapest', 'fastest', 'recommended', 'specific', 'balanced', 'bayesian'
    // - 'cheapest': Select courier with lowest rate
    // - 'fastest': Select courier with shortest delivery time
    // - 'recommended': Use Shiprocket's recommended courier (usually best overall)
    // - 'specific': Use a specific courier (set preferred_courier_id below)
    // - 'balanced': Balance between cost and speed (recommended for most cases)
    'courier_selection_strategy' => 'recommended',

    // Bayesian re-ranking options (used when strategy = 'bayesian')
    // If bayesian_scorer_url is set, the system will POST order+couriers to it
    // expecting a response with couriers including '_bayes_score' and already sorted.
    'bayesian_scorer_url' => null, // e.g. 'http://localhost:5001/score'
    'bayesian_scorer_timeout_seconds' => 2,
    // Optional local blend weights when external scorer is not configured
    'bayesian_blend' => [
        'model' => 1.0,
        'cost' => 0.2,
        'speed' => 0.2
    ],
    
    // If strategy is 'specific', specify courier company ID
    // Example: 1 for Delhivery, 2 for Blue Dart, etc.
    'preferred_courier_id' => null,
    
    // Enable/disable automatic pickup scheduling
    'auto_schedule_pickup' => true, // Automatically schedule pickup after courier assignment
    
    // Default package dimensions (in cm)
    'default_dimensions' => [
        'length' => 10,
        'breadth' => 10,
        'height' => 10
    ],
    
    // Weight calculation
    // Options: 'fixed', 'per_item', 'custom'
    'weight_calculation' => 'per_item',
    'weight_per_item' => 0.5, // kg per item
    'minimum_weight' => 0.5,   // minimum package weight in kg
    'fixed_weight' => 0.5,     // used if weight_calculation is 'fixed'
    
    // Pickup location name (must match Shiprocket dashboard)
    'pickup_location' => 'Purathel',
    
    // Notification settings
    'notify_admin_on_success' => true,
    'notify_admin_on_failure' => true,
    'admin_notification_email' => null, // null = use default from email config
    
    // Retry settings
    'retry_on_failure' => false,
    'max_retries' => 3,
    'retry_delay_seconds' => 60,
    
    // Logging
    'log_automation_events' => true,
    'log_file' => __DIR__ . '/../logs/shiprocket_automation.log'
];