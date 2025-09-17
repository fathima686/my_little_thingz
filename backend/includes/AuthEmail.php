<?php
// Password reset email helper (supports link and OTP flows)
require_once __DIR__ . '/../config/email.php';
require_once __DIR__ . '/SimpleEmailSender.php';

class AuthEmail {
    private SimpleEmailSender $sender;

    public function __construct() {
        $this->sender = new SimpleEmailSender();
    }

    // Link-based reset (kept for compatibility)
    public function sendResetLink(string $toEmail, string $toName, string $resetLink): bool {
        $subject = 'Reset your password';
        $message = $this->resetTemplate($toName, $resetLink);
        return $this->sender->sendGenericEmail($toEmail, $toName, $subject, $message);
    }

    // OTP-based reset
    public function sendResetOtpEmail(string $toEmail, string $toName, string $otp): bool {
        $subject = 'Your password reset code';
        $message = $this->otpTemplate($toName, $otp);
        return $this->sender->sendGenericEmail($toEmail, $toName, $subject, $message);
    }

    private function resetTemplate(string $name, string $link): string {
        $safeName = htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8');
        $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Password Reset</title>"
            . "<style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#333;margin:0;padding:0}"
            . ".container{max-width:600px;margin:0 auto;padding:24px}"
            . ".card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:24px}"
            . ".btn{display:inline-block;background:#6b46c1;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;margin-top:12px}"
            . ".muted{color:#666;font-size:13px;margin-top:16px}</style></head><body>"
            . "<div class='container'><div class='card'>"
            . "<h2>Password reset request</h2>"
            . "<p>Hi <strong>{$safeName}</strong>,</p>"
            . "<p>We received a request to reset your password. Click the button below to set a new password. This link will expire in 30 minutes.</p>"
            . "<p><a class='btn' href='{$safeLink}'>Reset Password</a></p>"
            . "<p class='muted'>If you did not request this, you can safely ignore this email.</p>"
            . "</div></div></body></html>";
    }

    private function otpTemplate(string $name, string $otp): string {
        $safeName = htmlspecialchars($name ?: 'there', ENT_QUOTES, 'UTF-8');
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Password Reset Code</title>"
            . "<style>body{font-family:Arial,sans-serif;background:#f6f7fb;color:#333;margin:0;padding:0}"
            . ".container{max-width:600px;margin:0 auto;padding:24px}"
            . ".card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06);padding:24px}"
            . ".code{font-size:28px;letter-spacing:6px;font-weight:bold;background:#f1f1f9;padding:12px 16px;border-radius:8px;display:inline-block}"
            . ".muted{color:#666;font-size:13px;margin-top:16px}</style></head><body>"
            . "<div class='container'><div class='card'>"
            . "<h2>Your password reset code</h2>"
            . "<p>Hi <strong>{$safeName}</strong>,</p>"
            . "<p>Use the following one-time code to reset your password. This code will expire in 30 minutes.</p>"
            . "<p class='code'>{$safeOtp}</p>"
            . "<p class='muted'>If you did not request this, you can safely ignore this email.</p>"
            . "</div></div></body></html>";
    }
}