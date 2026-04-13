<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = [
        'product_id',
        'tenant_id',
        'collection',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'alt_text',
        'sort_order',
        'uploaded_by',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'size' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Public URL for storefront / API (admin uploads on public disk).
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
