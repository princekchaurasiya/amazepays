<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

final class ProductPricingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PricingService $pricing,
    ) {}

    public function show(Request $request, string $sku): ResponsePayload
    {
        $product = Product::query()
            ->forStorefrontCatalog()
            ->where('sku', $sku)
            ->first();

        if (! $product) {
            return $this->notFound();
        }

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
            'denomination' => ['nullable', 'numeric', 'min:1'],
            'offer_code' => ['nullable', 'string', 'max:64'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        $denomination = (float) ($validated['denomination'] ?? ($product->default_card_value ?? $product->selling_price ?? 0));

        $result = $this->pricing->calculate(
            product: $product,
            quantity: $quantity,
            denomination: $denomination,
            offerCode: $validated['offer_code'] ?? null,
            user: $request->user(),
            tenant: null,
        );

        return $this->ok('response.ok', [
            'sku' => $sku,
            'pricing' => $result->toArray(),
        ]);
    }
}

