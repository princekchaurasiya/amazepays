<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QsOrder extends Model
{
    use HasFactory;
    protected $table = 'qs_ordered';
    protected $fillable = ['woohoo_order_id', 'order_status', 'denomination', 'sender_first_name', 'sender_email', 'sender_phone_no', 'sender_post_code', 'sender_address_1', 'sender_address_2', 'sender_city', 'sender_state', 'sku', 'amount', 'receiver_name', 'receiver_email', 'receiver_mobile', 'receiver_msg', 'cards', 'order_cancel', 'order_payment', 'currency','additionalTxnFields'];
}
