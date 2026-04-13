<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use SoftDeletes;

    public static array $offerTypes = [
        'flat_discount', 'percentage_discount', 'cashback',
        'buy_x_get_y', 'first_purchase', 'category_specific',
        'brand_specific', 'product_specific',
    ];

    protected $fillable = [
        'tenant_id', 'name', 'code', 'type', 'discount_value', 'discount_percentage',
        'min_order_value', 'max_discount', 'usage_limit', 'per_user_limit',
        'is_active', 'is_public', 'start_date', 'end_date',
        'applicable_product_ids', 'applicable_category_ids', 'applicable_brand_ids',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'applicable_product_ids' => 'array',
        'applicable_category_ids' => 'array',
        'applicable_brand_ids' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(OfferUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopePublic($query)
    {
        return $query->active()->where('is_public', true);
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isUsageLimitReached(): bool
    {
        if (! $this->usage_limit) {
            return false;
        }

        return $this->usages()->count() >= $this->usage_limit;
    }

    public function isUsedByUser(int $userId): bool
    {
        return $this->usages()->where('user_id', $userId)->count() >= $this->per_user_limit;
    }

    public function calculateDiscount(float $orderAmount): float
    {
        if ($orderAmount < $this->min_order_value) {
            return 0;
        }

        $discount = match ($this->type) {
            'flat_discount' => $this->discount_value,
            'percentage_discount', 'cashback' => ($orderAmount * $this->discount_percentage) / 100,
            'first_purchase' => $this->discount_value > 0 ? $this->discount_value : ($orderAmount * $this->discount_percentage) / 100,
            default => 0,
        };

        if ($this->max_discount && $discount > $this->max_discount) {
            return $this->max_discount;
        }

        return round($discount, 2);
    }
}
