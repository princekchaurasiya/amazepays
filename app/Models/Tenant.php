<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'type', 'status', 'business_name', 'gst_number', 'pan_number',
        'address', 'city', 'state', 'country_code', 'contact_email', 'contact_phone',
        'webhook_url', 'webhook_secret', 'credit_limit', 'current_balance',
        'margin_percentage', 'order_approval_required', 'maker_checker_threshold',
        'business_hours_only', 'business_hours', 'allowed_payment_methods', 'settings',
        'suspension_reason', 'suspended_at', 'contract_start', 'contract_end',
    ];

    protected $hidden = ['webhook_secret'];

    protected $casts = [
        'business_hours' => 'array',
        'allowed_payment_methods' => 'array',
        'settings' => 'array',
        'order_approval_required' => 'boolean',
        'business_hours_only' => 'boolean',
        'suspended_at' => 'datetime',
        'contract_start' => 'datetime',
        'contract_end' => 'datetime',
        'webhook_secret' => 'encrypted',
    ];

    // Relationships
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_users')
            ->withPivot('role', 'is_primary')
            ->withTimestamps();
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'tenant_products', 'tenant_id', 'product_id')
            ->withPivot('custom_price', 'margin_override', 'is_active')
            ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function ipWhitelists(): HasMany
    {
        return $this->hasMany(IpWhitelist::class);
    }

    public function walletLoadRequests(): HasMany
    {
        return $this->hasMany(WalletLoadRequest::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeB2b($query)
    {
        return $query->where('type', 'b2b');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isWithinBusinessHours(): bool
    {
        if (! $this->business_hours_only) {
            return true;
        }

        $hours = $this->business_hours ?? [];
        if (empty($hours)) {
            return true;
        }

        $now = now();
        $dayName = strtolower($now->format('l'));
        $dayHours = $hours[$dayName] ?? null;

        if (! $dayHours || ! ($dayHours['enabled'] ?? false)) {
            return false;
        }

        $start = $now->copy()->setTimeFromTimeString($dayHours['start'] ?? '09:00');
        $end = $now->copy()->setTimeFromTimeString($dayHours['end'] ?? '18:00');

        return $now->between($start, $end);
    }

    public function requiresMakerChecker(float $amount): bool
    {
        return $this->order_approval_required && $amount >= $this->maker_checker_threshold;
    }

    public function getDecryptedWebhookSecret(): ?string
    {
        return $this->webhook_secret;
    }
}
