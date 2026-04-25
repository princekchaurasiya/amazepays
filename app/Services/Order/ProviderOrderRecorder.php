<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\Order;
use App\Models\ProviderOrder;
use Illuminate\Support\Arr;

/**
 * Persists upstream voucher provider attempts on {@see ProviderOrder} (Phase 3 baseline).
 * Keeps {@see Order::$woohoo_order_id} in sync for legacy readers until all code uses this table.
 */
final class ProviderOrderRecorder
{
    /**
     * @param  array<string, mixed>  $wooResponse  Woohoo order payload (refno, orderId, status, …).
     */
    public function recordWoohooFromApiResponse(Order $order, array $wooResponse): ?ProviderOrder
    {
        $refno = (string) (Arr::get($wooResponse, 'refno') ?: $order->refno ?: '');
        if ($refno === '') {
            return null;
        }

        $wooOrderId = Arr::get($wooResponse, 'orderId');
        $wooOrderId = $wooOrderId !== null && $wooOrderId !== '' ? (string) $wooOrderId : null;

        $wooStatus = strtoupper((string) (Arr::get($wooResponse, 'status') ?? ''));
        $rowStatus = $this->mapWoohooStatusToRow($wooStatus);

        return $this->upsertWoohooRow($order, $refno, $wooOrderId, $rowStatus);
    }

    public function recordWoohooProviderId(Order $order, string $woohooOrderId, string $rowStatus = 'processing'): ?ProviderOrder
    {
        $refno = (string) ($order->refno ?: '');
        if ($refno === '') {
            $refno = 'woo-order-'.$order->id;
        }

        return $this->upsertWoohooRow($order, $refno, $woohooOrderId, $this->normalizeRowStatus($rowStatus));
    }

    private function upsertWoohooRow(Order $order, string $providerReference, ?string $wooOrderId, string $rowStatus): ?ProviderOrder
    {
        $orderItem = $order->items()->orderBy('id')->first();
        if (! $orderItem) {
            return null;
        }

        $row = ProviderOrder::query()
            ->where('order_id', $order->id)
            ->where('provider', 'woohoo')
            ->orderBy('id')
            ->first();

        if (! $row) {
            $row = new ProviderOrder([
                'tenant_id' => (int) $order->tenant_id,
                'order_id' => (int) $order->id,
                'provider' => 'woohoo',
            ]);
        }

        $terminal = in_array($rowStatus, ['succeeded', 'failed', 'refunded', 'cancelled'], true);

        $row->fill([
            'tenant_id' => (int) $order->tenant_id,
            'order_id' => (int) $order->id,
            'order_item_id' => (int) $orderItem->id,
            'provider_reference' => $providerReference,
            'provider_order_id' => $wooOrderId ?? $row->provider_order_id,
            'status' => $rowStatus,
            'initiated_at' => $row->initiated_at ?? now(),
            'completed_at' => $terminal ? now() : $row->completed_at,
        ]);
        $row->save();

        if ($wooOrderId !== null && $wooOrderId !== '') {
            $order->woohoo_order_id = $wooOrderId;
            $order->saveQuietly();
        }

        return $row;
    }

    private function mapWoohooStatusToRow(string $wooStatus): string
    {
        return match ($wooStatus) {
            'COMPLETE', 'COMPLETED', 'SUCCESS' => 'succeeded',
            'FAILED', 'FAILURE' => 'failed',
            'CANCELLED', 'CANCELED' => 'cancelled',
            default => 'processing',
        };
    }

    private function normalizeRowStatus(string $status): string
    {
        $s = strtolower(trim($status));

        if (in_array($s, ['succeeded', 'complete', 'completed', 'fulfilled', 'confirmed'], true)) {
            return 'succeeded';
        }
        if (in_array($s, ['failed', 'failure'], true)) {
            return 'failed';
        }
        if (in_array($s, ['cancelled', 'canceled'], true)) {
            return 'cancelled';
        }
        if (in_array($s, ['refunded'], true)) {
            return 'refunded';
        }

        return 'processing';
    }
}
