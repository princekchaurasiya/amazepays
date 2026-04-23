<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkUpdateProductsRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'exists:products,id'],
            'catalog_scope' => ['required', 'string', 'in:storefront,business,all'],
            'apply_visibility' => ['sometimes', 'boolean'],
            'show_product' => [Rule::requiredIf(fn () => $this->boolean('apply_visibility')), 'boolean'],
            'price_mode' => ['nullable', 'string', 'in:absolute,relative_percent,relative_fixed'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'price_relative_percent' => ['required_if:price_mode,relative_percent', 'numeric', 'min:-90', 'max:500'],
            'price_relative_amount' => ['required_if:price_mode,relative_fixed', 'numeric'],
            'apply_custom_description' => ['sometimes', 'boolean'],
            'custom_description' => ['nullable', 'string'],
            'apply_how_to_redeem' => ['sometimes', 'boolean'],
            'how_to_redeem' => ['nullable', 'string'],
            'apply_terms_and_conditions' => ['sometimes', 'boolean'],
            'terms_and_conditions' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $hasVisibility = $this->boolean('apply_visibility');
            $hasPrice = filled($this->input('price_mode'));
            $hasContent = $this->boolean('apply_custom_description')
                || $this->boolean('apply_how_to_redeem')
                || $this->boolean('apply_terms_and_conditions');

            if (! $hasVisibility && ! $hasPrice && ! $hasContent) {
                $v->errors()->add('ids', 'Choose at least one change: visibility, pricing, or content.');
            }

            if ($this->input('price_mode') === 'absolute') {
                if (
                    ! $this->filled('selling_price')
                    && ! $this->filled('mrp')
                    && ! $this->filled('discount_percentage')
                ) {
                    $v->errors()->add('selling_price', 'Set at least one pricing field for absolute mode.');
                }
            }
        });
    }
}
