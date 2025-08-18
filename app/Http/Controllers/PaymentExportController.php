<?php

namespace App\Http\Controllers;

use App\Exports\PaymentsExport;
use Maatwebsite\Excel\Facades\Excel;

class PaymentExportController extends Controller
{
    public function export()
    {
        return Excel::download(new PaymentsExport, 'payments.xlsx');
    }
}

