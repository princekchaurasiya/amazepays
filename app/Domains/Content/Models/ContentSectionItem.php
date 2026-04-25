<?php

namespace App\Domains\Content\Models;

use App\Domains\Media\Models\MediaAsset;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentSectionItem extends Model
{
    use SoftDeletes;

    protected $table = 'content_section_items';

    protected $fillable = [
        'tenant_id',
        'content_section_id',
        'sort_order',
        'is_enabled',
        'start_at',
        'end_at',
        'priority',
        'title',
        'subtitle',
        'web_media_asset_id',
        'mobile_media_asset_id',
        'cta_text',
        'cta_type',
        'cta_value',
        'deeplink',
        'redirect_url',
        'product_id',
        'category_id',
        'brand_id',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(ContentSection::class, 'content_section_id');
    }

    public function webMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'web_media_asset_id');
    }

    public function mobileMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'mobile_media_asset_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}

