<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QsProduct;

class SearchController extends Controller
{
    public function search(Request $request)
    {

        $query = $request->input('query');
        $results = QsProduct::where('name', 'LIKE', "%$query%")
                            ->orWhere('description', 'LIKE', "%$query%")
                            ->get();

        return view('search.results', compact('results'));
    }
}
