<?php

namespace App\Services\Voucher;

/** Maps raw getbrands API rows into catalog items for CatalogSyncService. */
final class VouchagramCatalogMapper
{
    /**
     * @param  array<int, mixed>  $brands
     * @return array<int, array<string, mixed>>
     */
    public static function mapBrandRowsToCatalogItems(array $brands): array
    {
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

            if ($denomType === 'D' || ($denom === null && ($min !== null || $max !== null))) {
                $priceField = [
                    'type' => 'RANGE',
                    'min' => (float) ($min ?? 1),
                    'max' => (float) ($max ?? 100000),
                    'currency' => 'INR',
                ];
            } elseif ($denom !== null && $denom !== '') {
                $vals = array_map('trim', explode(',', (string) $denom));
                $denominations = array_map(fn ($v) => (float) $v, $vals);
                $priceField = [
                    'type' => 'SLAB',
                    'denominations' => $denominations,
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
}
