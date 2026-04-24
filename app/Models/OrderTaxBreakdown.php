<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTaxBreakdown extends Model
{
    use HasFactory;

    protected $table = 'order_tax_breakdowns';

    protected $fillable = [
        'order_item_id',
        'tax_component',
        'tax_rate_id',
        'hsn_sac_code',
        'rate_percent',
        'taxable_amount_minor',
        'tax_amount_minor',
        'currency',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:3',
        'taxable_amount_minor' => 'integer',
        'tax_amount_minor' => 'integer',
    ];

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}

