<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'sku' => ['required', 'string', Rule::unique((new Product)->getTable(), 'sku')],
            'source_provider' => 'required|string|in:woohoo,kgen,value_design,lysto,ezpin,gyftrr,manual,vouchagram',
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
            'custom_image' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use by another product.',
            'source_provider.in' => 'Please select a valid voucher provider.',
            'discount_percentage.max' => 'Discount cannot exceed 100%.',
        ];
    }
}
