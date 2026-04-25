<?php

namespace App\Domains\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentSection extends Model
{
    use SoftDeletes;

    protected $table = 'content_sections';

    protected $fillable = [
        'tenant_id',
        'surface',
        'slug',
        'type',
        'status',
        'is_enabled',
        'sort_order',
        'platform',
        'start_at',
        'end_at',
        'priority',
        'title',
        'subtitle',
        'background_color',
        'text_color',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'priority' => 'integer',
        'metadata' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ContentSectionItem::class, 'content_section_id');
    }
}

