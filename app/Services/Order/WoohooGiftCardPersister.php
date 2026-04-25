<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Contracts\VoucherOrderResult;
use App\Models\GiftCard;
use App\Models\GiftCardEvent;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Writes Woohoo-issued instruments to {@see GiftCard} + audit row on {@see GiftCardEvent}.
 * Legacy {@see Order::$cards} blob may still be updated elsewhere until reads migrate.
 */
final class WoohooGiftCardPersister
{
    /**
     * Build the same card shape the storefront expects (Woohoo-style keys), from normalized rows.
     *
     * @return list<array<string, mixed>>
     */
    public function displayCardsForOrder(Order $order): array
    {
        $rows = $order->giftCards()->orderBy('id')->get();

        $out = [];
        foreach ($rows as $gc) {
            $number = $gc->card_number_encrypted;
            $pin = $gc->card_pin_encrypted;
            if (($number === null || $number === '') && ($pin === null || $pin === '')) {
                continue;
            }

            $out[] = [
                'cardNumber' => $number !== null && $number !== '' ? (string) $number : '',
                'cardPin' => $pin !== null && $pin !== '' ? (string) $pin : '',
                'amount' => round(((int) $gc->face_value_minor) / 100, 2),
                'validity' => $gc->valid_until?->format('Y-m-d'),
                'gift_card_id' => $gc->id,
                'provider' => (string) $gc->provider,
                'card_last4' => $gc->card_last4,
            ];
        }

        return $out;
    }

    /**
     * Redacted gift-card rows for API order payloads (no PAN/PIN — only last4 and metadata).
     *
     * @return list<array<string, mixed>>
     */
    public function apiSummariesForOrder(Order $order): array
    {
        $rows = $order->giftCards()->orderBy('id')->get();
        $out = [];
        foreach ($rows as $gc) {
            $out[] = [
                'id' => $gc->id,
                'provider' => $gc->provider,
                'status' => $gc->status,
                'face_value' => round(((int) $gc->face_value_minor) / 100, 2),
                'currency' => $gc->currency,
                'card_last4' => $gc->card_last4,
                'valid_until' => $gc->valid_until?->format('Y-m-d'),
                'issued_at' => $gc->issued_at?->toIso8601String(),
            ];
        }

        return $out;
    }

