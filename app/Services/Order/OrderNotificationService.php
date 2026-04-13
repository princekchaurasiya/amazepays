<?php

namespace App\Services\Order;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Handles all order-related notifications (email, SMS).
 * Called by jobs after order creation / fulfillment -- not inline in controllers.
 */
class OrderNotificationService
{
    /**
     * Send order confirmation to the purchaser.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        try {
            $user = $order->user;
            $email = $order->receiver_email ?? $user->email;

            Mail::send('emails.order-confirmation', [
                'order' => $order,
                'user' => $user,
            ], function ($message) use ($email, $order) {
                $message->to($email)
                    ->subject("Order Confirmed - #{$order->order_number}");
            });

            Log::info('Order confirmation sent', ['order_id' => $order->id, 'email' => $email]);
        } catch (\Throwable $e) {
            Log::error('Failed to send order confirmation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send voucher codes to the recipient.
     */
    public function sendVoucherDelivery(Order $order, array $voucherCodes): void
    {
        try {
            $recipientEmail = $order->gift_option === 'send_as_gift'
                ? $order->receiver_email
                : $order->user->email;

            Mail::send('emails.voucher-delivery', [
                'order' => $order,
                'voucherCodes' => $voucherCodes,
            ], function ($message) use ($recipientEmail, $order) {
                $message->to($recipientEmail)
                    ->subject("Your Gift Card - Order #{$order->order_number}");
            });

            Log::info('Voucher delivery sent', ['order_id' => $order->id]);
        } catch (\Throwable $e) {
            Log::error('Failed to send voucher delivery email', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send order failure notification to the purchaser.
     */
    public function sendOrderFailure(Order $order, string $reason): void
    {
        try {
            Mail::send('emails.order-failed', [
                'order' => $order,
                'reason' => $reason,
            ], function ($message) use ($order) {
                $message->to($order->user->email)
                    ->subject("Order Failed - #{$order->order_number}");
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send order failure notification', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
