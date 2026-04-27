<?php

namespace App\Models;

use App\Helpers\ProductHelper;
use App\Helpers\ProductImageHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'tenant_id',
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

    /**
     * Phase-3 pricing source of truth.
     * Prefer normalized tables; fall back to legacy price JSON if needed.
     *
     * @return array{type:string,denominations:array<int,float>,min:float|null,max:float|null}
     */
    public function resolveStorefrontPrice(): array
    {
        // Slab (denominations)
        if (Schema::hasTable('product_price_denominations')) {
            $hasActive = Schema::hasColumn('product_price_denominations', 'is_active');
            $rows = DB::table('product_price_denominations')
                ->where('product_id', $this->id)
                ->when($hasActive, fn ($q) => $q->where('is_active', true))
                ->orderBy('display_order')
                ->orderBy('amount_minor')
                ->get(['amount_minor']);

            $denoms = $rows
                ->map(fn ($r) => (float) (((int) ($r->amount_minor ?? 0)) / 100))
                ->filter(fn ($v) => $v > 0)
                ->values()
                ->all();

            if ($denoms !== []) {
                return ['type' => 'SLAB', 'denominations' => $denoms, 'min' => null, 'max' => null];
            }
        }

        // Range (min/max)
        if (Schema::hasTable('product_price_ranges')) {
            $hasActive = Schema::hasColumn('product_price_ranges', 'is_active');
            $row = DB::table('product_price_ranges')
                ->where('product_id', $this->id)
                ->when($hasActive, fn ($q) => $q->where('is_active', true))
                ->first(['min_amount_minor', 'max_amount_minor']);

            if ($row) {
                $min = (float) (((int) ($row->min_amount_minor ?? 0)) / 100);
                $max = (float) (((int) ($row->max_amount_minor ?? 0)) / 100);
                if ($min > 0 && $max > 0 && $max >= $min) {
                    return ['type' => 'RANGE', 'denominations' => [], 'min' => $min, 'max' => $max];
                }
            }
        }

        // Legacy fallback
        $legacy = (array) ($this->price ?? []);
        $type = strtoupper((string) ($legacy['type'] ?? ''));
        if ($type === 'SLAB' || $type === 'RANGE') {
            return [
                'type' => $type,
                'denominations' => array_values(array_filter(array_map('floatval', (array) ($legacy['denominations'] ?? [])), fn ($v) => $v > 0)),
                'min' => isset($legacy['min']) ? (float) $legacy['min'] : null,
                'max' => isset($legacy['max']) ? (float) $legacy['max'] : null,
            ];
        }

        return ['type' => 'RANGE', 'denominations' => [], 'min' => null, 'max' => null];
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
        return $this->hasMany(ProductMedia::class, 'product_id')->orderBy('display_order');
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
        // Phase-3 schema uses status/published_at instead of legacy show_product.
        if (Schema::hasColumn($this->getTable(), 'show_product')) {
            return $query->where('show_product', true);
        }

        if (Schema::hasColumn($this->getTable(), 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn($this->getTable(), 'published_at')) {
            $query->whereNotNull('published_at');
        }

        return $query;
    }

    /** Products assigned to the homepage hot-deals slot (nullable rank; lower = earlier). */
    public function scopeHotDealRank($query)
    {
        if (Schema::hasColumn($this->getTable(), 'hot_deal_rank')) {
            return $query->whereNotNull('hot_deal_rank')->orderBy('hot_deal_rank');
        }

        // Phase-3 schema: fallback to featured products, then display order.
        if (Schema::hasColumn($this->getTable(), 'is_featured')) {
            $query->orderByDesc('is_featured');
        }
        if (Schema::hasColumn($this->getTable(), 'display_order')) {
            $query->orderBy('display_order');
        }

        return $query->orderByDesc('id');
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
        $query->visible();

        // Legacy schema: catalog_audience = b2c/b2b/both
        if (Schema::hasColumn($this->getTable(), 'catalog_audience')) {
            $query->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH]);
        } else {
            // Phase-3 schema: is_b2b_only / is_b2c_only flags
            if (Schema::hasColumn($this->getTable(), 'is_b2b_only')) {
                $query->where('is_b2b_only', false);
            }
        }

        if (Schema::hasColumn($this->getTable(), 'source_provider')) {
            $query->whereNotIn('source_provider', self::STOREFRONT_EXCLUDED_SOURCE_PROVIDERS);
        }

        return $query;
    }

    /** B2B panel shop / price list: tenant-assigned products with Business or Both catalog audience. */
    public function scopeForB2bCatalog($query)
    {
        if (Schema::hasColumn($this->getTable(), 'catalog_audience')) {
            return $query->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2B, self::CATALOG_AUDIENCE_BOTH]);
        }

        // Phase-3 schema: include B2B + Both. Only exclude explicit B2C-only rows.
        if (Schema::hasColumn($this->getTable(), 'is_b2c_only')) {
            return $query->where('is_b2c_only', false);
        }

        // Best-effort fallback: if we only have is_b2b_only, include those plus "both" (unknown).
        return $query;
    }

    /** Admin list: storefront-eligible rows (b2c + both), regardless of show_product. */
    public function scopeAdminStorefrontCatalog($query)
    {
        if (Schema::hasColumn($this->getTable(), 'catalog_audience')) {
            return $query->where(function ($q) {
                $q->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH])
                    ->orWhereNull('catalog_audience')
                    ->orWhere('catalog_audience', '');
            });
        }

        // Phase-3 schema: storefront excludes B2B-only.
        if (Schema::hasColumn($this->getTable(), 'is_b2b_only')) {
            return $query->where('is_b2b_only', false);
        }

        return $query;
    }

    /** Admin list: B2B-eligible rows (b2b + both), regardless of show_product. */
    public function scopeAdminBusinessCatalog($query)
    {
        if (Schema::hasColumn($this->getTable(), 'catalog_audience')) {
            return $query->where(function ($q) {
                $q->whereIn('catalog_audience', [self::CATALOG_AUDIENCE_B2B, self::CATALOG_AUDIENCE_BOTH])
                    ->orWhereNull('catalog_audience')
                    ->orWhere('catalog_audience', '');
            });
        }

        // Phase-3 schema: include rows that are not explicitly B2C-only.
        if (Schema::hasColumn($this->getTable(), 'is_b2c_only')) {
            return $query->where('is_b2c_only', false);
        }

        return $query;
    }

    public function isExcludedFromConsumerStorefront(): bool
    {
        return in_array((string) $this->source_provider, self::STOREFRONT_EXCLUDED_SOURCE_PROVIDERS, true);
    }

    public function isListedOnConsumerStorefront(): bool
    {
        // Legacy visibility flag.
        if (Schema::hasColumn($this->getTable(), 'show_product')) {
            if (! (bool) $this->getAttribute('show_product')) {
                return false;
            }
        } else {
            // Phase-3: require status=active + published_at set.
            if (Schema::hasColumn($this->getTable(), 'status') && (string) $this->getAttribute('status') !== 'active') {
                return false;
            }
            if (Schema::hasColumn($this->getTable(), 'published_at') && $this->getAttribute('published_at') === null) {
                return false;
            }
        }

        if (Schema::hasColumn($this->getTable(), 'catalog_audience')) {
            if (! in_array((string) ($this->catalog_audience ?? self::CATALOG_AUDIENCE_BOTH), [self::CATALOG_AUDIENCE_B2C, self::CATALOG_AUDIENCE_BOTH], true)) {
                return false;
            }
        } elseif (Schema::hasColumn($this->getTable(), 'is_b2b_only')) {
            if ((bool) $this->getAttribute('is_b2b_only') === true) {
                return false;
            }
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
            $q = BrandCardTheme::query();
            // Legacy DBs may not have is_active; avoid crashing.
            if (Schema::hasColumn('brand_card_themes', 'is_active')) {
                $q->active();
            }

            $hasProductId = Schema::hasColumn('brand_card_themes', 'product_id');
            $hasBrandId = Schema::hasColumn('brand_card_themes', 'brand_id');
            $hasBrandName = Schema::hasColumn('brand_card_themes', 'brand_name');

            // If the table doesn't have any of the selector columns, do not query it.
            if ($hasProductId || $hasBrandId || $hasBrandName) {
                $theme = $q
                    ->where(function ($query) use ($hasProductId, $hasBrandId, $hasBrandName) {
                        if ($hasProductId) {
                            $query->orWhere('product_id', $this->id);
                        }
                        if ($hasBrandId) {
                            $query->orWhere('brand_id', $this->brand_id);
                        }
                        if ($hasBrandName) {
                            $query->orWhere('brand_name', $this->brandName)
                                ->orWhereNull('brand_name');
                        }
                    })
                    ->when($hasProductId, fn ($qb) => $qb->orderByDesc('product_id'))
                    ->when($hasBrandId, fn ($qb) => $qb->orderByDesc('brand_id'))
                    ->when(Schema::hasColumn('brand_card_themes', 'priority'), fn ($qb) => $qb->orderBy('priority'))
                    ->when(Schema::hasColumn('brand_card_themes', 'id'), fn ($qb) => $qb->orderBy('id'))
                    ->first();
            }
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
