<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BlockedMobile extends Model
{
    protected $fillable = [
        'tenant_id',
        'blocked_by_user_id',
        'mobile',
        'reason',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public static function normalize(string $raw): string
    {
        $d = preg_replace('/\D+/', '', $raw);
        if (strlen($d) > 10) {
            if (str_starts_with($d, '91') && strlen($d) >= 12) {
                $d = substr($d, -10);
            } else {
                $d = substr($d, -10);
            }
        }

        return $d;
    }

    public static function isBlocked(string $mobile): bool
    {
        $normalized = self::normalize($mobile);
        if ($normalized === '' || strlen($normalized) !== 10) {
            return false;
        }

        if (Cache::has("blocked_mobile:{$normalized}")) {
            return true;
        }

        $record = static::where('mobile', $normalized)->first();

        if (! $record) {
            return false;
        }

        // Phase-3: expires_at NULL means permanent block.
        if ($record->expires_at === null) {
            Cache::put("blocked_mobile:{$normalized}", true, 3600);

            return true;
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            return false;
        }

        $ttl = $record->expires_at ? now()->diffInSeconds($record->expires_at) : 3600;
        Cache::put("blocked_mobile:{$normalized}", true, max(60, $ttl));

        return true;
    }

    public static function block(string $mobile, string $reason, int $durationSeconds = 86400, bool $auto = false): static
    {
        $normalized = self::normalize($mobile);

        $record = static::updateOrCreate(
            ['mobile' => $normalized],
            [
                'reason' => $reason,
                'expires_at' => now()->addSeconds($durationSeconds),
            ]
        );

        Cache::put("blocked_mobile:{$normalized}", true, $durationSeconds);

        return $record;
    }

    public static function unblock(string $mobile): bool
    {
        $normalized = self::normalize($mobile);
        Cache::forget("blocked_mobile:{$normalized}");

        return static::where('mobile', $normalized)->delete() > 0;
    }

    public function blockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
