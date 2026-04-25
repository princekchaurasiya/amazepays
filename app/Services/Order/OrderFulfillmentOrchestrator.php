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
 * Unified fulfillment trigger for paid orders.
 *
 * Centralizes provider selection and order state updates so wallet and
 * gateway-paid flows use the same fulfillment path.
 */
class OrderFulfillmentOrchestrator
{
    public function __construct(
        private VouchagramOrderFulfillmentService $vouchagramFulfillment,
        private ProviderOrderRecorder $providerOrderRecorder,
        private WoohooGiftCardPersister $giftCardPersister,
    ) {}

    public function fulfillPaidOrder(Order $order): void
    {
        $order->refresh();

        if (! $this->isFulfillableState($order)) {
            return;
        }

        $product = Product::find($order->product_id);
        if (! $product) {
            $order->update([
                'status' => 'failed',
                'order_status' => 'FAILED',
                'remarks' => trim((string) ($order->remarks ?? '').' | PRODUCT_NOT_FOUND'),
            ]);

            return;
        }

        // Keep existing proven Vouchagram logic intact; route all entry points here.
        if (in_array((string) $product->source_provider, ['vouchagram', 'vouchagram_send', 'vouchagram_pull'], true)) {
            $this->vouchagramFulfillment->fulfillIfApplicable($order);

            return;
        }

        try {
            $provider = VoucherProviderFactory::forProduct($product);
            $result = $provider->placeOrder($this->buildProviderPayload($order, $product));
            $this->applyProviderResult($order, $result, $provider->getName());
        } catch (\Throwable $e) {
            Log::error('Order fulfillment orchestration failed', [
                'order_id' => $order->id,
                'provider' => (string) $product->source_provider,
                'error' => $e->getMessage(),
            ]);

            $order->update([
                'status' => 'failed',
                'order_status' => 'FAILED',
                'remarks' => trim((string) ($order->remarks ?? '').' | FULFILLMENT_ERROR'),
            ]);
        }
    }

    /**
     * Reconcile non-terminal orders by querying provider order status.
     */
    public function reconcileOrderStatus(Order $order): bool
    {
        $order->refresh();
        $legacy = strtoupper((string) ($order->order_status ?? ''));
        if (in_array($legacy, ['COMPLETE', 'FAILED', 'CANCELLED'], true)) {
            return false;
        }

        $product = Product::find($order->product_id);
        if (! $product) {
            return false;
        }

        $provider = VoucherProviderFactory::forProduct($product);
        $providerName = $provider->getName();
        $providerOrderId = $this->resolveProviderOrderId($order, $providerName);
        if (! $providerOrderId) {
            return false;
        }

        try {
            $result = $provider->queryOrder($providerOrderId);
            $this->applyProviderResult($order, $result, $providerName);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Order reconciliation failed', [
                'order_id' => $order->id,
                'provider' => $providerName,
                'provider_order_id' => $providerOrderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isFulfillableState(Order $order): bool
    {
        $status = strtolower((string) ($order->status ?? ''));
        $legacy = strtoupper((string) ($order->order_status ?? ''));

        // prevent duplicate provider placement on repeated callbacks
        if (in_array($legacy, ['PROCESSING', 'COMPLETE', 'FAILED', 'CANCELLED'], true)) {
            return false;
        }

        return $status === 'paid' || $legacy === 'PAID';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProviderPayload(Order $order, Product $product): array
    {
        return [
            'order_id' => (string) ($order->order_number ?? $order->id),
            'external_order_id' => Str::limit((string) ($order->order_number ?? 'AP-'.$order->id), 50, ''),
            'sku' => (string) $product->sku,
            'quantity' => (int) max(1, (int) $order->quantity),
            'price' => (float) ($order->denomination ?? $order->unit_price ?? 0),
            'denomination' => (float) ($order->denomination ?? $order->unit_price ?? 0),
            'currency' => (string) ($order->currency ?: 'INR'),
            'customer_name' => (string) ($order->receiver_name ?: $order->billing_name ?: 'Customer'),
            'customer_email' => (string) ($order->receiver_email ?: $order->billing_email ?: ''),
            'customer_phone' => (string) ($order->receiver_mobile ?: $order->billing_tel ?: ''),
        ];
    }

    private function applyProviderResult(Order $order, VoucherOrderResult $result, string $providerName): void
    {
        $payload = [
            'remarks' => $result->error
                ? trim((string) ($order->remarks ?? '').' | '.$result->error)
                : $order->remarks,
        ];

        if ($result->providerOrderId) {
            if ($providerName === 'woohoo') {
                $payload['woohoo_order_id'] = $result->providerOrderId;
            } elseif (str_starts_with($providerName, 'vouchagram')) {
                $payload['vouchagram_reference_num'] = $result->providerOrderId;
            }
        }

        if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
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
        } elseif ($result->success && in_array($result->status, ['pending', 'processing'], true)) {
            $payload['status'] = 'processing';
            $payload['order_status'] = 'PROCESSING';
        } else {
            $payload['status'] = 'failed';
            $payload['order_status'] = 'FAILED';
        }

        $order->update($payload);

        if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
            $this->giftCardPersister->persistFromVoucherOrderResult($order->fresh(), $result, $providerName);
        }

        if ($providerName === 'woohoo' && $result->providerOrderId) {
            $rowStatus = 'processing';
            if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
                $rowStatus = 'succeeded';
            } elseif (! $result->success) {
                $rowStatus = 'failed';
            }
            $this->providerOrderRecorder->recordWoohooProviderId(
                $order->fresh(),
                $result->providerOrderId,
                $rowStatus,
            );
        }
    }

    private function resolveProviderOrderId(Order $order, string $providerName): ?string
    {
        if ($providerName === 'woohoo') {
            $fromRow = $order->providerOrders()
                ->where('provider', 'woohoo')
                ->whereNotNull('provider_order_id')
                ->orderByDesc('id')
                ->value('provider_order_id');

            return $fromRow ? (string) $fromRow : ($order->woohoo_order_id ?: null);
        }

        if (str_starts_with($providerName, 'vouchagram')) {
            return $order->vouchagram_reference_num
                ?: $order->vouchagram_external_order_id
                ?: null;
        }

        return $order->order_number ?: null;
    }
}
