<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class HomepageSection extends Model
{
    use HasFactory;

    protected $table = 'homepage_sections';

    protected $fillable = [
        'section_name',
        'section_type',
        'title',
        'content',
        'config',
        'status',
        'sort_order',
    ];

    public $timestamps = false;

    protected $casts = [
        'status' => 'boolean',
        'config' => 'array',
        'sort_order' => 'integer',
    ];

    /** Built-in storefront section type keys (plus custom_html). */
    public const SECTION_TYPES = [
        'banner',
        'brands',
        'hot_deals',
        'categories',
        'other_deals',
        'kgen',
        'custom_html',
    ];

    public static function hasSortOrderColumn(): bool
    {
        return Schema::hasColumn((new static)->getTable(), 'sort_order');
    }

    /**
     * Order by sort_order when the column exists (older DBs may predate migrations).
     */
    public function scopeOrdered(Builder $query): Builder
    {
        if (static::hasSortOrderColumn()) {
            return $query->orderBy('sort_order')->orderBy('id');
        }

        return $query->orderBy('id');
    }

    public static function queryMaxSortOrder(): int
    {
        if (! static::hasSortOrderColumn()) {
            return 0;
        }

        return (int) static::query()->max('sort_order');
    }
}
