<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use Illuminate\Support\Facades\Log;

/**
 * B2C — Send Voucher API (vouchers delivered via email/SMS to end customer).
 */
class VouchagramSendProvider implements VoucherProviderInterface
{
    public function __construct(
        private VouchagramService $vouchagram,
    ) {}

    public function getName(): string
    {
        return 'vouchagram_send';
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
            $cfg = config('vouchagram.send', []);
            $inner = [
                'BrandProductCode' => (string) ($orderData['brand_product_code'] ?? $orderData['sku'] ?? ''),
                'ExternalOrderId' => (string) ($orderData['external_order_id'] ?? ''),
                'Quantity' => (string) ($orderData['quantity'] ?? 1),
                'Denomination' => (string) ($orderData['denomination'] ?? ''),
                'CustomerFName' => (string) ($orderData['customer_first_name'] ?? 'Customer'),
                'CustomerMName' => (string) ($orderData['customer_middle_name'] ?? ''),
                'CustomerLName' => (string) ($orderData['customer_last_name'] ?? ''),
                'CommunicationMode' => (string) ($orderData['communication_mode'] ?? $cfg['communication_mode'] ?? '5'),
                'EmailTo' => (string) ($orderData['email'] ?? $orderData['email_to'] ?? ''),
                'EmailSubject' => (string) ($orderData['email_subject'] ?? ''),
                'MobileNo' => (string) ($orderData['mobile'] ?? $orderData['mobile_no'] ?? ''),
                'TemplateId' => (string) ($orderData['template_id'] ?? $cfg['template_id'] ?? '213'),
                'ServiceType' => (string) ($orderData['service_type'] ?? 'V'),
                'DynamicVars' => is_array($orderData['dynamic_vars'] ?? null) ? $orderData['dynamic_vars'] : [],
            ];

            $decoded = $this->vouchagram->postEncrypted(
                VouchagramService::MODE_SEND,
                '/sendvoucher',
                $inner
            );

            $ref = $decoded['reference_num'] ?? null;
            $externalOrderId = $decoded['external_order_id'] ?? $inner['ExternalOrderId'];

            return new VoucherOrderResult(
                success: true,
                status: 'pending',
                providerOrderId: $ref ?? $externalOrderId,
                raw: $decoded,
            );
        } catch (\Throwable $e) {
            Log::error('VouchagramSendProvider placeOrder failed', ['error' => $e->getMessage(), 'order' => $orderData]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            $inner = ['sv_ex_order_id' => $providerOrderId];
            $decoded = $this->vouchagram->postEncrypted(
                VouchagramService::MODE_SEND,
                '/checkvoucherstatus',
                $inner
            );

            $items = $this->extractVoucherItemsFromSendResponse($decoded);
            $first = $items[0] ?? [];

            $voucherStatus = strtolower((string) ($first['voucher_status'] ?? $first['email_status'] ?? 'pending'));

            $status = match ($voucherStatus) {
                'valid', 'success', 'delivered' => 'fulfilled',
                'pending', 'processing' => 'pending',
                'failed', 'invalid' => 'failed',
                default => 'pending',
            };

            return new VoucherOrderResult(
                success: true,
                status: $status,
                providerOrderId: $providerOrderId,
                voucherCode: $first['voucher_no'] ?? null,
                pin: $first['voucher_pin'] ?? null,
                expiryDate: $first['end_date'] ?? null,
                voucherCodes: $items,
                raw: $decoded,
            );
        } catch (\Throwable $e) {
            Log::error('VouchagramSendProvider queryOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        try {
            $brands = $this->vouchagram->getBrands(VouchagramService::MODE_SEND, '');
        } catch (\Throwable $e) {
            Log::error('VouchagramSendProvider fetchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }

        $out = [];
        foreach ($brands as $row) {
            if (! is_array($row)) {
                continue;
            }
            $code = (string) ($row['BrandProductCode'] ?? '');
            if ($code === '') {
                continue;
            }
            $denom = $row['denominationList'] ?? null;
            $min = $row['MinValue'] ?? null;
            $max = $row['MaxValue'] ?? null;
            $denomType = strtoupper((string) ($row['DenomType'] ?? 'F'));

            $priceType = 'FIXED';
            $priceField = null;
            if ($denomType === 'D' || ($denom === null && ($min !== null || $max !== null))) {
                $priceType = 'RANGE';
                $priceField = [
                    'type' => 'RANGE',
                    'min' => (float) ($min ?? 1),
                    'max' => (float) ($max ?? 100000),
                    'currency' => 'INR',
                ];
            } elseif ($denom !== null && $denom !== '') {
                $vals = array_map('trim', explode(',', (string) $denom));
                $priceField = [
                    'type' => 'SLAB',
                    'values' => array_map(fn ($v) => (float) $v, $vals),
                    'currency' => 'INR',
                ];
            } else {
                $priceField = [
                    'type' => 'RANGE',
                    'min' => (float) ($min ?? 1),
                    'max' => (float) ($max ?? 100000),
                    'currency' => 'INR',
                ];
            }

            $out[] = [
                'sku' => $code,
                'name' => (string) ($row['BrandName'] ?? $code),
                'description' => (string) ($row['Descriptions'] ?? ''),
                'tnc' => (string) ($row['tnc'] ?? ''),
                'price' => $priceField,
                'denomination' => is_numeric($denom) ? (float) $denom : null,
                'image' => $row['BrandImage'] ?? null,
                'image_url' => $row['BrandImage'] ?? null,
                'currency' => 'INR',
                'provider' => 'vouchagram',
            ];
        }

        return $out;
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return $this->fetchCatalog();
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        try {
            $stock = $this->vouchagram->getStock(VouchagramService::MODE_SEND, $productSku, '0');
            $avail = (int) ($stock['AvailableQuantity'] ?? 0);

            return new StockCheckResult(
                available: $avail >= $quantity,
                quantity: $avail,
            );
        } catch (\Throwable $e) {
            return new StockCheckResult(available: false, error: $e->getMessage());
        }
    }

    public function cancelOrder(string $providerOrderId): bool
    {
        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractVoucherItemsFromSendResponse(array $decoded): array
    {
        $items = [];
        foreach ($decoded['brand_details'] ?? [] as $bd) {
            if (! is_array($bd)) {
                continue;
            }
            foreach ($bd['items'] ?? [] as $it) {
                if (is_array($it)) {
                    $items[] = $it;
                }
            }
        }

        return $items;
    }
}
