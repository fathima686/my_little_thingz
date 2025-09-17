# Email Notifications Setup Guide

## Overview
Your payment system now sends email notifications for both successful and failed payments.

## Email Configuration

### Step 1: Configure Email Settings
Edit `backend/config/email.php` and update the following:

```php
$email_config = [
    // SMTP Configuration (Recommended)
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'your-email@gmail.com', // Your Gmail address
    'smtp_password' => 'your-app-password', // Gmail App Password (not regular password)
    'smtp_encryption' => 'tls',
    
    // From email details
    'from_email' => 'your-email@gmail.com', // Your Gmail address
    'from_name' => 'My Little Thingz',
    
    // Use SMTP (true) or PHP mail() (false)
    'use_smtp' => true,
];
```

### Step 2: Gmail Setup (Recommended)

1. **Enable 2-Factor Authentication** on your Gmail account
2. **Generate App Password**:
   - Go to Google Account settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password (not your regular Gmail password)

### Step 3: Alternative - Use PHP mail() function
If you prefer to use your server's mail function instead of SMTP:

```php
'use_smtp' => false,
```

## Email Templates

### Payment Success Email
- **Subject**: "Payment Successful - Order #[ORDER_NUMBER]"
- **Content**: Beautiful HTML email with order details
- **Sent when**: Payment is successfully verified

### Payment Failure Email  
- **Subject**: "Payment Failed - Order #[ORDER_NUMBER]"
- **Content**: HTML email explaining the failure reason
- **Sent when**: Payment verification fails

## Features

✅ **Automatic Email Sending**: Emails are sent automatically after payment verification
✅ **Beautiful HTML Templates**: Professional-looking email designs
✅ **Order Details**: Includes order number, amount, and status
✅ **User-Friendly**: Clear success/failure messages
✅ **SMTP Support**: Reliable email delivery via Gmail SMTP

## Testing

After configuration, test by:
1. Making a test payment (success case)
2. Intentionally failing a payment (failure case)
3. Check your email inbox for notifications

## Troubleshooting

- **Emails not sending**: Check SMTP credentials and Gmail App Password
- **SMTP errors**: Verify Gmail settings and 2FA is enabled
- **PHP mail() issues**: Check server mail configuration

## Security Note

Never commit real email credentials to version control. Consider using environment variables for production.
