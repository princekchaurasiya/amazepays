<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class SubmitWalletLoadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:100|max:500000',
            'payment_mode' => 'required|in:neft,imps,rtgs,cash,cheque',
            'reference_no' => 'nullable|string|max:100',
            'proof' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }
}
