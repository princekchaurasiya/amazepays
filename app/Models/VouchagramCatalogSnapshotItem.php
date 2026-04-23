<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VouchagramCatalogSnapshotItem extends Model
{
    protected $table = 'vouchagram_catalog_snapshot_items';

    protected $fillable = [
        'snapshot_id',
        'api_mode',
        'brand_product_code',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(VouchagramCatalogSnapshot::class, 'snapshot_id');
    }
}
