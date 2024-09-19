<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class QsCategory extends Model
{
    protected $table = 'qs_categories';

    // Define the relationship with QsProduct
    // public function products()
    // {
    //     return $this->hasMany(QsProduct::class, 'qs_category_id');
    // }

}
