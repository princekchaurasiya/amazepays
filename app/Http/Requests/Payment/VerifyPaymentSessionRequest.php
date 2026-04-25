<?php

namespace App\Http\Requests\Payment;

use App\Http\Requests\Concerns\RejectsUnexpectedInput;
use Illuminate\Foundation\Http\FormRequest;

final class VerifyPaymentSessionRequest extends FormRequest
{
    use RejectsUnexpectedInput;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Optional override when a gateway uses a different reference id.
            'transaction_id' => ['nullable', 'string', 'max:191'],
        ];
    }
}
