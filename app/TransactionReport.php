<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionReport extends Model
{
    use HasFactory;

    protected $table = 'transaction_reports';

    protected $casts = [
        'report_type' => 'array',
    ];
}
