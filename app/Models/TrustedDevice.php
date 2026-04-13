<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrustedDevice extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'device_name', 'device_type',
        'browser', 'os', 'ip_address', 'country_code',
        'is_trusted', 'trusted_at', 'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'trusted_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function isDeviceTrusted(int $userId, string $deviceId): bool
    {
        return static::where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->where('is_trusted', true)
            ->exists();
    }

    public static function recordDevice(int $userId, string $deviceId, array $info = []): static
    {
        $device = static::updateOrCreate(
            ['user_id' => $userId, 'device_id' => $deviceId],
            array_merge($info, ['last_seen_at' => now()])
        );

        return $device;
    }

    public function trust(): void
    {
        $this->update([
            'is_trusted' => true,
            'trusted_at' => now(),
        ]);
    }

    public function revoke(): void
    {
        $this->update(['is_trusted' => false, 'trusted_at' => null]);
    }
}
