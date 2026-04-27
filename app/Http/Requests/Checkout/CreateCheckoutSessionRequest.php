<?php

namespace App\Http\Requests\Checkout;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

final class CreateCheckoutSessionRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'min:1', 'exists:products,id'],
            'denomination' => ['required', 'numeric', 'min:1', 'max:999999'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'gift_send_option' => ['required', 'string', 'in:send_as_gift,buy_for_self'],
            'loyalty_points_to_redeem' => ['nullable', 'integer', 'min:1', 'max:100000000'],

            // Gift-only fields
            'receiver_name' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'string', 'max:255'],
            'receiver_email' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'email', 'max:255'],
            'receiver_mobile' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'digits:10'],
            'receiver_msg' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'string', 'max:500'],
            'gift_theme_id' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'integer', 'exists:gift_themes,id'],
            'gift_message_title' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'string', 'max:120'],
            'sender_first_name' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'string', 'max:120'],
            'gift_delivery_option' => ['nullable', 'required_if:gift_send_option,send_as_gift', 'string', 'in:send_now,send_later'],
            'gift_delivery_at' => ['nullable', 'required_if:gift_delivery_option,send_later', 'date', 'after:now'],
        ];
    }
}

