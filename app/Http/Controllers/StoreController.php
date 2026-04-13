<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\VDWebApiService;
use App\Models\StoreDetail;
use App\Models\Brand;
use App\Exports\StoresExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

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

        $brandcodes = \App\Models\Brand::pluck('brand_code');

        $token = $service->getToken();

        if (!$token) {
            return back()->with('error', 'Unable to get access token');
        }

        $stores = $service->getStores($token, $brandcodes);

        if (!$stores) {
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
        if (!$token) {
            return back()->with('error', 'Failed to get token');
        }

        // Sync stores to DB
        $this->vdWeb->syncStoresToDatabase($token, $request->brand_code);

        return redirect()->route('stores.filter', ['brand_code' => $request->brand_code]);
    }

    public function filterStores(Request $request)
{
    $query = StoreDetail::query();

    // Apply filters based on user input
    if ($request->filled('brand_code')) {
        $query->where('brand_code', $request->brand_code);
    }

    if ($request->filled('brand_name')) {
        $query->where('brand_name', $request->brand_name);
    }

    if ($request->filled('country')) {
        $query->where('country', $request->country);
    }

    if ($request->filled('state')) {
        $query->where('state', $request->state);
    }

    if ($request->filled('city')) {
        $query->where('city', $request->city);
    }

    if ($request->filled('contact_number')) {
        $query->where('contact_number', 'like', '%' . $request->contact_number . '%');
    }

    if ($request->filled('store_name')) {
        $query->where('store_name', 'like', '%' . $request->store_name . '%');
    }

    $stores = $query->paginate(20);

    // Dropdown filter data (distinct values)
    $brandcodes = StoreDetail::distinct()->pluck('brand_code')->filter();
    $brandnames = StoreDetail::distinct()->pluck('brand_name')->filter();
    $countries  = StoreDetail::distinct()->pluck('country')->filter();
    $states     = StoreDetail::distinct()->pluck('state')->filter();
    $cities     = StoreDetail::distinct()->pluck('city')->filter();

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
        'filters' => $request->all(),
    ]);
}


    public function exportStores(Request $request)
    {
        return Excel::download(new StoresExport($request->all()), 'stores.xlsx');
    }

}
