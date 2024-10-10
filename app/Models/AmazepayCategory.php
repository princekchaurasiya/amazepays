<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;


class AmazepayCategory extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug', 'order',  'thumbnail'];

    // Automatically generate slugs on creation and update
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            Log::info('Creating category: ', ['name' => $category->name]);
            $category->slug = Str::slug($category->name);
            Log::info('Generated slug: ', ['slug' => $category->slug]);
        });

        static::updating(function ($category) {
            Log::info('Updating category: ', ['name' => $category->name]);
            $category->slug = Str::slug($category->name);
            Log::info('Updated slug: ', ['slug' => $category->slug]);
        });
    }
}
