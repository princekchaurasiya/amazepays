<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Billing;

class BillingController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'billing_name' => 'required|string|max:255',
            'billing_email' => 'required|email|max:255',
            'billing_tel' => 'nullable|string|max:50',
            'billing_zip' => 'nullable|string|max:20',
            'billing_address' => 'nullable|string|max:255',
            'billing_address_two' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|max:100',
            'billing_gst_number' => 'nullable|string|max:50',
        ]);

        Billing::create($validated);

        return response()->json(['message' => 'Billing data saved to the database.']);
    }

}
