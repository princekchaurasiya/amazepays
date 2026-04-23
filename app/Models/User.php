<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile',
        'referral_code',

        'billing_zip', 'billing_address', 'billing_city', 'billing_state',
        'billing_country', 'billing_address_two',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relationships
     */

    // Wallet relationship
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Wallet transactions through wallet
    public function walletTransactions()
    {
        return $this->hasManyThrough(WalletTransaction::class, Wallet::class);
    }

    /** Storefront / voucher orders */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function cartItems()
    {
        return $this->hasManyThrough(CartItem::class, Cart::class);
    }

    // KGen Orders
    public function kgenOrders()
    {
        return $this->hasMany(KGenOrder::class);
    }

    // CC Avenue Payments
    public function ccAvenuePayments()
    {
        return $this->hasMany(CcAvenuePayment::class);
    }

    // Unlimit Payments
    public function unlimitPayments()
    {
        return $this->hasMany(UnlimitPayment::class);
    }

    // All payments (union of both payment gateways)
    public function payments()
    {
        // This returns a collection, not a relationship
        // Use this in controllers when you need all payments
        return $this->ccAvenuePayments->merge($this->unlimitPayments);
    }

    // OTPs
    public function otps()
    {
        return $this->hasMany(Otp::class);
    }

    // Email verification codes
    public function emailVerificationCodes()
    {
        return $this->hasMany(EmailVerificationCode::class);
    }

    // User IPs
    public function userIps()
    {
        return $this->hasMany(UserIp::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    /** @alias tenants() — used by middleware naming convention */
    public function tenantUsers(): BelongsToMany
    {
        return $this->tenants();
    }

    public function currentTenant(): ?Tenant
    {
        $primary = $this->tenants()->wherePivot('is_primary', true)->first();

        return $primary ?? $this->tenants()->first();
    }

    public function currentTenantId(): ?int
    {
        return $this->currentTenant()?->id;
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Post-login destination: storefront (/) or admin panel (/panel) per role.
     */
    public function homeUrl(): string
    {
        try {
            if ($this->hasAnyRole(['super-admin', 'admin', 'finance', 'b2b-client', 'b2b-operator'])) {
                return route('admin.dashboard');
            }
        } catch (RoleDoesNotExist) {
            // During early bootstrap/tests roles can be absent; default safely to storefront.
        }

        return route('home');
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->wallet()->create();
        });
    }
}
