<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'carts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'session_token',
        'status',
        'currency',
        'cached_subtotal_minor',
        'cached_discount_total_minor',
        'cached_tax_total_minor',
        'cached_grand_total_minor',
    ];

    protected $casts = [
        'cached_subtotal_minor' => 'integer',
        'cached_discount_total_minor' => 'integer',
        'cached_tax_total_minor' => 'integer',
        'cached_grand_total_minor' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderByDesc('id');
    }
}
