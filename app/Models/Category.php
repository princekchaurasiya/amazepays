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
        'parent_id', 'name', 'slug', 'order', 'thumbnail', 'accent_color',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Category $category) {
            Log::info('Creating category: ', ['name' => $category->name]);
            $category->slug = Str::slug($category->name);
            Log::info('Generated slug: ', ['slug' => $category->slug]);
        });

        static::updating(function (Category $category) {
            Log::info('Updating category: ', ['name' => $category->name]);
            $category->slug = Str::slug($category->name);
            Log::info('Updated slug: ', ['slug' => $category->slug]);
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id')
            ->withTimestamps();
    }
}
