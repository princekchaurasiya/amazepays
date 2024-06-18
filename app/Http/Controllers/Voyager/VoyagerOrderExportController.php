<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class VoyagerOrderExportController extends Controller
{
    public function export(Request $request)
    {
        $fileName = 'order-export.xlsx';
        return Excel::download(new OrdersExport, $fileName);
    }
}
