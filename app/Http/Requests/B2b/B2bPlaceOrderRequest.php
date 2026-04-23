<?php

namespace App\Http\Requests\B2b;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

class B2bPlaceOrderRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'required|integer|min:1|max:50',
            'denomination' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:wallet',
            'offer_code' => 'nullable|string|max:50',
        ];
    }
}
