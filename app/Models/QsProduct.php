<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\ProductHelper;

class QsProduct extends Model
{

    use HasFactory;
    protected $table = 'qs_products';
    protected $fillable = [
        'discount_percentage',
        'CGST',
        'SGST',
        'IGST',
        'amazepay_how_to_redeem',
        'amazepay_t_and_c',
        'amazepay_product_description',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'CGST' => 'decimal:2',
        'SGST' => 'decimal:2',
        'IGST' => 'decimal:2',
        'images' => 'array', // Keep as array cast, but accessor ensures it's always an array
        'show_product' => 'boolean',
    ];

    /**
     * Accessor methods to safely decode JSON fields
     * These handle cases where data might already be decoded or still be JSON strings
     */
    
    /**
     * Get the price attribute - automatically decode if it's a JSON string
     * Uses ProductHelper for consistent decoding
     */
    public function getPriceAttribute($value)
    {
        return ProductHelper::decodePrice($value);
    }

    /**
     * Get the currency attribute - automatically decode if it's a JSON string
     * Uses ProductHelper for consistent decoding
     */
    public function getCurrencyAttribute($value)
    {
        return ProductHelper::decodeCurrency($value);
    }

    /**
     * Get the images attribute - ensure it's always an array
     * Uses ProductHelper for consistent decoding
     */
    public function getImagesAttribute($value)
    {
        return ProductHelper::decodeImages($value);
    }

    /**
     * Get the range attribute - extracts min/max range from price structure
     * Returns an array with 'min', 'max', 'type', and 'denominations' if available
     * Handles both RANGE and SLAB price types
     * Uses ProductHelper for consistent extraction
     */
    public function getRangeAttribute()
    {
        return ProductHelper::extractRange(
            $this->price,
            $this->attributes['minPrice'] ?? null,
            $this->attributes['maxPrice'] ?? null
        );
    }

    /**
     * Relationships
     */

    // Many-to-many relationship with AmazepayCategory
    public function amazepayCategories()
    {
        return $this->belongsToMany(AmazepayCategory::class, 'amazepay_category_product', 'product_id', 'amazepay_category_id');
    }

    // Orders for this product
    public function orders()
    {
        return $this->hasMany(QsOrder::class, 'sku', 'sku');
    }

    // Brand relationship (if brand_id exists)
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    // QS Category relationship (if amazepay_category_id exists)
    public function qsCategory()
    {
        return $this->belongsTo(QsCategory::class, 'amazepay_category_id');
    }

    /**
     * Helper methods for price and range
     * These delegate to ProductHelper for consistency
     */
    
    /**
     * Get the minimum price from range or price structure
     */
    public function getMinPrice()
    {
        return ProductHelper::getMinPrice($this->price, $this->attributes['minPrice'] ?? null);
    }

    /**
     * Get the maximum price from range or price structure
     */
    public function getMaxPrice()
    {
        return ProductHelper::getMaxPrice($this->price, $this->attributes['maxPrice'] ?? null);
    }

    /**
     * Get price type (RANGE, SLAB, etc.) from price structure
     */
    public function getPriceType()
    {
        return ProductHelper::getPriceType($this->price);
    }

    /**
     * Get available denominations from price structure
     */
    public function getDenominations()
    {
        return ProductHelper::getDenominations($this->price);
    }

    /**
     * Check if product has a price range
     */
    public function hasPriceRange()
    {
        return ProductHelper::hasPriceRange($this->price);
    }

    /**
     * Check if product uses SLAB pricing
     */
    public function isSlabPricing()
    {
        return ProductHelper::isSlabPricing($this->price);
    }

    /**
     * Check if product uses RANGE pricing
     */
    public function isRangePricing()
    {
        return ProductHelper::isRangePricing($this->price);
    }

    /**
     * Get formatted price display string
     * Examples: "₹100 - ₹10,000" for RANGE, "₹500, ₹1000" for SLAB
     */
    public function getFormattedPriceRange($currencySymbol = '₹')
    {
        return ProductHelper::getFormattedPriceRange($this->price, $currencySymbol);
    }

    /**
     * Scopes
     */

    // Scope for visible products
    public function scopeVisible($query)
    {
        return $query->where('show_product', true);
    }

    // Scope for priority products
    public function scopePriority($query)
    {
        return $query->where('priority', '>', 0)->orderBy('priority');
    }

    // Scope for secondary priority products
    public function scopeSecondaryPriority($query)
    {
        return $query->where('secondary_priority', '>', 0)->orderBy('secondary_priority');
    }
}
