<?php
// Email configuration for sending payment notifications
// You can use SMTP or PHP's built-in mail() function

$email_config = [
    // SMTP Configuration (Recommended for better delivery)
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'fathima470077@gmail.com',
    'smtp_password' => 'zduw nxao ofdz foyx',
    'smtp_encryption' => 'tls',
    
    // From email details
    'from_email' => 'fathima470077@gmail.com',
    'from_name' => 'My Little Thingz',

    // Admin notifications
    // Set this to the email that should receive admin alerts (defaults to from_email if not set)
    'admin_email' => 'fathima470077@gmail.com',
    'admin_name'  => 'Store Admin',
    
    // Use SMTP (true) or PHP mail() (false)
    'use_smtp' => true, // Using SMTP for better delivery and reliability
];

return $email_config;
?>
