<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BlockedMobile extends Model
{
    protected $fillable = [
        'mobile', 'reason', 'blocked_at', 'expires_at',
        'auto_blocked', 'blocked_by', 'block_count', 'permanent',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_blocked' => 'boolean',
        'permanent' => 'boolean',
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

        if ($record->permanent) {
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
                'blocked_at' => now(),
                'expires_at' => now()->addSeconds($durationSeconds),
                'auto_blocked' => $auto,
                'block_count' => DB::raw('block_count + 1'),
            ]
        );

        $record->refresh();

        $permanentAfter = config('security.threat_detection.permanent_block_after', 3);
        if ($record->block_count >= $permanentAfter) {
            $record->update(['permanent' => true, 'expires_at' => null]);
        }

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
        return $this->belongsTo(User::class, 'blocked_by');
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
