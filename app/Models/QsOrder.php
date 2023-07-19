<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QsOrder extends Model
{
    use HasFactory;
    protected $table = 'qs_ordered';
    protected $fillable = [
        'order_id',
        'order_status',
        // Add other attributes here if needed...
    ];
}
