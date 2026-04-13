<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Woohoo / catalog-synced taxonomy (table: synced_categories). */
class SyncedCategory extends Model
{
    protected $table = 'synced_categories';

    protected $fillable = [
        'name',
        'url',
        'description',
        'images',
        'subcategoriesCount',
        'subcategories',
    ];

    protected $casts = [
        'images' => 'array',
        'subcategoriesCount' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'synced_category_id');
    }
}
