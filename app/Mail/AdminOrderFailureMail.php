<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class AdminOrderFailureMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function build()
    {
        $e = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $order = $this->order;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Failure Notification</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .email-container { max-width: 600px; margin: 20px auto; background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
    h1 { color: #d9534f; text-align: center; }
    p { font-size: 16px; color: #333; line-height: 1.6; }
    table { margin-top: 20px; border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
    th { background: #f2f2f2; font-weight: bold; }
    .footer { margin-top: 20px; font-size: 12px; text-align: center; color: #777; }
  </style>
</head>
<body>
  <div class="email-container">
    <h1>Order Failed</h1>
    <p>Hello Admin,</p>
    <p>We encountered an issue processing the following order. Please review the details and take the necessary action from the admin panel.</p>
    <table>
      <tr><th>Order ID</th><td>{$e((string) ($order->refno ?? $order->order_number ?? $order->id))}</td></tr>
      <tr><th>Customer Name</th><td>{$e(trim((string) (($order->sender_first_name ?? '').' '.($order->sender_last_name ?? ''))))}</td></tr>
      <tr><th>Email</th><td>{$e((string) ($order->sender_email ?? $order->billing_email ?? ''))}</td></tr>
      <tr><th>Product Name</th><td>{$e((string) ($order->product_name ?? ''))}</td></tr>
      <tr><th>Denomination</th><td>₹{$e((string) ($order->denomination ?? ''))}</td></tr>
      <tr><th>Quantity</th><td>{$e((string) ($order->quantity ?? ''))}</td></tr>
      <tr><th>Grand Total</th><td>₹{$e((string) ($order->grand_payable_amount ?? ''))}</td></tr>
      <tr><th>Discounted Amount</th><td>₹{$e((string) ($order->discounted_amount_value ?? ''))}</td></tr>
      <tr><th>Total Amount Payable</th><td>₹{$e((string) ($order->amount_payable_after_discount ?? ''))}</td></tr>
      <tr><th>Status</th><td><strong>Failed</strong></td></tr>
      <tr><th>Order Date</th><td>{$e((string) ($order->created_at ?? ''))}</td></tr>
    </table>
    <div class="footer">
      <p>This is an automated notification. Please do not reply to this email.</p>
    </div>
  </div>
</body>
</html>
HTML;

        return $this->subject('Order Failure Notification')->html($html);
    }
}

