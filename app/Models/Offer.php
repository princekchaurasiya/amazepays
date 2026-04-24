<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Offer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'offers';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'name',
        'code',
        'discount_type',
        'discount_percent',
        'discount_amount_minor',
        'max_discount_amount_minor',
        'min_cart_amount_minor',
        'applies_to',
        'stackable',
        'status',
        'requires_code',
        'usage_limit_global',
        'usage_limit_per_user',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:3',
        'discount_amount_minor' => 'integer',
        'max_discount_amount_minor' => 'integer',
        'min_cart_amount_minor' => 'integer',
        'requires_code' => 'boolean',
        'usage_limit_global' => 'integer',
        'usage_limit_per_user' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(OfferUsage::class);
    }
}
