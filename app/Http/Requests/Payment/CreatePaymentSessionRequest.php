<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

final class CreatePaymentSessionRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'min:1', 'exists:orders,id'],
            'gateway' => ['required', 'string', 'in:unlimit,razorpay,mock_razorpay'],
            // optional metadata, allow-listed
            'method_category' => ['nullable', 'string', 'in:card,upi,netbanking,wallet,emi,other'],
            'payment_method' => ['nullable', 'string', 'max:64'],
        ];
    }
}

