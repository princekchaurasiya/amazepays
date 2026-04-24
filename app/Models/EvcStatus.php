<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvcStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'request_ref_no',
        'status',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