    /**
     * Persist synchronous voucher codes from {@see VoucherOrderResult} (non-Woohoo providers).
     * Woohoo instruments stay on the card-array sync path; this is skipped when the provider name is woohoo.
     */
    public function persistFromVoucherOrderResult(Order $order, VoucherOrderResult $result, string $providerName): void
    {
        $providerEnum = $this->mapFulfillmentProviderToEnum($providerName);
        if ($providerEnum === null) {
            return;
        }

        if (! $result->success || ! in_array($result->status, ['fulfilled', 'confirmed'], true)) {
            return;
        }

        $orderItem = $order->items()->orderBy('id')->first();
        if (! $orderItem) {
            return;
        }

        $codes = $result->allCodes();
        if ($codes === []) {
            return;
        }

        $defaultFaceMinor = (int) ($orderItem->line_total_minor ?? $order->grand_total_minor ?? 0);
        if ($defaultFaceMinor < 1) {
            $defaultFaceMinor = 1;
        }

        $currency = (string) ($order->currency ?: 'INR');

        foreach ($codes as $index => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $number = trim((string) ($entry['code'] ?? $entry['voucher_code'] ?? $entry['cardNumber'] ?? $entry['cardnumber'] ?? ''));
            $pin = trim((string) ($entry['pin'] ?? $entry['voucher_pin'] ?? $entry['cardPin'] ?? $entry['cardpin'] ?? ''));

            if ($number === '' && $pin === '') {
                continue;
            }

            $expRaw = $entry['expiry'] ?? $entry['expiry_date'] ?? null;
            if (($expRaw === null || $expRaw === '') && $index === 0) {
                $expRaw = $result->expiryDate;
            }

            $externalId = $this->buildFulfillmentExternalId($providerEnum, $order, (int) $index, $number, $pin);
            $validUntil = $this->parseValidity($expRaw);
            $last4 = strlen($number) >= 4 ? substr($number, -4) : null;

            try {
                $giftCard = GiftCard::query()->updateOrCreate(
                    [
                        'provider' => $providerEnum,
                        'external_card_id' => $externalId,
                    ],
                    [
                        'tenant_id' => (int) $order->tenant_id,
                        'order_id' => (int) $order->id,
                        'order_item_id' => (int) $orderItem->id,
                        'provider_order_id' => null,
                        'product_id' => $orderItem->product_id ? (int) $orderItem->product_id : null,
                        'card_number_encrypted' => $number !== '' ? $number : null,
                        'card_pin_encrypted' => $pin !== '' ? $pin : null,
                        'card_last4' => $last4,
                        'face_value_minor' => $defaultFaceMinor,
                        'currency' => $currency,
                        'status' => 'active',
                        'valid_until' => $validUntil,
                        'issued_at' => now(),
                    ],
                );

                if ($giftCard->wasRecentlyCreated) {
                    GiftCardEvent::query()->create([
                        'gift_card_id' => (int) $giftCard->id,
                        'event_type' => 'issued',
                        'occurred_at' => now(),
                        'raw_payload' => json_encode(['provider' => $providerName, 'index' => $index]) ?: '{}',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Fulfillment gift card persist failed', [
                    'order_id' => $order->id,
                    'provider' => $providerEnum,
                    'external_card_id' => $externalId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>|array<int, mixed>  $cards
     */
    public function syncWoohooCards(Order $order, array $cards): void
    {
        $orderItem = $order->items()->orderBy('id')->first();
        if (! $orderItem) {
            return;
        }

        $providerOrder = $order->providerOrders()
            ->where('provider', 'woohoo')
            ->orderByDesc('id')
            ->first();

        $defaultFaceMinor = (int) ($orderItem->line_total_minor ?? $order->grand_total_minor ?? 0);
        if ($defaultFaceMinor < 1) {
            $defaultFaceMinor = 1;
        }

        $currency = (string) ($order->currency ?: 'INR');

        foreach ($cards as $index => $card) {
            if (! is_array($card)) {
                continue;
            }

            $number = trim((string) ($card['cardNumber'] ?? $card['cardnumber'] ?? ''));
            $pin = trim((string) ($card['cardPin'] ?? $card['cardpin'] ?? ''));

            if ($number === '' && $pin === '') {
                continue;
            }

            $externalId = $this->buildExternalId($order, (int) $index, $number, $pin);
            $amountMinor = $this->resolveFaceValueMinor($card, $defaultFaceMinor);
            $validUntil = $this->parseValidity($card['validity'] ?? $card['validUntil'] ?? null);
            $last4 = strlen($number) >= 4 ? substr($number, -4) : null;

            try {
                $giftCard = GiftCard::query()->updateOrCreate(
                    [
                        'provider' => 'woohoo',
                        'external_card_id' => $externalId,
                    ],
                    [
                        'tenant_id' => (int) $order->tenant_id,
                        'order_id' => (int) $order->id,
                        'order_item_id' => (int) $orderItem->id,
                        'provider_order_id' => $providerOrder?->id,
                        'product_id' => $orderItem->product_id ? (int) $orderItem->product_id : null,
                        'card_number_encrypted' => $number !== '' ? $number : null,
                        'card_pin_encrypted' => $pin !== '' ? $pin : null,
                        'card_last4' => $last4,
                        'face_value_minor' => $amountMinor,
                        'currency' => $currency,
                        'status' => 'active',
                        'valid_until' => $validUntil,
                        'issued_at' => now(),
                    ],
                );

                if ($giftCard->wasRecentlyCreated) {
                    GiftCardEvent::query()->create([
                        'gift_card_id' => (int) $giftCard->id,
                        'event_type' => 'issued',
                        'occurred_at' => now(),
                        'raw_payload' => json_encode($this->redactCardForLog($card)) ?: '{}',
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Woohoo gift card persist failed', [
                    'order_id' => $order->id,
                    'external_card_id' => $externalId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildExternalId(Order $order, int $index, string $number, string $pin): string
    {
        $basis = (string) $order->tenant_id.'|'.$order->id.'|'.$index.'|'.$number.'|'.$pin;

        return 'woo_'.substr(hash('sha256', $basis), 0, 40);
    }

    private function buildFulfillmentExternalId(string $providerEnum, Order $order, int $index, string $number, string $pin): string
    {
        $basis = $providerEnum.'|'.$order->tenant_id.'|'.$order->id.'|'.$index.'|'.$number.'|'.$pin;

        return 'fv_'.substr(hash('sha256', $basis), 0, 40);
    }

    private function mapFulfillmentProviderToEnum(string $providerName): ?string
    {
        if ($providerName === 'woohoo') {
            return null;
        }

        if ($providerName === 'value_design') {
            return 'vd';
        }

        if ($providerName === 'vouchagram') {
            return 'vouchagram_send';
        }

        $allowed = ['vouchagram_send', 'vouchagram_pull', 'vd', 'kgen', 'lysto', 'internal', 'ezpin', 'gyftrr'];
        if (in_array($providerName, $allowed, true)) {
            return $providerName;
        }

        return 'internal';
    }

    /**
     * @param  array<string, mixed>  $card
     */
    private function resolveFaceValueMinor(array $card, int $defaultMinor): int
    {
        $amount = $card['amount'] ?? $card['faceValue'] ?? null;
        if ($amount === null || $amount === '') {
            return $defaultMinor;
        }
        if (is_numeric($amount)) {
            return max(1, (int) round(((float) $amount) * 100));
        }

        return $defaultMinor;
    }

    private function parseValidity(mixed $raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $card
     * @return array<string, mixed>
     */
    private function redactCardForLog(array $card): array
    {
        $out = $card;
        foreach (['cardNumber', 'cardnumber', 'cardPin', 'cardpin'] as $key) {
            if (array_key_exists($key, $out)) {
                $out[$key] = '***';
            }
        }

        return $out;
    }
}
