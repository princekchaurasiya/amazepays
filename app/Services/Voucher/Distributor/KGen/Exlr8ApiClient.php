<?php

namespace App\Services\Voucher\Distributor\KGen;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class Exlr8ApiClient
{
    /**
     * @return array<string,mixed>
     */
    public function authenticate(): array
    {
        $response = $this->baseClient()
            ->post($this->url('/authenticate'), [
                'userId' => config('kgen.user_id'),
                'userSecret' => config('kgen.user_secret'),
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('EXLR8 authenticate failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function accessToken(): string
    {
        $cacheKey = 'exlr8.access_token';

        return (string) Cache::remember($cacheKey, now()->addMinutes(20), function () {
            $json = $this->authenticate();

            return (string) ($json['accessToken'] ?? $json['token'] ?? '');
        });
    }

    /**
     * @return array<string,mixed>
     */
    public function placeOrder(array $payload): array
    {
        $response = $this->client()->post($this->url('/order/place'), $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('EXLR8 place order failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function getOrders(array $query = []): array
    {
        $response = $this->client()->get($this->url('/orders'), $query);

        if (! $response->successful()) {
            throw new \RuntimeException('EXLR8 get orders failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function getProducts(array $query = []): array
    {
        $response = $this->client()->get($this->url('/products'), $query);

        if (! $response->successful()) {
            throw new \RuntimeException('EXLR8 get products failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    public function getWallet(array $query = []): array
    {
        $response = $this->client()->get($this->url('/wallet'), $query);

        if (! $response->successful()) {
            throw new \RuntimeException('EXLR8 get wallet failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    private function client(): PendingRequest
    {
        return $this->baseClient()->withHeaders([
            'X-Access-Token' => $this->accessToken(),
            'Content-Type' => 'application/json',
        ]);
    }

    private function baseClient(): PendingRequest
    {
        return Http::timeout(30)->acceptJson()->withHeaders([
            'userId' => (string) config('kgen.user_id'),
            'userSecret' => (string) config('kgen.user_secret'),
        ]);
    }

    private function url(string $path): string
    {
        return rtrim(config('kgen.base_url'), '/').rtrim(config('kgen.api_prefix'), '/').'/'.ltrim($path, '/');
    }
}

