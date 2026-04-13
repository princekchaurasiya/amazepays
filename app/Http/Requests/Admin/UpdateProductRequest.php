<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.update');
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'denomination' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'custom_description' => 'nullable|string',
            'how_to_redeem' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'show_product' => 'boolean',
            'priority' => 'integer|min:0',
            'display_order' => 'integer|min:0',
            'custom_image' => 'nullable|image|max:5120',
        ];
    }
}
