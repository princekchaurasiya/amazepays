<?php

namespace App\Http\Controllers;

use App\Exports\PaymentDetailsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;


class PaymentDetailsExportController extends Controller
{
    public function export()
    {
        return Excel::download(new paymentDetailsExport, 'order.xlsx');
    }
}
