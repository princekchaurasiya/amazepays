<?php

namespace App\Exports;

use App\Models\StoreDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class StoresExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return StoreDetail::all();
    }

    public function view(): View
    {
        $query = StoreDetail::query();

        if (!empty($this->filters['brand_code'])) {
            $query->where('brand_code', $this->filters['brand_code']);
        }

        if (!empty($this->filters['country'])) {
            $query->where('country', $this->filters['country']);
        }

        if (!empty($this->filters['min_price'])) {
            $query->where('price', '>=', $this->filters['min_price']);
        }

        if (!empty($this->filters['max_price'])) {
            $query->where('price', '<=', $this->filters['max_price']);
        }

        if (!empty($this->filters['search'])) {
            $query->where('store_name', 'like', '%' . $this->filters['search'] . '%');
        }

        return view('stores.export', ['stores' => $query->get()]);
    }
}
