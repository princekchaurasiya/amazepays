<?php

namespace App\Exports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Brand::select([
            'brand_code',
            'brand_name',
            'brand_type',
            'discount',
            'min_price',
            'max_price',
            'stock_available',
            'category',
            'description'
        ])->get();
    }

    public function headings(): array
    {
        return [
            'Brand Code',
            'Brand Name',
            'Brand Type',
            'Discount',
            'Min Price',
            'Max Price',
            'Stock Available',
            'Category',
            'Description',
        ];
    }
}
