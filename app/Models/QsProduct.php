<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class QsProduct extends Model
{

    use HasFactory;
    protected $table = 'qs_products';
    protected $fillable = ['discount_percentage'];

    // Define the relationship with QsCategory
    public function amazepayCategories()
    {
        return $this->belongsToMany(AmazepayCategory::class, 'amazepay_category_product', 'product_id', 'amazepay_category_id');
    }
}
