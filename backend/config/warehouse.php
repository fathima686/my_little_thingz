<?php
// Fixed warehouse address for admin procurement orders (normalized)
return [
  // Backward-compatible single string
  'address' => "Purathel, Anakkal PO, Kanjirapally 686508\nPhone: 9495470077",
  // Structured fields for Shiprocket/integrations
  'address_fields' => [
    'name' => 'Purathel',
    'address_line1' => 'Anakkal PO',
    'address_line2' => '',
    'city' => 'Kanjirapally',
    'state' => 'Kerala',
    'pincode' => '686508',
    'country' => 'India',
    'phone' => '9495470077',
  ],
];