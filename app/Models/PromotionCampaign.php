<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromotionCampaign extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'promotion_campaigns';

    protected $fillable = [
        'tenant_id',
        'created_by_user_id',
        'name',
        'slug',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'display_priority',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'display_priority' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class, 'campaign_id');
    }

    public function bankOffers(): HasMany
    {
        return $this->hasMany(BankOffer::class, 'campaign_id');
    }
}

