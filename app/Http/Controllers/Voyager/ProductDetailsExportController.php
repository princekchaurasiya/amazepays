<?php




namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductDetailsExport;
use Carbon\Carbon;


class ProductDetailsExportController extends Controller
{
    public function export()
    {
        $timestamp = Carbon::now()->format('d-m-Y_H-i-s');
        $filename = "product-details_{$timestamp}.xlsx";

        return Excel::download(new ProductDetailsExport, $filename);
    }
}
