<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VoyagerFetchProductListController extends Controller
{
    public function fetchProductList(Request $request)
    {
        // Run your custom Artisan command
        Artisan::call('fetch:productList');

        // Redirect back to the Voyager settings page
        return redirect()
            ->route('voyager.settings.index')
            ->with('success', 'Product List Updated Successfully');
    }
}
