<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Homepage / catalog brands shown to customers (table: storefront_brands).
 */
class StorefrontBrand extends Model
{
    use HasFactory;

    protected $table = 'storefront_brands';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'canonical_url',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
