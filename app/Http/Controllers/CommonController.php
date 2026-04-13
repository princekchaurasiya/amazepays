<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommonController extends Controller
{
    public function checkData(Request $request)
    {
        return response()->json([
            'status' => true,
            'received_keys' => array_keys($request->all()),
        ]);
    }
}
