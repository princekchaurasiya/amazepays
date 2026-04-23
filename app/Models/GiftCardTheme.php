<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GiftCardTheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'gallery_images',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'metadata' => 'array',
        'gallery_images' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'gift_theme_id');
    }

    public static function resolveMediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
