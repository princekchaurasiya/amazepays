<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnlimitCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();

        Log::info('Valid Unlimit callback received', $data);

        // Process callback (e.g. update payment status)
        // Example: Payment::where('transaction_id', $data['id'])->update(['status' => $data['status']]);

        return response()->json(['status' => 'ok']);
    }
}
