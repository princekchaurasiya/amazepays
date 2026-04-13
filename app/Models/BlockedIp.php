<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class BlockedIp extends Model
{
    protected $fillable = [
        'ip_address', 'reason', 'blocked_at', 'expires_at',
        'auto_blocked', 'blocked_by', 'block_count', 'permanent',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_blocked' => 'boolean',
        'permanent' => 'boolean',
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

        // Permanent block
        if ($record->permanent) {
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
                'blocked_at' => now(),
                'expires_at' => now()->addSeconds($durationSeconds),
                'auto_blocked' => $auto,
                'block_count' => \DB::raw('block_count + 1'),
            ]
        );

        // Check if should be made permanent
        $permanentAfter = config('security.threat_detection.permanent_block_after', 3);
        if ($record->block_count >= $permanentAfter) {
            $record->update(['permanent' => true, 'expires_at' => null]);
        }

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
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function isExpired(): bool
    {
        if ($this->permanent) {
            return false;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('permanent', true)
                ->orWhere(function ($q2) {
                    $q2->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                });
        });
    }
}
