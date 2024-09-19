<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QsProduct extends Model
{
    protected $table = 'qs_products';
    protected $fillable = ['discount_percentage'];

    // Define the relationship with QsCategory
    // public function category()
    // {
    //     return $this->belongsTo(QsCategory::class, 'qs_category_id');
    // }
}
