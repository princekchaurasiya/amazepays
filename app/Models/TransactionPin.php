<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class TransactionPin extends Model
{
    protected $fillable = [
        'user_id', 'pin_hash', 'failed_attempts', 'locked_until', 'pin_changed_at',
    ];

    protected $hidden = ['pin_hash'];

    protected $casts = [
        'locked_until' => 'datetime',
        'pin_changed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verify(string $pin): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        if (Hash::check($pin, $this->pin_hash)) {
            $this->resetFailedAttempts();

            return true;
        }

        $this->recordFailedAttempt();

        return false;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function setPin(string $pin): void
    {
        $this->update([
            'pin_hash' => Hash::make($pin),
            'pin_changed_at' => now(),
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    private function recordFailedAttempt(): void
    {
        $this->increment('failed_attempts');
        $this->refresh();

        $maxAttempts = config('security.transaction_pin.max_failed_attempts', 5);

        if ($this->failed_attempts >= $maxAttempts) {
            $lockDuration = config('security.transaction_pin.lockout_duration', 1800);
            $this->update(['locked_until' => now()->addSeconds($lockDuration)]);
        }
    }

    private function resetFailedAttempts(): void
    {
        $this->update(['failed_attempts' => 0, 'locked_until' => null]);
    }
}
