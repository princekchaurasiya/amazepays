<?php

namespace App\Exports;

use App\Models\Brand;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class BrandsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Brand::query()
            ->select([
                'id',
                'name',
                'slug',
                'source_provider',
                'source_brand_id',
                'status',
                'is_featured',
                'display_order',
            ])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Slug',
            'Source Provider',
            'Source Brand ID',
            'Status',
            'Is Featured',
            'Display Order',
        ];
    }
}
