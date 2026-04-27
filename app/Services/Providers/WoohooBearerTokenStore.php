<?php

namespace App\Services\Providers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class WoohooBearerTokenStore
{
    private const SETTINGS_GROUP = 'providers';
    private const SETTINGS_KEY = 'woohoo.bearer_token';

    public function get(): ?string
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        $row = DB::table('settings')
            ->whereNull('tenant_id')
            ->where('group', self::SETTINGS_GROUP)
            ->where('key', self::SETTINGS_KEY)
            ->first(['value', 'value_type']);

        if (! $row || ! is_string($row->value) || $row->value === '' || ($row->value_type ?? null) !== 'encrypted') {
            return null;
        }

        try {
            return $this->encrypter()->decrypt($row->value);
        } catch (DecryptException) {
            return null;
        }
    }

    public function exists(): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        return DB::table('settings')
            ->whereNull('tenant_id')
            ->where('group', self::SETTINGS_GROUP)
            ->where('key', self::SETTINGS_KEY)
            ->where('value_type', 'encrypted')
            ->whereNotNull('value')
            ->where('value', '<>', '')
            ->exists();
    }

    public function updatedAt(): mixed
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        return DB::table('settings')
            ->whereNull('tenant_id')
            ->where('group', self::SETTINGS_GROUP)
            ->where('key', self::SETTINGS_KEY)
            ->value('updated_at');
    }

    public function put(string $token): void
    {
        if (! Schema::hasTable('settings')) {
            throw new \RuntimeException('settings table missing');
        }

        $token = trim($token);
        if ($token === '') {
            throw new \InvalidArgumentException('empty token');
        }

        $encrypted = $this->encrypter()->encrypt($token);

        DB::table('settings')->updateOrInsert(
            ['tenant_id' => null, 'group' => self::SETTINGS_GROUP, 'key' => self::SETTINGS_KEY],
            [
                'value' => $encrypted,
                'value_type' => 'encrypted',
                'is_public' => false,
                'description' => 'Woohoo bearer token (encrypted)',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private function encrypter(): Encrypter
    {
        $raw = (string) env('WOOHOO_TOKEN_ENCRYPTION_KEY', '');
        if ($raw === '') {
            throw new \RuntimeException('WOOHOO_TOKEN_ENCRYPTION_KEY missing in .env');
        }

        // Accept either a base64 key ("base64:...") or a plain 32-char key.
        $key = Str::startsWith($raw, 'base64:') ? base64_decode(substr($raw, 7), true) : $raw;
        if (! is_string($key) || $key === '' || strlen($key) !== 32) {
            throw new \RuntimeException('WOOHOO_TOKEN_ENCRYPTION_KEY must be 32 bytes (or base64 for 32 bytes)');
        }

        return new Encrypter($key, 'AES-256-CBC');
    }
}

