<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    use HasFactory;

    protected $fillable = [
        'desktop_image',
        'image_mobile',
        'video',
        'video_mobile',
        'small_header',
        'big_header',
        'cta_value',
        'cta_link',
        'priority',
        'slider_location',
        'status',
        'img_alt_tag',
        'display_on_page',
        'product_id',
        'category_id',
        'brand_id',
        'custom_url',
        'link_type',
    ];
}
