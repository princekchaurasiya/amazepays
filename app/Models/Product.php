<?php

namespace App\Models;

use App\Helpers\ProductHelper;
use App\Helpers\ProductImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

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
        'catalog_audience',
        'last_synced_at',
        'sync_status',
        'price',
        'currency',
        'product_currency_code',
        'images',
        'custom_image',
        'card_logo_url',
        'card_bg_color',
        'card_text_color',
        'card_accent_color',
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
        'hot_deal_rank',
        'display_order',
        'out_of_stock',
        'is_special_sku',
        'slug',
        'url',
        'gift_option_policy',
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
        'hot_deal_rank' => 'decimal:2',
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

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id')
            ->withTimestamps();
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'product_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'brand_id');
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

    /** Products assigned to the homepage hot-deals slot (nullable rank; lower = earlier). */
    public function scopeHotDealRank($query)
    {
        return $query->whereNotNull('hot_deal_rank')->orderBy('hot_deal_rank');
    }

    public function scopeDisplayOrder($query)
    {
        return $query->where('display_order', '>', 0)->orderBy('display_order');
    }

    /** @var list<string> */
    public const STOREFRONT_EXCLUDED_SOURCE_PROVIDERS = ['vouchagram_pull'];

    public const CATALOG_AUDIENCE_B2C = 'b2c';

    public const CATALOG_AUDIENCE_B2B = 'b2b';

    public const CATALOG_AUDIENCE_BOTH = 'both';

    public const GIFT_OPTION_BOTH = 'both';

    public const GIFT_OPTION_SELF_ONLY = 'self_only';

    public const GIFT_OPTION_GIFT_ONLY = 'gift_only';

    /**
     * Default B2B/B2C channel for a voucher provider (used on sync and admin create).
     */
    public static function defaultCatalogAudienceForSourceProvider(?string $provider): string
    {
        $p = (string) $provider;

        $defaults = config('voucher.default_audiences', []);
        $configured = is_array($defaults) ? ($defaults[$p] ?? null) : null;
        $candidate = is_string($configured) ? strtolower(trim($configured)) : '';

        if (in_array($candidate, [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_B2B, self::CATALOG_AUDIENCE_BOTH], true)) {
            return $candidate;
        }

        // Fallback (legacy defaults)
        return match ($p) {
            'vouchagram_pull' => self::CATALOG_AUDIENCE_B2B,
            'vouchagram_send', 'vouchagram', 'value_design' => self::CATALOG_AUDIENCE_B2C,
            default => self::CATALOG_AUDIENCE_BOTH,
        };
    }

    /** B2C storefront listings: visible, B2C-eligible, and not pull-only provider rows. */
    public function scopeForStorefrontCatalog($query)
    {
        return $query->where('show_product', true)
            ->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH])
            ->whereNotIn('source_provider', self::STOREFRONT_EXCLUDED_SOURCE_PROVIDERS);
    }

    /** B2B panel shop / price list: tenant-assigned products with Business or Both catalog audience. */
    public function scopeForB2bCatalog($query)
    {
        return $query->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2B, self::CATALOG_AUDIENCE_BOTH]);
    }

    /** Admin list: storefront-eligible rows (b2c + both), regardless of show_product. */
    public function scopeAdminStorefrontCatalog($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH])
                ->orWhereNull('catalog_audience')
                ->orWhere('catalog_audience', '');
        });
    }

    /** Admin list: B2B-eligible rows (b2b + both), regardless of show_product. */
    public function scopeAdminBusinessCatalog($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2B, self::CATALOG_AUDIENCE_BOTH])
                ->orWhereNull('catalog_audience')
                ->orWhere('catalog_audience', '');
        });
    }

    public function isExcludedFromConsumerStorefront(): bool
    {
        return in_array((string) $this->source_provider, self::STOREFRONT_EXCLUDED_SOURCE_PROVIDERS, true);
    }

    public function isListedOnConsumerStorefront(): bool
    {
        if (! $this->show_product) {
            return false;
        }

        if (! in_array((string) ($this->catalog_audience ?? self::CATALOG_AUDIENCE_BOTH), [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH], true)) {
            return false;
        }

        return ! $this->isExcludedFromConsumerStorefront();
    }

    public function getGiftOptionPolicyAttribute($value): string
    {
        $p = (string) $value;

        return in_array($p, [self::GIFT_OPTION_BOTH, self::GIFT_OPTION_SELF_ONLY, self::GIFT_OPTION_GIFT_ONLY], true)
            ? $p
            : self::GIFT_OPTION_BOTH;
    }

    public function canBuyForSelf(): bool
    {
        return $this->gift_option_policy !== self::GIFT_OPTION_GIFT_ONLY;
    }

    public function canSendAsGift(): bool
    {
        return $this->gift_option_policy !== self::GIFT_OPTION_SELF_ONLY;
    }

    /** @return array{logo_url: string|null,bg_color: string,text_color: string,accent_color: string} */
    public function resolveCardTheme(): array
    {
        $neutral = [
            'logo_url' => null,
            'bg_color' => '#f3f4f6',
            'text_color' => '#111827',
            'accent_color' => '#0f766e',
        ];

        $theme = null;
        if (class_exists(BrandCardTheme::class) && Schema::hasTable('brand_card_themes')) {
            $theme = BrandCardTheme::query()
                ->active()
                ->where(function ($query) {
                    $query->where('product_id', $this->id)
                        ->orWhere('brand_id', $this->brand_id)
                        ->orWhere('brand_name', $this->brandName);
                })
                ->orderByDesc('product_id')
                ->orderByDesc('brand_id')
                ->orderBy('priority')
                ->first();
        }

        $logo = $theme?->logo_url ?: ($this->card_logo_url ?: $neutral['logo_url']);
        $bg = $theme?->bg_color ?: ($this->card_bg_color ?: $neutral['bg_color']);
        $text = $theme?->text_color ?: ($this->card_text_color ?: $neutral['text_color']);
        $accent = $theme?->accent_color ?: ($this->card_accent_color ?: $neutral['accent_color']);

        return [
            'logo_url' => $logo,
            'bg_color' => $this->sanitizeHexColor($bg, $neutral['bg_color']),
            'text_color' => $this->sanitizeHexColor($text, $neutral['text_color']),
            'accent_color' => $this->sanitizeHexColor($accent, $neutral['accent_color']),
        ];
    }

    private function sanitizeHexColor(?string $value, string $fallback): string
    {
        $color = strtoupper(trim((string) $value));
        if ($color !== '' && preg_match('/^#(?:[0-9A-F]{3}|[0-9A-F]{6})$/', $color) === 1) {
            return $color;
        }

        return $fallback;
    }
}
