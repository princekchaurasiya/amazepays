<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class VoyagerGenerateBearerTokenController extends Controller
{
    public function generateBearerToken(Request $request)
    {
        // Run your custom Artisan command
        Artisan::call('generate:bearerToken');

        // Redirect back to the Voyager settings page
        return redirect()
            ->route('voyager.settings.index')
            ->with('success', 'Bearer Token Generated Successfully');
    }
}
