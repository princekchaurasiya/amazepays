<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KGenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'variant_id',
        'external_ref',
        'mrp',
        'payable_amount',
        'selling_price',
        'status',
        'payment_status',
        'fulfillment_status',
        'api_response',
        'vouchers',
        // ... other fields
    ];

    protected $casts = [
        'api_response' => 'array',
        'vouchers' => 'array',
        'mrp' => 'decimal:2',
        'payable_amount' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    /**
     * Relationships
     */

    // User who placed the order
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Product ordered (if variant_id is stored and relates to KgenProduct)
    public function product()
    {
        return $this->belongsTo(KgenProduct::class, 'variant_id', 'productID');
    }

    /**
     * Scopes
     */

    // Scope for user orders
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    // Scope for completed orders
    public function scopeCompleted($query)
    {
        return $query->where('fulfillment_status', 'fulfilled');
    }

    // Scope for pending orders
    public function scopePending($query)
    {
        return $query->where('fulfillment_status', 'pending');
    }

    // Scope for paid orders
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function firstVoucherFromApi(): ?array
    {
        $api = $this->api_response;
        if (is_string($api)) {
            $decoded = json_decode($api, true);
            $api = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($api)) {
            return null;
        }
        $lineItems = $api['lineItems'] ?? null;
        if (! is_array($lineItems) || $lineItems === []) {
            return null;
        }
        $vouchers = $lineItems[0]['vouchers'] ?? null;
        if (! is_array($vouchers) || $vouchers === []) {
            return null;
        }

        $first = $vouchers[0] ?? null;

        return is_array($first) ? $first : null;
    }

    public function getVoucherCodeAttribute(): string
    {
        $v = $this->firstVoucherFromApi();

        return (string) ($v['voucherCode'] ?? 'N/A');
    }

    public function getVoucherPinAttribute(): string
    {
        $v = $this->firstVoucherFromApi();

        return (string) ($v['voucherPin'] ?? 'N/A');
    }

    public function getVoucherExpirationDateAttribute(): string
    {
        $v = $this->firstVoucherFromApi();
        $exp = $v['expirationDate'] ?? 'N/A';

        return is_scalar($exp) ? (string) $exp : 'N/A';
    }
}
