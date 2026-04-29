<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'display_name',
        'email',
        'account_type',
        'status',
        'is_super_admin',
        'last_login_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'is_super_admin' => 'boolean',
        'last_login_at' => 'datetime',
        'is_blocked' => 'boolean',
        'can_transact' => 'boolean',
        'restricted_features' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function authIdentities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    /**
     * Phase-3: users table does not store mobile/email. These live in `user_auth_identities`.
     */
    public function scopeWhereMobile(Builder $query, string $mobile): Builder
    {
        return $query->whereHas('authIdentities', function (Builder $q) use ($mobile) {
            $q->where('type', 'mobile')->where('identifier', $mobile);
        });
    }

    public function scopeWhereMobileLike(Builder $query, string $fragment): Builder
    {
        return $query->whereHas('authIdentities', function (Builder $q) use ($fragment) {
            $q->where('type', 'mobile')->where('identifier', 'like', "%{$fragment}%");
        });
    }

    public function getMobileAttribute(): ?string
    {
        return $this->authIdentities()
            ->where('type', 'mobile')
            ->orderByDesc('is_primary')
            ->value('identifier');
    }

    public function getNameAttribute(): ?string
    {
        return $this->display_name;
    }

    /**
     * Legacy helper used across controllers/middleware for post-login redirects.
     */
    public function homeUrl(): string
    {
        if ($this->hasAnyRole(['super-admin', 'admin', 'finance'])) {
            return '/panel';
        }

        if ($this->hasAnyRole(['b2b-client', 'b2b-operator'])) {
            return '/panel/b2b';
        }

        return '/';
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(UserOtpCode::class);
    }

    public function contactChannels(): HasMany
    {
        return $this->hasMany(UserContactChannel::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    public function twoFactorSecret(): HasOne
    {
        return $this->hasOne(TwoFactorSecret::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function ips(): HasMany
    {
        return $this->hasMany(UserIp::class);
    }

    public function transactionPin(): HasOne
    {
        return $this->hasOne(TransactionPin::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Legacy singular cart accessor.
     * Phase-3 supports multiple carts; we treat the latest open cart as "the cart".
     */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class)->latestOfMany();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    protected static function booted(): void
    {
        static::created(function (User $user): void {
            // Create a default INR wallet for tenant-scoped users (skip platform super-admins).
            if (! $user->tenant_id) {
                return;
            }

            Wallet::firstOrCreate(
                ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'currency' => 'INR'],
                [
                    'status' => 'active',
                    'balance' => 0,
                    'is_frozen' => false,
                    'available_balance_minor' => 0,
                    'held_balance_minor' => 0,
                ]
            );
        });
    }
}
