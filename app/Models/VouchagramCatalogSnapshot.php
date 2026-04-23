<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VouchagramCatalogSnapshot extends Model
{
    protected $table = 'vouchagram_catalog_snapshots';

    protected $fillable = [
        'mode',
        'brand_product_code_filter',
        'item_count',
        'fetched_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fetched_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VouchagramCatalogSnapshotItem::class, 'snapshot_id');
    }
}
