<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'desktop_image',
        'image_mobile',
        'video',
        'video_mobile',
        'small_header',
        'big_header',
        'cta_value',
        'cta_link',
        'priority',
        'slider_location',
        'status',
        'img_alt_tag',
        'display_on_page',
        'product_id',
        'category_id',
        'brand_id',
        'custom_url',
        'link_type',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** Slide category link (FK → categories / storefront). */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /** Slide brand link (FK → storefront_brands). */
    public function storefrontBrand(): BelongsTo
    {
        return $this->belongsTo(StorefrontBrand::class, 'brand_id');
    }
}
