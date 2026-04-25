<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class OrderFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $reason,
    ) {}

    public function build()
    {
        $e = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $orderNo = $this->order->order_number ?? (string) $this->order->id;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Failed</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0;">
  <div style="max-width:600px; margin:20px auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px;">
    <h1 style="text-align:center; color:#d9534f;">Order failed</h1>
    <p>Order <strong>#{$e((string) $orderNo)}</strong> could not be completed.</p>
    <p>Reason: {$e($this->reason)}</p>
  </div>
</body>
</html>
HTML;

        return $this->subject("Order Failed - #{$orderNo}")->html($html);
    }
}

