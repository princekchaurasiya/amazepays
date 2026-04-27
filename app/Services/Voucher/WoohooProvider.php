<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use App\Services\Order\WoohooApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WoohooProvider implements VoucherProviderInterface
{
    public function __construct(array $credentials = [])
    {
        // Credentials are intentionally ignored: Woohoo access is configured via `config/woohoo.php`
        // and bearer token hydration from the `settings` table (see `AppServiceProvider`).
    }

    public function getName(): string
    {
        return 'woohoo';
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function isAsyncFulfillment(): bool
    {
        return true;
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        try {
            $host = (string) config('woohoo.host');
            $bearerToken = (string) config('woohoo.bearer_token');
            $clientSecret = (string) config('woohoo.client_secret');
            if ($host === '' || $bearerToken === '' || $clientSecret === '') {
                $missing = [];
                if ($host === '') {
                    $missing[] = 'WOOHOO_URL (config woohoo.host)';
                }
                if ($clientSecret === '') {
                    $missing[] = 'WOOHOO_CLIENT_SECRET';
                }
                if ($bearerToken === '') {
                    $missing[] = 'Woohoo bearer token (settings providers/woohoo.bearer_token)';
                }

                throw new \RuntimeException('Woohoo configuration missing: '.implode(', ', $missing));
            }

            $refno = (string) ($orderData['external_order_id'] ?? $orderData['order_id'] ?? '');
            if ($refno === '') {
                $refno = 'AP-'.Str::uuid()->toString();
            }

            $quantity = (int) max(1, (int) ($orderData['quantity'] ?? 1));
            $price = (float) ($orderData['price'] ?? $orderData['denomination'] ?? 0);
            $sku = (string) ($orderData['sku'] ?? '');
            if ($sku === '' || $price <= 0) {
                throw new \InvalidArgumentException('Invalid Woohoo order payload (missing sku/price)');
            }

            $createOrderPayload = [
                'address' => [
                    'firstname' => (string) ($orderData['customer_name'] ?? 'Customer'),
                    'lastname' => '',
                    'email' => (string) ($orderData['customer_email'] ?? ''),
                    'telephone' => $this->normalizePhone((string) ($orderData['customer_phone'] ?? '')),
                    'line1' => '-',
                    'line2' => '-',
                    'city' => '-',
                    'region' => '-',
                    'country' => 'IN',
                    'postcode' => '000000',
                    'languages' => 'en',
                    'billToThis' => true,
                ],
                'billing' => [
                    'firstname' => (string) ($orderData['customer_name'] ?? 'Customer'),
                    'lastname' => '',
                    'email' => (string) ($orderData['customer_email'] ?? ''),
                    'telephone' => $this->normalizePhone((string) ($orderData['customer_phone'] ?? '')),
                    'line1' => '-',
                    'line2' => '-',
                    'city' => '-',
                    'region' => '-',
                    'country' => 'IN',
                    'postcode' => '000000',
                    'languages' => 'en',
                    'billToThis' => true,
                ],
                'payments' => [[
                    'code' => 'svc',
                    'amount' => $price,
                ]],
                'refno' => Str::limit($refno, 50, ''),
                'products' => [[
                    'sku' => $sku,
                    'price' => $price,
                    'qty' => $quantity,
                    'currency' => 356,
                ]],
                'syncOnly' => $quantity > (int) env('SYNC_ONLY_THRESHOLD') ? false : true,
                'delivery_mode' => 'API',
            ];

            /** @var WoohooApiService $api */
            $api = app(WoohooApiService::class);
            $result = $api->createOrder($createOrderPayload);

            if (($result['success'] ?? false) === true) {
                $status = strtoupper((string) ($result['status'] ?? ''));
                $data = is_array($result['data'] ?? null) ? $result['data'] : [];

                $providerOrderId = isset($data['orderId']) ? (string) $data['orderId'] : null;

                // If Woohoo returned COMPLETE, fetch cards immediately so downstream can persist + notify.
                $voucherCodes = [];
                if ($status === 'COMPLETE' && $providerOrderId) {
                    try {
                        $cardsResp = $api->callCardActivation(['orderId' => $providerOrderId, 'status' => 'COMPLETE']);
                        if (is_object($cardsResp)) {
                            $cardsResp = json_decode(json_encode($cardsResp), true);
                        }
                        if (is_array($cardsResp)) {
                            $cards = $cardsResp['cards'] ?? [];
                            if (is_array($cards)) {
                                foreach ($cards as $card) {
                                    if (! is_array($card)) {
                                        continue;
                                    }
                                    $voucherCodes[] = [
                                        'code' => $card['cardNumber'] ?? $card['cardnumber'] ?? null,
                                        'pin' => $card['cardPin'] ?? $card['cardpin'] ?? null,
                                        'expiry' => $card['validity'] ?? $card['expiry'] ?? null,
                                    ];
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Woohoo card activation fetch failed after COMPLETE', [
                            'provider_order_id' => $providerOrderId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                return new VoucherOrderResult(
                    success: true,
                    status: $status === 'COMPLETE' ? 'fulfilled' : 'pending',
                    providerOrderId: $providerOrderId,
                    voucherCodes: $voucherCodes !== [] ? $voucherCodes : null,
                    voucherCode: $voucherCodes[0]['code'] ?? null,
                    pin: $voucherCodes[0]['pin'] ?? null,
                    expiryDate: $voucherCodes[0]['expiry'] ?? null,
                    raw: array_merge($result, ['cards' => $voucherCodes]),
                );
            }

            $message = (string) ($result['message'] ?? 'Woohoo order request failed');
            throw new \RuntimeException($message);
        } catch (\Exception $e) {
            Log::error('Woohoo placeOrder failed', ['error' => $e->getMessage(), 'order' => $orderData]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            /** @var WoohooApiService $api */
            $api = app(WoohooApiService::class);

            // Orchestrator stores Woohoo `orderId` as providerOrderId. Use card activation endpoint.
            $combined = $api->callCardActivation(['orderId' => $providerOrderId, 'status' => 'COMPLETE']);
            if (is_object($combined)) {
                $combined = json_decode(json_encode($combined), true);
            }
            if (! is_array($combined)) {
                $combined = [];
            }

            $cards = $combined['cards'] ?? [];
            if (! is_array($cards)) {
                $cards = [];
            }

            $codes = [];
            foreach ($cards as $card) {
                if (! is_array($card)) {
                    continue;
                }
                $codes[] = [
                    'code' => $card['cardNumber'] ?? $card['cardnumber'] ?? null,
                    'pin' => $card['cardPin'] ?? $card['cardpin'] ?? null,
                    'expiry' => $card['validity'] ?? $card['expiry'] ?? null,
                ];
            }

            if ($codes !== []) {
                return new VoucherOrderResult(
                    success: true,
                    status: 'fulfilled',
                    providerOrderId: $providerOrderId,
                    voucherCodes: $codes,
                    voucherCode: $codes[0]['code'] ?? null,
                    pin: $codes[0]['pin'] ?? null,
                    expiryDate: $codes[0]['expiry'] ?? null,
                    raw: $combined,
                );
            }

            return new VoucherOrderResult(
                success: true,
                status: 'pending',
                providerOrderId: $providerOrderId,
                raw: $combined,
            );
        } catch (\Exception $e) {
            Log::error('Woohoo queryOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        // Catalog sync is handled by dedicated Woohoo catalog services/jobs (Phase 3).
        return [];
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return $this->fetchCatalog();
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        return new StockCheckResult(available: true, quantity: max(0, $quantity));
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false; // Woohoo does not support order cancellation
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?: '';
        if ($digits === '') {
            return '+910000000000';
        }
        if (Str::startsWith($phone, '+')) {
            return '+'.$digits;
        }
        if (strlen($digits) === 10) {
            return '+91'.$digits;
        }

        return '+'.$digits;
    }
}
