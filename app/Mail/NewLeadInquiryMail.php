<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class NewLeadInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name:string,email:string,message:string,contact_number:string}  $leadDetails
     */
    public function __construct(public readonly array $leadDetails) {}

    public function build()
    {
        $e = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>New Lead Inquiry</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .email-container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    h1 { color: #5bc0de; text-align: center; }
    p { font-size: 16px; color: #333; line-height: 1.6; }
    table { margin-top: 20px; border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #f2f2f2; font-weight: bold; }
  </style>
</head>
<body>
  <div class="email-container">
    <h1>New Lead Inquiry for Amazepay</h1>
    <p>You have received a new lead inquiry. Below are the details:</p>
    <table>
      <tr><th>Name</th><td>{$e($this->leadDetails['name'] ?? '')}</td></tr>
      <tr><th>Mobile Number</th><td>{$e($this->leadDetails['contact_number'] ?? '')}</td></tr>
      <tr><th>Email</th><td>{$e($this->leadDetails['email'] ?? '')}</td></tr>
      <tr><th>Message</th><td>{$e($this->leadDetails['message'] ?? '')}</td></tr>
    </table>
    <p>Please follow up with the lead as soon as possible.</p>
  </div>
</body>
</html>
HTML;

        return $this->subject('New Lead Inquiry for Amazepay')->html($html);
    }
}

