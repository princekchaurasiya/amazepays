<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\VDWebApiService;
use App\Models\StoreDetail;
use App\Models\Brand;
use App\Exports\StoresExport;
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
        $brands = \App\Models\Brand::all(); // Or fetch via API
        return view('brands.select', compact('brands'));
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

        return view('stores.list', [
            'stores' => $stores,
            'brandCode' => $brandcodes,
        ]);
    }

    public function showForm()
    {
        $brands = Brand::pluck('brand_name', 'brand_code');
        return view('stores.form', compact('brands'));
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

    return view('stores.list', [
        'stores'      => $stores,
        'brandcodes'  => $brandcodes,
        'brandnames'  => $brandnames,
        'countries'   => $countries,
        'states'      => $states,
        'cities'      => $cities,
        'filters'     => $request->all(),
    ]);
}


    public function exportStores(Request $request)
    {
        return Excel::download(new StoresExport($request->all()), 'stores.xlsx');
    }

}
