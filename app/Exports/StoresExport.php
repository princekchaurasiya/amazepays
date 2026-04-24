<?php

namespace App\Exports;

use App\Models\StoreDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class StoresExport implements FromCollection
{
    /**
     * @return Collection
     */
    /** @var array<string, mixed> */
    protected array $filters;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $query = StoreDetail::query();

        if (! empty($this->filters['brand_code'])) {
            $query->where('brand_code', $this->filters['brand_code']);
        }

        if (! empty($this->filters['brand_name'])) {
            $query->where('brand_name', $this->filters['brand_name']);
        }

        if (! empty($this->filters['country'])) {
            $query->where('country', $this->filters['country']);
        }

        if (! empty($this->filters['state'])) {
            $query->where('state', $this->filters['state']);
        }

        if (! empty($this->filters['city'])) {
            $query->where('city', $this->filters['city']);
        }

        if (! empty($this->filters['contact_number'])) {
            $query->where('contact_number', 'like', '%'.$this->filters['contact_number'].'%');
        }

        if (! empty($this->filters['store_name'])) {
            $query->where('store_name', 'like', '%'.$this->filters['store_name'].'%');
        }

        return $query->get();
    }
}
