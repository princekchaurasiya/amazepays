<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VoyagerFetchProductDataController extends Controller
{
    public function fetchProductData(Request $request)
    {
        // Run your custom Artisan command
        Artisan::call('fetch:productData');

        // Redirect back to the Voyager settings page
        return redirect()
            ->route('voyager.settings.index')
            ->with('success', 'Product data updated successfully.');
    }
}
