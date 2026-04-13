<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
