<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function handleError(Request $request)
    {
        $errorMessage = $request->get('errorMessage', 'An error occurred');
        return view('userpanel.wentWrong', compact('errorMessage'));
    }
}
