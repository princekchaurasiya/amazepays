<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmazepayBrand extends Model
{
    use HasFactory;

    protected $table = 'amazepay_available_brands';

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'order',
    ];

    public function products()
    {
        return $this->hasMany(QsProduct::class, 'brand_id');
    }
}
