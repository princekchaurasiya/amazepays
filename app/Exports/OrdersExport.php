<?php

namespace App\Exports;

use App\Models\QsOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Facades\Request;

class OrdersExport implements FromQuery, WithHeadings
{
    public function query()
    {
        $query = QsOrder::query()
            ->select([
                'qs_orders.created_at as Date',
                'qs_orders.woohoo_order_id as `Order Number`',
                \DB::raw('"B2C" as `Voucher Type`'),
                'qs_orders.invoice_number as `Invoice Number`',
                'qs_orders.sender_first_name as `Customer Name`',
                'qs_orders.gst_number as `Customer GSTIN`',
                'qs_orders.sender_state as State',
                'qs_products.name as `Product Name`', // Adjust the column name as per your actual column name in qs_products
                'qs_orders.quantity as Quantity',
                'qs_orders.denomination as Denomination',
                'qs_orders.grand_payable_amount as Total Amount',
                \DB::raw('CONCAT(qs_products.discount_percentage, "%") as `Discount Percentage`'), // Adding percentage symbol
                'qs_orders.discounted_amount_value as Discount Amount',
                'qs_orders.amount_payable_after_discount as `Payable Amount`',
                'qs_orders.id as `Payment Id`',
            ])
            ->join('qs_products', 'qs_orders.sku', '=', 'qs_products.sku') // Use qs_products table
            ->where('qs_orders.order_status', 'COMPLETE')
            ->whereNotNull('qs_orders.order_status');

        $filter = Request::get('filter');

        if ($filter == 'last_7_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(7));
        } elseif ($filter == 'last_14_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(14));
        } elseif ($filter == 'last_30_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(30));
        } elseif ($filter == 'current_month') {
            $query->whereMonth('qs_orders.created_at', now()->month)->whereYear('qs_orders.created_at', now()->year);
        } elseif ($filter == 'last_month') {
            $query->whereMonth('qs_orders.created_at', now()->subMonth()->month)->whereYear('qs_orders.created_at', now()->subMonth()->year);
        } elseif ($filter == 'custom_date_range') {
            $startDate = Request::get('start_date');
            $endDate = Request::get('end_date');
            if ($startDate && $endDate) {
                $query->whereBetween('qs_orders.created_at', [$startDate, $endDate]);
            }
        }

        return $query;
    }

    public function headings(): array
    {
        return ['Date', 'Order Number', 'Voucher Type', 'Invoice Number', 'Customer Name', 'Customer GSTIN', 'State', 'Product Name', 'Quantity', 'Denomination', 'Total Amount', 'Discount Percentage', 'Discount Amount', 'Payable Amount', 'Payment Id'];
    }
}
