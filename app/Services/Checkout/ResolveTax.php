<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\Models\Order;
use App\Models\OrderTaxBreakdown;
use App\Models\ProductTaxAssignment;
use App\Models\TaxJurisdiction;
use App\Models\TaxRate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class ResolveTax
{
    public function resolveAndPersist(Order $order, ?CarbonImmutable $effectiveAt = null): void
    {
        $effectiveAt = $effectiveAt ?? CarbonImmutable::now();
        $effectiveDate = $effectiveAt->toDateString();

        $order->loadMissing(['items', 'billingSnapshot']);

        DB::transaction(function () use ($order, $effectiveDate): void {
            // Clear existing breakdowns for current items (re-resolve allowed while draft).
            $itemIds = $order->items->pluck('id')->all();
            if (!empty($itemIds)) {
                OrderTaxBreakdown::query()->whereIn('order_item_id', $itemIds)->delete();
            }

            $jurisdictionId = $this->resolveJurisdictionId($order);

            $orderTaxTotalMinor = 0;

            foreach ($order->items as $item) {
                $taxableMinor = max(0, (int) $item->line_subtotal_minor - (int) $item->line_discount_minor);

                $assignment = $this->resolveProductTaxAssignment((int) $item->product_id, $jurisdictionId, $effectiveDate);
                if ($assignment === null || $assignment->is_exempt) {
                    $item->forceFill([
                        'line_tax_minor' => 0,
                        'line_total_minor' => $taxableMinor,
                    ])->save();
                    continue;
                }

                $rates = $this->resolveRates(
                    (int) $assignment->hsn_sac_code_id,
                    $jurisdictionId,
                    $effectiveDate,
                    $assignment->override_rate_percent,
                );

                $lineTaxMinor = 0;
                foreach ($rates as $rateRow) {
                    $ratePercent = (string) $rateRow['rate_percent'];
                    $taxAmountMinor = (int) round($taxableMinor * ((float) $ratePercent / 100), 0, PHP_ROUND_HALF_UP);

                    OrderTaxBreakdown::create([
                        'order_item_id' => $item->id,
                        'tax_component' => $rateRow['component'],
                        'tax_rate_id' => $rateRow['tax_rate_id'],
                        'hsn_sac_code' => $rateRow['hsn_sac_code'],
                        'rate_percent' => $ratePercent,
                        'taxable_amount_minor' => $taxableMinor,
                        'tax_amount_minor' => $taxAmountMinor,
                        'currency' => $item->currency ?? $order->currency ?? 'INR',
                    ]);

                    $lineTaxMinor += $taxAmountMinor;
                }

                $item->forceFill([
                    'line_tax_minor' => $lineTaxMinor,
                    'line_total_minor' => $taxableMinor + $lineTaxMinor,
                ])->save();

                $orderTaxTotalMinor += $lineTaxMinor;
            }

            $order->forceFill([
                'tax_total_minor' => $orderTaxTotalMinor,
                'grand_total_minor' => max(0, (int) $order->subtotal_minor - (int) $order->discount_total_minor) + $orderTaxTotalMinor,
            ])->save();
        });
    }

    private function resolveJurisdictionId(Order $order): int
    {
        $countryCode = strtoupper((string) ($order->billingSnapshot?->country ?? 'IN'));
        $state = strtoupper((string) ($order->billingSnapshot?->state ?? ''));

        $query = TaxJurisdiction::query()
            ->where('is_active', true)
            ->where('country_code', $countryCode);

        if ($state !== '') {
            $match = (clone $query)
                ->where(function ($q) use ($state) {
                    $q->where('state_code', $state)
                        ->orWhere('code', $state)
                        ->orWhere('name', $state);
                })
                ->orderByRaw("CASE scope WHEN 'state' THEN 0 WHEN 'union_territory' THEN 1 ELSE 2 END")
                ->first();

            if ($match !== null) {
                return (int) $match->id;
            }
        }

        $fallback = $query->where('scope', 'country')->first();
        if ($fallback !== null) {
            return (int) $fallback->id;
        }

        $created = TaxJurisdiction::create([
            'code' => $countryCode,
            'name' => $countryCode,
            'scope' => 'country',
            'country_code' => $countryCode,
            'state_code' => null,
            'is_active' => true,
        ]);

        return (int) $created->id;
    }

    private function resolveProductTaxAssignment(int $productId, int $jurisdictionId, string $effectiveDate): ?ProductTaxAssignment
    {
        return ProductTaxAssignment::query()
            ->where('product_id', $productId)
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->where(function ($q) use ($effectiveDate) {
                $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveDate);
            })
            ->where(function ($q) use ($jurisdictionId) {
                $q->whereNull('jurisdiction_id')->orWhere('jurisdiction_id', $jurisdictionId);
            })
            ->orderByRaw('CASE WHEN jurisdiction_id IS NULL THEN 1 ELSE 0 END') // prefer specific jurisdiction
            ->orderByDesc('effective_from')
            ->first();
    }

    /**
     * @return array<int, array{component: string, rate_percent: string, tax_rate_id: int|null, hsn_sac_code: string|null}>
     */
    private function resolveRates(int $hsnSacCodeId, int $jurisdictionId, string $effectiveDate, $overrideRatePercent): array
    {
        if ($overrideRatePercent !== null) {
            // Override is treated as a single "other" component.
            return [[
                'component' => 'other',
                'rate_percent' => (string) $overrideRatePercent,
                'tax_rate_id' => null,
                'hsn_sac_code' => null,
            ]];
        }

        $rows = TaxRate::query()
            ->where('is_active', true)
            ->where('hsn_sac_code_id', $hsnSacCodeId)
            ->where('jurisdiction_id', $jurisdictionId)
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->where(function ($q) use ($effectiveDate) {
                $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $effectiveDate);
            })
            ->orderBy('component')
            ->get(['id', 'component', 'rate_percent']);

        if ($rows->isEmpty()) {
            return [];
        }

        $hsn = DB::table('hsn_sac_codes')->where('id', $hsnSacCodeId)->value('code');

        return $rows->map(fn ($r) => [
            'component' => (string) $r->component,
            'rate_percent' => (string) $r->rate_percent,
            'tax_rate_id' => (int) $r->id,
            'hsn_sac_code' => $hsn ? (string) $hsn : null,
        ])->all();
    }
}

