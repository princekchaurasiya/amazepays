<?php

namespace App\Services\Catalog;

use App\Helpers\ApiSignatureHelper;
use App\Services\Providers\WoohooBearerTokenStore;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class WoohooCatalogService
{
    public function fetchProductsForCategory(string $externalCategoryId): array
    {
        $externalCategoryId = trim($externalCategoryId);
        if ($externalCategoryId === '') {
            throw new \InvalidArgumentException('missing external category id');
        }

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (app(WoohooBearerTokenStore::class)->get() ?: '');

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            throw new \RuntimeException('Woohoo missing credentials/token');
        }

        $absApiUrl = "https://{$host}/rest/v3/catalog/categories/{$externalCategoryId}/products";
        $signature = ApiSignatureHelper::generateSignature('', 'get', $absApiUrl, $clientSecret);
        $dateAtClient = now()->toIso8601String();

        $resp = $this->sendSigned('GET', $absApiUrl, $bearerToken, $signature, $dateAtClient);

        if (! $resp->successful()) {
            throw new \RuntimeException('Woohoo fetch products failed: HTTP '.$resp->status());
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Woohoo invalid JSON for products');
        }

        $products = $json['products'] ?? null;
        if (! is_array($products)) {
            throw new \RuntimeException('Woohoo missing products array');
        }

        return $products;
    }

    public function fetchProductDetailsBySku(string $sku): array
    {
        $sku = trim($sku);
        if ($sku === '') {
            throw new \InvalidArgumentException('missing sku');
        }

        $host = (string) config('woohoo.host');
        $clientSecret = (string) config('woohoo.client_secret');
        $bearerToken = (string) (app(WoohooBearerTokenStore::class)->get() ?: '');

        if ($host === '' || $clientSecret === '' || $bearerToken === '') {
            throw new \RuntimeException('Woohoo missing credentials/token');
        }

        $absApiUrl = "https://{$host}/rest/v3/catalog/products/{$sku}";
        $signature = ApiSignatureHelper::generateSignature('', 'get', $absApiUrl, $clientSecret);
        $dateAtClient = now()->toIso8601String();

        $resp = $this->sendSigned('GET', $absApiUrl, $bearerToken, $signature, $dateAtClient);

        if (! $resp->successful()) {
            throw new \RuntimeException('Woohoo fetch product details failed: HTTP '.$resp->status());
        }

        $payload = $resp->json();
        if (! is_array($payload)) {
            throw new \RuntimeException('Woohoo invalid JSON for product details');
        }

        return $payload;
    }

    private function sendSigned(string $method, string $url, string $bearerToken, string $signature, string $dateAtClient): Response
    {
        return Http::acceptJson()
            ->withToken($bearerToken)
            ->withHeaders([
                'dateAtClient' => $dateAtClient,
                'signature' => $signature,
                'Accept' => '*/*',
                'User-Agent' => 'Amazepays/1.0 (+https://amazepays.in)',
            ])
            ->send($method, $url);
    }
}

