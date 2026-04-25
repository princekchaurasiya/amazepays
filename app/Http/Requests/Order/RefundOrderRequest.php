<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

final class RefundOrderRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gateway' => ['required', 'string', 'in:unlimit,razorpay,ccavenue,mock_razorpay'],
            'amount_minor' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:191'],
            'transaction_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
