<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\QsProduct;
use DB;
class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');
        // $results = QsProduct::where('show_product', true)->where(function ($q) use ($query) {
        //     $q->where('name', 'LIKE', "%$query%")->orWhere('description', 'LIKE', "%$query%"); })->get();

        //kev
        // $results = QsProduct::where('show_product', true)->where('name', 'LIKE', "%$query%")->orWhere('description', 'LIKE', "%$query%")->get();
        $orderByClause  = "CASE WHEN name LIKE '%".$query."%' THEN 0 ELSE 1 END, ";
        $orderByClause .= "CASE WHEN description LIKE '%".$query."%' THEN 0 ELSE 1 END";

        $results = DB::table('qs_products')
        ->where('name', 'LIKE', "%$query%")
        ->orWhere('description', 'LIKE', "%$query%")
        ->orderByRaw($orderByClause)->get();
        //dd($results);

        //kev
        return view('search.results', compact('results'));
    }
}
