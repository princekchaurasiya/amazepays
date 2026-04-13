<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => [
                'required',
                'integer',
                Rule::exists('orders', 'id')->where(fn ($q) => $q->where('user_id', Auth::id())),
            ],
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

        $orderId = (int) $validated['order_id'];
        unset($validated['order_id']);

        if (Schema::hasColumn('billings', 'order_id')) {
            Billing::updateOrCreate(
                ['order_id' => $orderId],
                $validated
            );
        } else {
            Billing::create($validated);
        }

        return response()->json(['message' => 'Billing data saved to the database.']);
    }
}
