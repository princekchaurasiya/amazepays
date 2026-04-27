<?php

namespace App\Http\Requests\Storefront;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:50',
            'denomination' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:wallet,ccavenue,unlimit',
            'gift_send_option' => 'nullable|string|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email|max:255',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:500',
            'gift_theme_id' => 'nullable|required_if:gift_send_option,send_as_gift|integer|exists:gift_themes,id',
            'gift_message_title' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'sender_first_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:120',
            'gift_delivery_option' => 'nullable|required_if:gift_send_option,send_as_gift|string|in:send_now,send_later',
            'gift_delivery_at' => 'nullable|required_if:gift_delivery_option,send_later|date|after:now',
            'offer_code' => 'nullable|string|max:50',

            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_tel' => 'required|string|max:15',
            'billing_address' => 'required|string|max:500',
            'billing_address_two' => 'nullable|string|max:500',
            'billing_city' => 'required|string|max:100',
            'billing_state' => 'required|string|max:100',
            'billing_zip' => 'required|string|max:10',
            'billing_country' => 'nullable|string|max:2',
            'billing_gst_number' => 'nullable|string|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
        ];
    }

    public function messages(): array
    {
        return [
            'billing_gst_number.regex' => 'Please enter a valid 15-character GSTIN.',
        ];
    }
}
