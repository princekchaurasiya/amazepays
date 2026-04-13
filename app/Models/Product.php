<?php

namespace App\Models;

use App\Helpers\ProductHelper;
use App\Helpers\ProductImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'sku',
        'name',
        'product_name',
        'description',
        'tnc',
        'product_id',
        'brand_id',
        'brandName',
        'source_provider',
        'last_synced_at',
        'sync_status',
        'price',
        'currency',
        'product_currency_code',
        'images',
        'custom_image',
        'custom_description',
        'how_to_redeem',
        'terms_and_conditions',
        'selling_price',
        'mrp',
        'denomination',
        'gst_rate',
        'discount_percentage',
        'CGST',
        'SGST',
        'IGST',
        'show_product',
        'priority',
        'display_order',
        'out_of_stock',
        'is_special_sku',
        'slug',
        'url',
        'synced_category_id',
        'sku_limits',
        'content_customized_at',
        'content_customized_by',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'canonical_url',
        'seo_score',
        'seo_edited_at',
        'seo_edited_by',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'CGST' => 'decimal:2',
        'SGST' => 'decimal:2',
        'IGST' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'mrp' => 'decimal:2',
        'denomination' => 'decimal:2',
        'images' => 'array',
        'show_product' => 'boolean',
        'out_of_stock' => 'boolean',
        'is_special_sku' => 'boolean',
        'last_synced_at' => 'datetime',
        'content_customized_at' => 'datetime',
        'seo_edited_at' => 'datetime',
        'seo_score' => 'integer',
    ];

    public function getPriceAttribute($value)
    {
        return ProductHelper::decodePrice($value);
    }

    public function getCurrencyAttribute($value)
    {
        return ProductHelper::decodeCurrency($value);
    }

    public function getImagesAttribute($value)
    {
        return ProductHelper::decodeImages($value);
    }

    public function getRangeAttribute()
    {
        return ProductHelper::extractRange(
            $this->price,
            $this->attributes['minPrice'] ?? null,
            $this->attributes['maxPrice'] ?? null
        );
    }

    /** Storefront / navigation categories (many-to-many via category_product). */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id')
            ->withTimestamps();
    }

    /** Synced Woo / catalog taxonomy (synced_categories). */
    public function syncedCategory(): BelongsTo
    {
        return $this->belongsTo(SyncedCategory::class, 'synced_category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'sku', 'sku');
    }

    public function orderSummaries(): HasManyThrough
    {
        return $this->hasManyThrough(OrderSummary::class, Order::class, 'sku', 'order_id', 'sku', 'id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(StorefrontBrand::class, 'brand_id');
    }

    public function productMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'product_id')->orderBy('sort_order');
    }

    public function hasCustomContent(): bool
    {
        if ($this->relationLoaded('productMedia') && $this->productMedia->isNotEmpty()) {
            return true;
        }
        if (! $this->relationLoaded('productMedia') && $this->productMedia()->exists()) {
            return true;
        }

        foreach (['custom_description', 'how_to_redeem', 'terms_and_conditions'] as $f) {
            $v = $this->getAttribute($f);
            if ($v !== null && $v !== '') {
                return true;
            }
        }

        if (! empty($this->custom_image) && $this->custom_image !== 'null') {
            return true;
        }

        return false;
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) (
            $this->attributes['product_name']
            ?? $this->attributes['name']
            ?? $this->attributes['brandName']
            ?? ''
        );
    }

    /**
     * Resolved primary image URL for storefront / listing views (wraps ProductImageHelper).
     */
    public function getDisplayImageUrlAttribute(): ?string
    {
        return ProductImageHelper::getProductImage($this);
    }

    public function getMinPrice()
    {
        return ProductHelper::getMinPrice($this->price, $this->attributes['minPrice'] ?? null);
    }

    public function getMaxPrice()
    {
        return ProductHelper::getMaxPrice($this->price, $this->attributes['maxPrice'] ?? null);
    }

    public function getPriceType()
    {
        return ProductHelper::getPriceType($this->price);
    }

    public function getDenominations()
    {
        return ProductHelper::getDenominations($this->price);
    }

    public function hasPriceRange()
    {
        return ProductHelper::hasPriceRange($this->price);
    }

    public function isSlabPricing()
    {
        return ProductHelper::isSlabPricing($this->price);
    }

    public function isRangePricing()
    {
        return ProductHelper::isRangePricing($this->price);
    }

    public function getFormattedPriceRange($currencySymbol = '₹')
    {
        return ProductHelper::getFormattedPriceRange($this->price, $currencySymbol);
    }

    public function scopeVisible($query)
    {
        return $query->where('show_product', true);
    }

    public function scopePriority($query)
    {
        return $query->where('priority', '>', 0)->orderBy('priority');
    }

    public function scopeDisplayOrder($query)
    {
        return $query->where('display_order', '>', 0)->orderBy('display_order');
    }
}
