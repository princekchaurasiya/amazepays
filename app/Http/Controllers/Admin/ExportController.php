<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BrandsExport;
use App\Exports\PaymentDetailsExport;
use App\Exports\PaymentsExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

final class ExportController extends Controller
{
    public function brands()
    {
        return Excel::download(new BrandsExport, 'brands.xlsx');
    }

    public function payments()
    {
        return Excel::download(new PaymentsExport, 'payments.xlsx');
    }

    public function paymentDetails()
    {
        return Excel::download(new PaymentDetailsExport, 'order.xlsx');
    }
}
