<?php

namespace App\Services\Order;

use App\Contracts\VoucherOrderResult;
use App\Models\Order;
use App\Models\Product;
use App\Services\Order\OrderNotificationService;
use App\Services\Voucher\VoucherProviderFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
        private OrderNotificationService $notifications,
    ) {}

    public function fulfillPaidOrder(Order $order): void
    {
        $order->refresh();

        if (! $this->isFulfillableState($order)) {
            return;
        }

        $orderItem = $order->items()->orderBy('id')->first();
        $productId = $order->product_id ?? $orderItem?->product_id;
        $product = $productId ? Product::find($productId) : null;
        if (! $product) {
            $order->update($this->schemaSafeOrderUpdate([
                'status' => 'failed',
                'remarks' => trim((string) ($order->remarks ?? '').' | PRODUCT_NOT_FOUND'),
            ]));

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

            $order->update($this->schemaSafeOrderUpdate([
                'status' => 'failed',
                'remarks' => trim((string) ($order->remarks ?? '').' | FULFILLMENT_ERROR'),
            ]));
        }
    }

    /**
     * Reconcile non-terminal orders by querying provider order status.
     */
    public function reconcileOrderStatus(Order $order): bool
    {
        $order->refresh();
        $status = strtolower((string) ($order->status ?? ''));
        if (in_array($status, ['fulfilled', 'failed', 'cancelled', 'refunded'], true)) {
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

        // prevent duplicate provider placement on repeated callbacks
        if (in_array($status, ['processing', 'fulfilled', 'failed', 'cancelled', 'refunded'], true)) {
            return false;
        }

        return $status === 'paid';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProviderPayload(Order $order, Product $product): array
    {
        $firstItem = $order->items()->orderBy('id')->first();
        $quantity = (int) max(1, (int) (($firstItem?->quantity ?? 1)));
        $denomination = $firstItem && $firstItem->unit_amount_minor !== null
            ? ((int) $firstItem->unit_amount_minor) / 100
            : (float) ($order->grand_total_minor ? ((int) $order->grand_total_minor) / 100 : 0);

        $billing = $order->billingSnapshot()->first();
        $customerName = (string) ($billing?->full_name ?: 'Customer');
        $customerEmail = (string) ($billing?->email ?: '');
        $customerPhone = (string) ($billing?->phone ?: '');

        if ($customerEmail === '' || ! filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Missing/invalid billing email. Please update your profile and retry checkout.');
        }
        if ($customerPhone === '') {
            throw new \RuntimeException('Missing billing phone. Please update your profile and retry checkout.');
        }

        return [
            'order_id' => (string) ($order->order_number ?? $order->id),
            'external_order_id' => Str::limit((string) ($order->order_number ?? 'AP-'.$order->id), 50, ''),
            'sku' => (string) $product->sku,
            'quantity' => $quantity,
            'price' => $denomination,
            'denomination' => $denomination,
            'currency' => (string) ($order->currency ?: 'INR'),
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
        ];
    }

    private function applyProviderResult(Order $order, VoucherOrderResult $result, string $providerName): void
    {
        $payload = [];
        if (Schema::hasColumn('orders', 'remarks')) {
            $payload['remarks'] = $result->error
                ? trim((string) ($order->remarks ?? '').' | '.$result->error)
                : $order->remarks;
        }

        if ($result->providerOrderId) {
            if ($providerName === 'woohoo') {
                if (Schema::hasColumn('orders', 'woohoo_order_id')) {
                    $payload['woohoo_order_id'] = $result->providerOrderId;
                }
            } elseif (str_starts_with($providerName, 'vouchagram')) {
                if (Schema::hasColumn('orders', 'vouchagram_reference_num')) {
                    $payload['vouchagram_reference_num'] = $result->providerOrderId;
                }
            }
        }

        if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
            $codes = $result->voucherCodes ?? [];
            $first = $codes[0] ?? [];

            if (Schema::hasColumn('orders', 'voucher_code')) {
                $payload['voucher_code'] = $result->voucherCode ?? (is_array($first) ? ($first['code'] ?? null) : null);
            }
            if (Schema::hasColumn('orders', 'voucher_pin')) {
                $payload['voucher_pin'] = $result->pin ?? (is_array($first) ? ($first['pin'] ?? null) : null);
            }

            $expRaw = $result->expiryDate ?? (is_array($first) ? ($first['expiry'] ?? null) : null);
            if ($expRaw && Schema::hasColumn('orders', 'expiry_date')) {
                try {
                    $payload['expiry_date'] = Carbon::parse($expRaw);
                } catch (\Throwable) {
                    $payload['expiry_date'] = null;
                }
            }

            // Phase-3 `orders.status` enum uses `fulfilled` (not `completed`).
            $payload['status'] = 'fulfilled';
        } elseif ($result->success && in_array($result->status, ['pending', 'processing'], true)) {
            $payload['status'] = 'processing';
        } else {
            $payload['status'] = 'failed';
        }

        $order->update($this->schemaSafeOrderUpdate($payload));

        if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
            // Persist instruments.
            if ($providerName === 'woohoo') {
                $codes = $result->voucherCodes ?? [];
                if (is_array($codes) && $codes !== []) {
                    $cards = [];
                    foreach ($codes as $c) {
                        if (! is_array($c)) {
                            continue;
                        }
                        $cards[] = [
                            'cardNumber' => $c['code'] ?? null,
                            'cardPin' => $c['pin'] ?? null,
                            'validity' => $c['expiry'] ?? null,
                        ];
                    }
                    if ($cards !== []) {
                        $this->giftCardPersister->syncWoohooCards($order->fresh(), $cards);
                    }
                }
            } else {
                $this->giftCardPersister->persistFromVoucherOrderResult($order->fresh(), $result, $providerName);
            }
        }

        // Send notifications once fulfilled.
        if ($result->success && in_array($result->status, ['fulfilled', 'confirmed'], true)) {
            $fresh = $order->fresh(['user', 'giftCards']);
            $this->notifications->sendOrderConfirmation($fresh);
            $this->notifications->sendVoucherDelivery($fresh, $this->giftCardPersister->displayCardsForOrder($fresh));
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

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function schemaSafeOrderUpdate(array $payload): array
    {
        $out = [];
        foreach ($payload as $key => $value) {
            if ($key === 'status' || Schema::hasColumn('orders', $key)) {
                $out[$key] = $value;
            }
        }

        return $out;
    }
}
