<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function build()
    {
        $e = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $order = $this->order;
        $orderNo = $order->order_number ?? (string) $order->id;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed</title>
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
    .wrap { max-width:600px; margin:20px auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; }
    h1 { color:#2f855a; text-align:center; }
    p { font-size:16px; color:#333; line-height:1.6; }
    .meta { margin-top:16px; font-size:14px; color:#555; }
    .badge { display:inline-block; padding:6px 10px; border-radius:999px; background:#edf2f7; color:#2d3748; font-size:12px; }
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Order confirmed</h1>
    <p>Your order has been placed successfully.</p>
    <p class="meta"><span class="badge">Order #{$e((string) $orderNo)}</span></p>
    <p class="meta">Product: {$e((string) ($order->product_name ?? ''))}</p>
  </div>
</body>
</html>
HTML;

        return $this->subject("Order Confirmed - #{$orderNo}")->html($html);
    }
}

