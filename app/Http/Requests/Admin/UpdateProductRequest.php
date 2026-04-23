<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'catalog_audience' => 'required|string|in:b2c,b2b,both',
            'hot_deal_rank' => 'nullable|numeric|min:0',
            'display_order' => 'integer|min:0',
            'custom_image' => 'nullable|image|max:5120',
        ];
    }
}
