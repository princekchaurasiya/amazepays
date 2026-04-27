<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BlockedIp extends Model
{
    protected $fillable = [
        'tenant_id',
        'blocked_by_user_id',
        'ip_address',
        'cidr',
        'reason',
        'scope',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function isBlocked(string $ip): bool
    {
        // Check Redis cache first for performance
        if (Cache::has("blocked_ip:{$ip}")) {
            return true;
        }

        $record = static::where('ip_address', $ip)->first();

        if (! $record) {
            return false;
        }

        // Permanent block (Phase-3): expires_at NULL
        if ($record->expires_at === null) {
            Cache::put("blocked_ip:{$ip}", true, 3600);

            return true;
        }

        // Check expiry
        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        Cache::put("blocked_ip:{$ip}", true, $record->expires_at ? now()->diffInSeconds($record->expires_at) : 3600);

        return true;
    }

    public static function block(string $ip, string $reason, int $durationSeconds = 86400, bool $auto = true): static
    {
        $record = static::updateOrCreate(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'expires_at' => now()->addSeconds($durationSeconds),
            ]
        );

        // Cache the block
        Cache::put("blocked_ip:{$ip}", true, $durationSeconds);

        return $record;
    }

    public static function unblock(string $ip): bool
    {
        Cache::forget("blocked_ip:{$ip}");

        return static::where('ip_address', $ip)->delete() > 0;
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
