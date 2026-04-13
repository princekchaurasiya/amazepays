<?php

namespace App\Services\Order;

use App\Contracts\VoucherOrderResult;
use App\Models\Order;
use App\Models\Product;
use App\Services\Voucher\VoucherProviderFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Routes Vouchagram orders to Send (B2C) vs Pull (B2B / tenant) providers after payment.
 */
class VouchagramOrderFulfillmentService
{
    public function fulfillIfApplicable(Order $order): void
    {
        $product = Product::find($order->product_id);
        if (! $product || $product->source_provider !== 'vouchagram') {
            return;
        }

        try {
            if ($order->tenant_id) {
                $this->applyResult($order, VoucherProviderFactory::make('vouchagram_pull')->placeOrder($this->buildPullPayload($order, $product)));
            } else {
                $this->applyResult($order, VoucherProviderFactory::make('vouchagram_send')->placeOrder($this->buildSendPayload($order, $product)));
            }
        } catch (\Throwable $e) {
            Log::error('Vouchagram fulfillment failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            $order->update([
                'status' => 'failed',
                'order_status' => 'FAILED',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSendPayload(Order $order, Product $product): array
    {
        $name = trim((string) ($order->receiver_name ?? $order->billing_name ?? 'Customer'));
        $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: ['Customer'];
        $first = $parts[0] ?? 'Customer';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        $middle = '';
        if (count($parts) > 2) {
            $middle = implode(' ', array_slice($parts, 1, -1));
        }

        $email = (string) ($order->receiver_email ?? $order->billing_email ?? '');
        $mobile = preg_replace('/\D/', '', (string) ($order->receiver_mobile ?? $order->billing_tel ?? ''));

        return [
            'brand_product_code' => $product->sku,
            'sku' => $product->sku,
            'external_order_id' => $this->externalOrderId($order),
            'quantity' => min(10, max(1, (int) $order->quantity)),
            'denomination' => (string) $order->denomination,
            'customer_first_name' => $first,
            'customer_middle_name' => $middle,
            'customer_last_name' => $last,
            'email' => $email,
            'mobile' => $mobile,
            'service_type' => 'V',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPullPayload(Order $order, Product $product): array
    {
        return [
            'brand_product_code' => $product->sku,
            'sku' => $product->sku,
            'external_order_id' => $this->externalOrderId($order),
            'quantity' => min(10, max(1, (int) $order->quantity)),
            'denomination' => (string) $order->denomination,
        ];
    }

    private function externalOrderId(Order $order): string
    {
        $base = (string) ($order->order_number ?? 'AP-'.$order->id);

        return Str::limit($base, 50, '');
    }

    private function applyResult(Order $order, VoucherOrderResult $result): void
    {
        $raw = $result->raw;
        $ref = is_array($raw) ? ($raw['reference_num'] ?? $raw['external_order_id'] ?? null) : null;

        $payload = [
            'vouchagram_reference_num' => $result->providerOrderId ?? $ref,
            'vouchagram_external_order_id' => $this->externalOrderId($order),
            'vouchagram_voucher_data' => is_array($raw) ? $raw : ['raw' => $raw],
        ];

        if ($result->success) {
            if ($order->tenant_id) {
                $codes = $result->voucherCodes ?? [];
                $first = $codes[0] ?? [];
                $payload['voucher_code'] = $result->voucherCode ?? (is_array($first) ? ($first['code'] ?? null) : null);
                $payload['voucher_pin'] = $result->pin ?? (is_array($first) ? ($first['pin'] ?? null) : null);
                $expRaw = $result->expiryDate ?? (is_array($first) ? ($first['expiry'] ?? null) : null);
                if ($expRaw) {
                    try {
                        $payload['expiry_date'] = Carbon::parse($expRaw);
                    } catch (\Throwable) {
                        $payload['expiry_date'] = null;
                    }
                }
                $payload['status'] = 'completed';
                $payload['order_status'] = 'COMPLETE';
            } else {
                $payload['status'] = 'processing';
                $payload['order_status'] = 'PROCESSING';
            }
        } else {
            $payload['status'] = 'failed';
            $payload['order_status'] = 'FAILED';
        }

        $order->update($payload);
    }
}
