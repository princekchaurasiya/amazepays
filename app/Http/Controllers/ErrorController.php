<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ErrorController extends Controller
{
    public function handleError(Request $request)
    {

        $errorMessage = $request->get('errorMessage', 'An error occurred');

        // Log the error message
        Log::info('Error occurred: ' . $errorMessage);

        return view('userpanel.wentWrong', compact('errorMessage'));
    }
}
