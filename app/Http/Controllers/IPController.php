<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IPController extends Controller
{ 
    public function storeIp(Request $request)
    {
        UserIp::create([
            'user_id' => auth()->id(), // or null if guest
            '$ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'IP address saved to database']);
    }
    
}
