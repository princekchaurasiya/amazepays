<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandCardTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'brand_id',
        'brand_name',
        'logo_url',
        'bg_color',
        'text_color',
        'accent_color',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'brand_id' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
