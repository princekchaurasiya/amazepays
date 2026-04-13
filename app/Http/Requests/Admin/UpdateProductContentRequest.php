<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.update');
    }

    public function rules(): array
    {
        return [
            'custom_description' => 'nullable|string|max:500000',
            'how_to_redeem' => 'nullable|string|max:500000',
            'terms_and_conditions' => 'nullable|string|max:500000',
        ];
    }
}
