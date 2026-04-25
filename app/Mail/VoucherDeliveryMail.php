<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

final class VoucherDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param list<array<string, mixed>> $voucherCodes
     */
    public function __construct(
        public readonly Order $order,
        public readonly array $voucherCodes,
    ) {}

    public function build()
    {
        $e = static fn (?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

        $rows = '';
        foreach ($this->voucherCodes as $i => $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['code'] ?? $row['voucher_code'] ?? '');
            $pin = (string) ($row['pin'] ?? $row['voucher_pin'] ?? '');
            $expiry = (string) ($row['expiry'] ?? $row['expiry_date'] ?? '');
            $idx = (int) $i + 1;

            $rows .= '<tr>'
                .'<td style="border:1px solid #ddd;padding:10px;">'.$idx.'</td>'
                .'<td style="border:1px solid #ddd;padding:10px;font-family:monospace;">'.$e($code).'</td>'
                .'<td style="border:1px solid #ddd;padding:10px;font-family:monospace;">'.$e($pin).'</td>'
                .'<td style="border:1px solid #ddd;padding:10px;">'.$e($expiry).'</td>'
                .'</tr>';
        }

        $orderNo = $this->order->order_number ?? (string) $this->order->id;

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your Gift Card</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0;">
  <div style="max-width:600px; margin:20px auto; background:#fff; border:1px solid #ddd; border-radius:8px; padding:20px;">
    <h1 style="text-align:center; color:#2b6cb0;">Your gift card</h1>
    <p>Order <strong>#{$e((string) $orderNo)}</strong></p>
    <p>Product: {$e((string) ($this->order->product_name ?? ''))}</p>
    <table style="width:100%; border-collapse: collapse; margin-top:16px;">
      <thead>
        <tr>
          <th style="border:1px solid #ddd; padding:10px; background:#f2f2f2; text-align:left;">#</th>
          <th style="border:1px solid #ddd; padding:10px; background:#f2f2f2; text-align:left;">Code</th>
          <th style="border:1px solid #ddd; padding:10px; background:#f2f2f2; text-align:left;">PIN</th>
          <th style="border:1px solid #ddd; padding:10px; background:#f2f2f2; text-align:left;">Expiry</th>
        </tr>
      </thead>
      <tbody>
        {$rows}
      </tbody>
    </table>
  </div>
</body>
</html>
HTML;

        return $this->subject("Your Gift Card - Order #{$orderNo}")->html($html);
    }
}

