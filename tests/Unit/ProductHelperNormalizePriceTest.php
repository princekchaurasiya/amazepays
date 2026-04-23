<?php

namespace Tests\Unit;

use App\Helpers\ProductHelper;
use Tests\TestCase;

class ProductHelperNormalizePriceTest extends TestCase
{
    public function test_normalize_merges_slab_values_alias_into_denominations(): void
    {
        $normalized = ProductHelper::normalizePriceArray([
            'type' => 'SLAB',
            'values' => [500, 1000],
            'currency' => 'INR',
        ]);

        $this->assertSame([500.0, 1000.0], $normalized['denominations']);
    }

    public function test_normalize_does_not_overwrite_existing_denominations(): void
    {
        $normalized = ProductHelper::normalizePriceArray([
            'type' => 'SLAB',
            'denominations' => [100],
            'values' => [500],
        ]);

        $this->assertSame([100.0], $normalized['denominations']);
    }

    public function test_normalize_merges_values_inside_cpg_entries(): void
    {
        $normalized = ProductHelper::normalizePriceArray([
            'cpg' => [
                [
                    'type' => 'SLAB',
                    'min' => 1,
                    'max' => 10000,
                    'values' => [250, 500],
                ],
            ],
        ]);

        $this->assertSame([250.0, 500.0], $normalized['cpg'][0]['denominations']);
    }

    public function test_extract_range_returns_denominations_for_slab_with_values_only(): void
    {
        $range = ProductHelper::extractRange([
            'type' => 'SLAB',
            'values' => [250, 500],
            'currency' => 'INR',
        ]);

        $this->assertSame('SLAB', $range['type']);
        $this->assertSame([250.0, 500.0], $range['denominations']);
        $this->assertSame(250.0, $range['min']);
        $this->assertSame(500.0, $range['max']);
    }

    public function test_get_denominations_includes_values_alias(): void
    {
        $denoms = ProductHelper::getDenominations([
            'type' => 'SLAB',
            'values' => [99],
        ]);

        $this->assertSame([99.0], $denoms);
    }
}
