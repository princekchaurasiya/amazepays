<?php

namespace App\Http\Controllers\Voyager;

use App\Http\Controllers\Controller;
use App\Exports\OrdersExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoyagerOrderExportController extends Controller
{
    public function export(Request $request)
    {
        // Retrieve filter from request
        $filter = $request->input('filter');

        // Determine the date range based on the filter
        switch ($filter) {
            case 'last_7_days':
                $startDate = Carbon::now()->subDays(7)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_14_days':
                $startDate = Carbon::now()->subDays(14)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'last_30_days':
                $startDate = Carbon::now()->subDays(30)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
            case 'current_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now(); // Use current date as the end date for the current month
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom_date_range':
                // Retrieve custom dates from request
                $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(7)->startOfDay();
                $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();
                break;
            default:
                // Default to last 7 days
                $startDate = Carbon::now()->subDays(7)->startOfDay();
                $endDate = Carbon::now()->endOfDay();
                break;
        }

        // Generate filename with the date range
        $fileName = 'order-export-' . $startDate->format('Y-m-d') . '-to-' . $endDate->format('Y-m-d') . '.xlsx';

        // Pass date range to the OrdersExport
        return Excel::download(new OrdersExport($startDate, $endDate), $fileName);
    }
}
