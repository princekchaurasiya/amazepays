<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('hot_deal_rank') && $this->input('hot_deal_rank') === '') {
            $this->merge(['hot_deal_rank' => null]);
        }

        if (! $this->filled('catalog_audience') && $this->filled('source_provider')) {
            $this->merge([
                'catalog_audience' => Product::defaultCatalogAudienceForSourceProvider($this->string('source_provider')->toString()),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()->can('products.create');
    }

    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                Rule::unique('products', 'sku')->where(fn ($q) => $q->where('source_provider', $this->input('source_provider'))),
            ],
            'source_provider' => 'required|string|in:woohoo,kgen,value_design,lysto,ezpin,gyftrr,manual,vouchagram,vouchagram_send,vouchagram_pull',
            'catalog_audience' => 'required|string|in:b2c,b2b,both',
            'selling_price' => 'required|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'denomination' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'custom_description' => 'nullable|string',
            'how_to_redeem' => 'nullable|string',
            'terms_and_conditions' => 'nullable|string',
            'show_product' => 'boolean',
            'hot_deal_rank' => 'nullable|numeric|min:0',
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
