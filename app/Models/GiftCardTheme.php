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

    protected $table = 'gift_themes';

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'is_active',
        'display_order',
        'preview_image_url',
        'email_template_path',
        'active_from',
        'active_until',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
        'active_from' => 'date',
        'active_until' => 'date',
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
