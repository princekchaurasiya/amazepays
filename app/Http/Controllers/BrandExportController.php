<?php

namespace App\Http\Controllers;

use App\Exports\BrandsExport;
use Maatwebsite\Excel\Facades\Excel;

class BrandExportController extends Controller
{
    public function export()
    {
        return Excel::download(new BrandsExport, 'brands.xlsx');
    }
}
