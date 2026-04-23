<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class B2bPriceListExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, array<string, mixed>>|array<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private Collection|array $rows,
    ) {}

    public function collection(): Collection
    {
        $rows = is_array($this->rows) ? collect($this->rows) : $this->rows;

        return $rows->map(fn (array $r) => [
            $r['sku'] ?? '',
            $r['name'] ?? '',
            $r['currency_code'] ?? '',
            $r['price_range_label'] ?? '',
            $r['representative_denomination'] ?? '',
            $r['discount_percentage'] ?? '',
            $r['sample_grand_total'] ?? '',
            $r['discount_source'] ?? '',
        ]);
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Product',
            'Currency',
            'Face value / range',
            'Sample denomination',
            'Discount %',
            'Indicative total (qty 1, sample denom)',
            'Discount source',
        ];
    }
}
