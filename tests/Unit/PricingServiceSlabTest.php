<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\Pricing\PricingService;
use Tests\TestCase;

class PricingServiceSlabTest extends TestCase
{
    public function test_slab_accepts_denomination_when_json_denominations_are_integers(): void
    {
        $product = new Product;
        $product->setRawAttributes([
            'price' => json_encode(['type' => 'SLAB', 'denominations' => [500, 1000]]),
            'gst_rate' => 0,
        ]);

        $result = app(PricingService::class)->calculate($product, 1, 500.0);

        $this->assertEquals(500.0, $result->unitPrice);
    }

    public function test_slab_accepts_denomination_when_json_uses_values_alias(): void
    {
        $product = new Product;
        $product->setRawAttributes([
            'price' => json_encode(['type' => 'SLAB', 'values' => [500, 1000]]),
            'gst_rate' => 0,
        ]);

        $result = app(PricingService::class)->calculate($product, 1, 500.0);

        $this->assertEquals(500.0, $result->unitPrice);
    }

    public function test_slab_with_empty_denominations_falls_back_to_range_using_product_columns(): void
    {
        $product = new Product;
        $product->setRawAttributes([
            'price' => json_encode(['type' => 'SLAB', 'denominations' => []]),
            'minPrice' => 100,
            'maxPrice' => 500,
            'gst_rate' => 0,
        ]);

        $result = app(PricingService::class)->calculate($product, 1, 200.0);

        $this->assertEquals(200.0, $result->unitPrice);
    }
}
