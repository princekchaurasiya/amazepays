<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendVerificationCode extends Mailable
{
    use Queueable, SerializesModels;

    public $code;

    /**
     * Create a new message instance.
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $code = htmlspecialchars((string) $this->code, ENT_QUOTES, 'UTF-8');
        $year = date('Y');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification</title>
  <style>
    body { font-family: Segoe UI, Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
    .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,.1); overflow: hidden; }
    .header { background: linear-gradient(135deg, #007bff, #0056b3); color: #fff; padding: 30px; text-align: center; }
    .header h1 { margin: 0; font-size: 28px; font-weight: 300; }
    .content { padding: 40px 30px; text-align: center; }
    .verification-code { background: #f8f9fa; border: 2px dashed #007bff; border-radius: 10px; padding: 20px; margin: 30px 0; font-size: 32px; font-weight: bold; color: #007bff; letter-spacing: 5px; font-family: Courier New, monospace; }
    .message { color: #666; font-size: 16px; margin-bottom: 20px; }
    .expiry { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; color: #856404; font-size: 14px; }
    .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
    .logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="logo">Amazepays</div>
      <h1>Email Verification</h1>
    </div>
    <div class="content">
      <p class="message">Thank you for registering with Amazepays! To complete your registration, please use the verification code below:</p>
      <div class="verification-code">{$code}</div>
      <p class="message">Enter this code on our verification page to activate your account.</p>
      <div class="expiry"><strong>Important:</strong> This verification code will expire in 10 minutes for security reasons.</div>
    </div>
    <div class="footer">
      <p>If you didn't create an account with Amazepays, please ignore this email.</p>
      <p>&copy; {$year} Amazepays. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
HTML;

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject('Your Verification Code')
            ->html($html);
    }
}
