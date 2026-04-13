<?php

namespace App\Services\Voucher;

use App\Contracts\StockCheckResult;
use App\Contracts\VoucherOrderResult;
use App\Contracts\VoucherProviderInterface;
use App\Support\ProviderResponseTranslator;
use Illuminate\Support\Facades\Log;

/**
 * B2B — Pull Voucher API (raw codes returned in API response).
 */
class VouchagramPullProvider implements VoucherProviderInterface
{
    public function __construct(
        private VouchagramService $vouchagram,
    ) {}

    public function getName(): string
    {
        return 'vouchagram_pull';
    }

    public function supportsIncrementalSync(): bool
    {
        return false;
    }

    public function isAsyncFulfillment(): bool
    {
        return false;
    }

    public function placeOrder(array $orderData): VoucherOrderResult
    {
        try {
            $inner = [
                'BrandProductCode' => (string) ($orderData['brand_product_code'] ?? $orderData['sku'] ?? ''),
                'ExternalOrderId' => (string) ($orderData['external_order_id'] ?? ''),
                'Quantity' => (int) ($orderData['quantity'] ?? 1),
                'Denomination' => (string) ($orderData['denomination'] ?? ''),
            ];

            $decoded = $this->vouchagram->postEncrypted(
                VouchagramService::MODE_PULL,
                '/pullvoucher',
                $inner
            );

            $codes = [];
            foreach ($decoded['PullVouchers'] ?? [] as $pv) {
                if (! is_array($pv)) {
                    continue;
                }
                foreach ($pv['Vouchers'] ?? [] as $v) {
                    if (! is_array($v)) {
                        continue;
                    }
                    $codes[] = [
                        'code' => $v['VoucherNo'] ?? $v['VoucherGCcode'] ?? '',
                        'pin' => $v['Voucherpin'] ?? '',
                        'expiry' => $v['EndDate'] ?? null,
                        'voucher_guid' => $v['VoucherGuid'] ?? null,
                        'value' => $v['Value'] ?? null,
                    ];
                }
            }

            $externalOut = (string) ($decoded['ExternalOrderIdOut'] ?? $inner['ExternalOrderId']);
            $resultType = strtoupper((string) ($decoded['ResultType'] ?? ''));

            $success = $resultType === 'SUCCESS' || ($decoded['ErrorCode'] ?? '') === '' || ($decoded['ErrorCode'] ?? '') === '0';

            $errorMsg = null;
            if (! $success) {
                $vc = (string) ($decoded['ErrorCode'] ?? '');
                $translated = ProviderResponseTranslator::fromVouchagramCode($vc !== '' && $vc !== '0' ? $vc : null);
                $errorMsg = $translated['message'];
                $providerText = (string) ($decoded['ErrorMessage'] ?? $decoded['Message'] ?? '');
                if ($providerText !== '' && ! str_contains($errorMsg, $providerText)) {
                    $errorMsg .= ' '.$providerText;
                }
            }

            return new VoucherOrderResult(
                success: $success,
                status: $success ? 'fulfilled' : 'failed',
                providerOrderId: $externalOut,
                voucherCode: $codes[0]['code'] ?? null,
                pin: $codes[0]['pin'] ?? null,
                expiryDate: isset($codes[0]['expiry']) ? (string) $codes[0]['expiry'] : null,
                voucherCodes: $codes,
                error: $errorMsg,
                raw: $decoded,
            );
        } catch (\Throwable $e) {
            Log::error('VouchagramPullProvider placeOrder failed', ['error' => $e->getMessage(), 'order' => $orderData]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function queryOrder(string $providerOrderId): VoucherOrderResult
    {
        try {
            $inner = ['sv_ex_order_id' => $providerOrderId];
            $decoded = $this->vouchagram->postEncrypted(
                VouchagramService::MODE_PULL,
                '/pullvoucher/checkstatus',
                $inner
            );

            $codes = [];
            foreach ($decoded['PullVouchers'] ?? [] as $pv) {
                if (! is_array($pv)) {
                    continue;
                }
                foreach ($pv['Vouchers'] ?? [] as $v) {
                    if (is_array($v)) {
                        $codes[] = [
                            'code' => $v['VoucherNo'] ?? $v['VoucherGCcode'] ?? '',
                            'pin' => $v['Voucherpin'] ?? '',
                            'expiry' => $v['EndDate'] ?? null,
                        ];
                    }
                }
            }

            $resultType = strtoupper((string) ($decoded['ResultType'] ?? ''));

            return new VoucherOrderResult(
                success: true,
                status: $resultType === 'SUCCESS' ? 'fulfilled' : 'pending',
                providerOrderId: $providerOrderId,
                voucherCode: $codes[0]['code'] ?? null,
                pin: $codes[0]['pin'] ?? null,
                voucherCodes: $codes,
                raw: $decoded,
            );
        } catch (\Throwable $e) {
            Log::error('VouchagramPullProvider queryOrder failed', ['error' => $e->getMessage()]);

            return new VoucherOrderResult(success: false, status: 'failed', error: $e->getMessage());
        }
    }

    public function fetchCatalog(): array
    {
        try {
            $brands = $this->vouchagram->getBrands(VouchagramService::MODE_PULL, '');
        } catch (\Throwable $e) {
            Log::error('VouchagramPullProvider fetchCatalog failed', ['error' => $e->getMessage()]);

            return [];
        }

        $send = new VouchagramSendProvider($this->vouchagram);

        return $send->fetchCatalog();
    }

    public function fetchCatalogUpdates(\DateTime $since): array
    {
        return $this->fetchCatalog();
    }

    public function checkStock(string $productSku, int $quantity = 1): StockCheckResult
    {
        try {
            $stock = $this->vouchagram->getStock(VouchagramService::MODE_PULL, $productSku, '0');
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
}
