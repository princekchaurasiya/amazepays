<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Woohoo / catalog-synced taxonomy (table: synced_categories). */
class SyncedCategory extends Model
{
    protected $table = 'synced_categories';

    protected $fillable = [
        'tenant_id',
        'local_category_id',
        'provider',
        'external_id',
        'external_parent_id',
        'name',
        'raw_payload',
        'synced_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'synced_at' => 'datetime',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'synced_category_id');
    }
}
