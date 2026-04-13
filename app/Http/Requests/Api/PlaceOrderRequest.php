<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:50',
            'denomination' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:wallet,ccavenue,unlimit,razorpay',
            'offer_code' => 'nullable|string|max:50',
            'gift_send_option' => 'nullable|string|in:send_as_gift,buy_for_self',
            'receiver_name' => 'nullable|required_if:gift_send_option,send_as_gift|string|max:255',
            'receiver_email' => 'nullable|required_if:gift_send_option,send_as_gift|email|max:255',
            'receiver_mobile' => 'nullable|required_if:gift_send_option,send_as_gift|digits:10',
            'receiver_msg' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'quantity.max' => 'You can order up to 50 vouchers at a time.',
            'denomination.required' => 'Please enter an amount.',
            'denomination.min' => 'The minimum denomination is ₹1.',
            'payment_method.in' => 'Please choose a valid payment method.',
            'receiver_email.required_if' => 'Recipient email is required when sending as a gift.',
            'receiver_mobile.required_if' => 'Recipient phone number is required when sending as a gift.',
        ];
    }
}
