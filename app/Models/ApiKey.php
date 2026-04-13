<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'key', 'secret', 'type',
        'rate_limit', 'allowed_ips', 'scopes', 'is_active', 'last_used_at', 'expires_at',
    ];

    protected $hidden = ['secret'];

    protected $casts = [
        'allowed_ips' => 'array',
        'scopes' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'secret' => 'encrypted',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key + secret pair.
     * Returns ['key' => ..., 'secret' => ...] — secret is shown ONCE.
     */
    public static function generate(int $tenantId, string $name, string $type = 'reseller'): array
    {
        $rawKey = Str::random(32);
        $rawSecret = Str::random(64);

        $record = static::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'type' => $type,
            'key' => hash('sha256', $rawKey),
            'secret' => $rawSecret,
            'is_active' => true,
        ]);

        return [
            'id' => $record->id,
            'key' => $rawKey,
            'secret' => $rawSecret,
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    public function markUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
