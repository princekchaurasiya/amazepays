<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Storefront / navigation categories (table: categories). */
class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'parent_id', 'name', 'slug', 'description', 'icon_url', 'banner_url',
        'depth', 'display_order', 'status', 'is_featured',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            $category->slug = Str::slug($category->name);
        });

        static::updating(function (Category $category) {
            $category->slug = Str::slug($category->name);
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id')
            ->withTimestamps();
    }
}
