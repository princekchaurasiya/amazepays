<?php

namespace App\Services\Voucher;

use App\Support\ProviderResponseTranslator;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

/**
 * Vouchagram Communication Engine — shared AES-256-CBC, JWT token, and HTTP helpers.
 *
 * @see docs/VOUCHAGRAM_SEND_API.md, docs/VOUCHAGRAM_PULL_API.md
 */
class VouchagramService
{
    public const MODE_SEND = 'send';

    public const MODE_PULL = 'pull';

    public function encryptPayload(mixed $data, string $mode): string
    {
        $payload = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new InvalidArgumentException('Invalid JSON payload for Vouchagram encryption.');
        }

        return $this->encryptAes256Cbc($payload, $mode);
    }

    public function decryptPayload(string $base64Cipher, string $mode): string
    {
        return $this->decryptAes256Cbc($base64Cipher, $mode);
    }

    /**
     * Decrypt API `data` field and decode JSON (or return raw string for JWT).
     */
    public function decryptDataField(string $base64Cipher, string $mode): mixed
    {
        $plain = $this->decryptPayload($base64Cipher, $mode);
        $plain = trim($plain, "\" \n\r\t");

        $json = json_decode($plain, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $plain;
    }

    public function getToken(string $mode): string
    {
        $cacheKey = 'vouchagram_token_'.$mode;
        $ttl = (int) config('vouchagram.token_cache_ttl', 1500);

        return Cache::remember($cacheKey, $ttl, function () use ($mode) {
            $cfg = $this->configForMode($mode);
            $url = $cfg['url'].'/gettoken';

            $response = $this->vouchagramHttp(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'username' => $cfg['username'],
                    'password' => $cfg['password'],
                ])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException('Vouchagram gettoken HTTP '.$response->status().': '.$response->body());
            }

            $body = $response->json();
            if (($body['code'] ?? '') !== '0000' && ($body['status'] ?? '') !== 'success') {
                throw new RuntimeException('Vouchagram gettoken failed: '.json_encode($body));
            }

            $encrypted = $body['data'] ?? '';
            if ($encrypted === '') {
                throw new RuntimeException('Vouchagram gettoken: empty data field.');
            }

            $decrypted = $this->decryptPayload($encrypted, $mode);
            $jwt = trim($decrypted, "\" \n\r\t");

            return $jwt;
        });
    }

    public function forgetToken(string $mode): void
    {
        Cache::forget('vouchagram_token_'.$mode);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBrands(string $mode, ?string $brandProductCode = null): array
    {
        $token = $this->getToken($mode);
        $cfg = $this->configForMode($mode);
        $url = $cfg['url'].'/getbrands';

        $response = $this->vouchagramHttp(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'token' => $token,
            ])
            ->post($url, [
                'BrandProductCode' => $brandProductCode ?? '',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Vouchagram getbrands HTTP '.$response->status().': '.$response->body());
        }

        $body = $response->json();
        $this->assertSuccessBody($body);

        $data = $body['data'] ?? '';
        $decoded = $this->decryptDataField($data, $mode);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getStock(string $mode, string $brandProductCode, string $denomination): array
    {
        $token = $this->getToken($mode);
        $cfg = $this->configForMode($mode);
        $url = $cfg['url'].'/getstock';

        $inner = [
            'BrandProductCode' => $brandProductCode,
            'Denomination' => (string) $denomination,
        ];
        $payload = $this->encryptPayload($inner, $mode);

        $response = $this->vouchagramHttp(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'token' => $token,
            ])
            ->post($url, ['payload' => $payload]);

        if (! $response->successful()) {
            throw new RuntimeException('Vouchagram getstock HTTP '.$response->status().': '.$response->body());
        }

        $body = $response->json();
        $this->assertSuccessBody($body);

        $decoded = $this->decryptDataField($body['data'] ?? '', $mode);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getStoreList(string $mode, ?string $brandProductCode = null, string|int $shop = ''): array
    {
        $token = $this->getToken($mode);
        $cfg = $this->configForMode($mode);
        $url = $cfg['url'].'/getstorelist';

        $response = $this->vouchagramHttp(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'token' => $token,
            ])
            ->post($url, [
                'BrandProductCode' => $brandProductCode ?? '',
                'shop' => (string) $shop,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Vouchagram getstorelist HTTP '.$response->status().': '.$response->body());
        }

        $body = $response->json();
        $this->assertSuccessBody($body);

        $data = $body['data'] ?? [];

        return is_array($data) ? $data : [];
    }

    public function postEncrypted(string $mode, string $pathSuffix, array $innerPayload): array
    {
        $token = $this->getToken($mode);
        $cfg = $this->configForMode($mode);
        $url = $cfg['url'].$pathSuffix;
        $payload = $this->encryptPayload($innerPayload, $mode);

        $response = $this->vouchagramHttp(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'token' => $token,
            ])
            ->post($url, ['payload' => $payload]);

        if (! $response->successful()) {
            Log::warning('Vouchagram API error', ['url' => $url, 'body' => $response->body()]);

            throw new RuntimeException('Vouchagram HTTP '.$response->status().': '.$response->body());
        }

        $body = $response->json();
        $this->assertSuccessBody($body);

        $decoded = $this->decryptDataField($body['data'] ?? '', $mode);

        return is_array($decoded) ? $decoded : ['raw' => $decoded];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function assertSuccessBody(array $body): void
    {
        $code = (string) ($body['code'] ?? '');
        if ($code !== '0000') {
            $translated = ProviderResponseTranslator::fromVouchagramCode($code);
            $detail = (string) ($body['desc'] ?? '');
            $message = $translated['message'];
            if ($detail !== '' && ! str_contains($message, $detail)) {
                $message .= ' ('.$detail.')';
            }
            throw new RuntimeException($message);
        }
    }

    /**
     * HTTP client for Vouchagram with a CA bundle when needed (fixes cURL 60 on Windows / XAMPP).
     */
    private function vouchagramHttp(int $timeoutSeconds): PendingRequest
    {
        $pending = Http::timeout($timeoutSeconds);
        $ca = $this->resolveCaBundlePath();
        if ($ca !== null) {
            $pending = $pending->withOptions(['verify' => $ca]);
        }

        return $pending;
    }

    /**
     * Resolve a PEM CA bundle for Guzzle verify=… (avoids cURL 60 when php.ini has no curl.cainfo).
     *
     * @return non-empty-string|null
     */
    private function resolveCaBundlePath(): ?string
    {
        $candidates = [];

        $fromConfig = config('vouchagram.http_ca_bundle');
        if (is_string($fromConfig) && $fromConfig !== '') {
            $candidates[] = $fromConfig;
        }

        foreach (['SSL_CERT_FILE', 'CURL_CA_BUNDLE'] as $envKey) {
            $v = getenv($envKey);
            if (is_string($v) && $v !== '') {
                $candidates[] = $v;
            }
        }

        foreach (['curl.cainfo', 'openssl.cafile'] as $iniKey) {
            $v = ini_get($iniKey);
            if (is_string($v) && $v !== '') {
                $candidates[] = $v;
            }
        }

        if (defined('PHP_BINARY') && is_string(PHP_BINARY) && PHP_BINARY !== '') {
            $candidates[] = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'cacert.pem';
        }

        $candidates[] = 'C:'.DIRECTORY_SEPARATOR.'xampp'.DIRECTORY_SEPARATOR.'php'.DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'.DIRECTORY_SEPARATOR.'cacert.pem';

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }
            $real = realpath($path);
            if ($real !== false && is_file($real) && is_readable($real)) {
                return $real;
            }
        }

        return null;
    }

    /**
     * @return array<string, string|null>
     */
    private function configForMode(string $mode): array
    {
        $key = $mode === self::MODE_PULL ? 'pull' : 'send';

        return config('vouchagram.'.$key, []);
    }

    private function encryptAes256Cbc(string $plain, string $mode): string
    {
        [$key, $iv] = $this->keyIvForMode($mode);
        $encrypted = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($encrypted === false) {
            throw new RuntimeException('Vouchagram AES-256-CBC encrypt failed.');
        }

        return base64_encode($encrypted);
    }

    private function decryptAes256Cbc(string $base64Cipher, string $mode): string
    {
        [$key, $iv] = $this->keyIvForMode($mode);
        $binary = base64_decode($base64Cipher, true);
        if ($binary === false) {
            throw new RuntimeException('Vouchagram: invalid base64 ciphertext.');
        }
        $plain = openssl_decrypt($binary, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new RuntimeException('Vouchagram AES-256-CBC decrypt failed.');
        }

        return $plain;
    }

    /**
     * @return array{0: string, 1: string} binary key (32) and IV (16)
     */
    private function keyIvForMode(string $mode): array
    {
        $cfg = $this->configForMode($mode);
        $keyRaw = (string) ($cfg['key'] ?? '');
        $ivRaw = (string) ($cfg['iv'] ?? '');

        if ($keyRaw === '' || $ivRaw === '') {
            throw new RuntimeException('Vouchagram key/iv not configured for mode '.$mode);
        }

        $keyBin = $this->normalizeKey32($keyRaw);
        $ivBin = $this->normalizeIv16($ivRaw);

        return [$keyBin, $ivBin];
    }

    private function normalizeKey32(string $key): string
    {
        if (strlen($key) === 32 && ctype_print($key)) {
            return $key;
        }
        if (strlen($key) === 64 && ctype_xdigit($key)) {
            $bin = hex2bin($key);

            return strlen($bin) === 32 ? $bin : hash('sha256', $bin, true);
        }
        if (strlen($key) === 32 && ctype_xdigit($key)) {
            $bin = hex2bin($key);

            return hash('sha256', $bin !== false ? $bin : $key, true);
        }

        return hash('sha256', $key, true);
    }

    private function normalizeIv16(string $iv): string
    {
        if (strlen($iv) >= 16) {
            return substr($iv, 0, 16);
        }
        if (strlen($iv) === 32 && ctype_xdigit($iv)) {
            $b = hex2bin($iv);

            return $b !== false && strlen($b) >= 16 ? substr($b, 0, 16) : hash('sha256', $iv, true);
        }

        return str_pad($iv, 16, "\0");
    }
}
