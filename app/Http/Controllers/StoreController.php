<?php

namespace App\Http\Controllers;

use App\Exports\StoresExport;
use App\Http\Services\VDWebApiService;
use App\Models\Brand;
use App\Models\StoreDetail;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class StoreController extends Controller
{
    protected $vdWeb;

    public function __construct(VDWebApiService $vdWeb)
    {
        $this->vdWeb = $vdWeb;
    }

    public function showBrandSelection()
    {
        $brands = Brand::all();

        return Inertia::render('Admin/Stores/StoresWorkspace', [
            'mode' => 'brand_pick',
            'allBrands' => $brands->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->brand_name,
                'brand_code' => $b->brand_code,
            ])->values()->all(),
        ]);
    }

    public function fetchStoresForBrand(Request $request, VDWebApiService $service)
    {
        $request->validate([
            'brand_code' => 'required|string',
        ]);

        $brandcodes = Brand::pluck('brand_code');

        $token = $service->getToken();

        if (! $token) {
            return back()->with('error', 'Unable to get access token');
        }

        $stores = $service->getStores($token, $brandcodes);

        if (! $stores) {
            return back()->with('error', 'No stores found or failed to fetch stores');
        }

        return Inertia::render('Admin/Stores/StoresWorkspace', [
            'mode' => 'list_fetch',
            'stores' => is_array($stores) ? $stores : [],
            'brandCode' => $brandcodes->values()->all(),
        ]);
    }

    public function showForm()
    {
        $plucked = Brand::pluck('brand_name', 'brand_code');
        $brands = [];
        foreach ($plucked as $code => $name) {
            $brands[] = ['code' => $code, 'name' => $name];
        }

        return Inertia::render('Admin/Stores/StoresWorkspace', [
            'mode' => 'form',
            'brands' => $brands,
        ]);
    }

    public function syncAndShow(Request $request)
    {
        $request->validate([
            'brand_code' => 'required|string',
        ]);

        $token = $this->vdWeb->getToken();
        if (! $token) {
            return back()->with('error', 'Failed to get token');
        }

        // Sync stores to DB
        $this->vdWeb->syncStoresToDatabase($token, $request->brand_code);

        return redirect()->route('panel.value-design.stores.filter', ['brand_code' => $request->brand_code]);
    }

    public function filterStores(Request $request)
    {
        $filters = $request->validate([
            'brand_code' => ['nullable', 'string', 'max:50'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'store_name' => ['nullable', 'string', 'max:255'],
        ]);

        $query = StoreDetail::query();

        // Apply filters based on user input
        if (! empty($filters['brand_code'])) {
            $query->where('brand_code', $filters['brand_code']);
        }

        if (! empty($filters['brand_name'])) {
            $query->where('brand_name', $filters['brand_name']);
        }

        if (! empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (! empty($filters['state'])) {
            $query->where('state', $filters['state']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['contact_number'])) {
            $query->where('contact_number', 'like', '%'.$filters['contact_number'].'%');
        }

        if (! empty($filters['store_name'])) {
            $query->where('store_name', 'like', '%'.$filters['store_name'].'%');
        }

        $stores = $query->paginate(20);

        // Dropdown filter data (distinct values)
        $brandcodes = StoreDetail::distinct()->pluck('brand_code')->filter();
        $brandnames = StoreDetail::distinct()->pluck('brand_name')->filter();
        $countries = StoreDetail::distinct()->pluck('country')->filter();
        $states = StoreDetail::distinct()->pluck('state')->filter();
        $cities = StoreDetail::distinct()->pluck('city')->filter();

        return Inertia::render('Admin/Stores/StoresWorkspace', [
            'mode' => 'list_filter',
            'stores' => $stores->items(),
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'total' => $stores->total(),
            ],
            'brandcodes' => $brandcodes->values()->all(),
            'brandnames' => $brandnames->values()->all(),
            'countries' => $countries->values()->all(),
            'states' => $states->values()->all(),
            'cities' => $cities->values()->all(),
            'filters' => $filters,
        ]);
    }

    public function exportStores(Request $request)
    {
        $filters = $request->validate([
            'brand_code' => ['nullable', 'string', 'max:50'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'store_name' => ['nullable', 'string', 'max:255'],
        ]);

        return Excel::download(new StoresExport($filters), 'stores.xlsx');
    }
}
