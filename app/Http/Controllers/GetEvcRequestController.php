<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\GetEvcRequest;

class GetEvcRequestController extends Controller
{
    public function create()
    {
        return view('get_evc_requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_of_card' => 'required|integer|min:1',
            'amount' => 'required|numeric',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'pincode' => 'required|string',
            'curr' => 'required|string',
        ]);

        // Generate IDs automatically
        $validated['order_id'] = Str::upper(Str::random(20)); // Example: PQL98PQ9IUISPQQID74
        $validated['receipt_no'] = Str::upper(Str::random(12)); // Example: V9IQUZAOJLIY
        $validated['req_id'] = Str::upper(Str::random(16)); // Example: 1LLZAOJU92YTkk55
        $validated['distributor_id'] = "VDAmazepay97";
        $validated['sku_code'] = "VD-KFC";
        GetEvcRequest::create($validated);

        return redirect()->back()->with('success', 'Request saved successfully!');
    }
}
