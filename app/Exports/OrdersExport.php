<?php

namespace App\Exports;

use App\Models\QsOrder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Facades\Request;
use Carbon\Carbon;

class OrdersExport implements FromQuery, WithHeadings, WithMapping
{
    protected $startDate;
    protected $endDate;

    public function __construct(Carbon $startDate = null, Carbon $endDate = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function query()
    {
        $query = QsOrder::query()
            ->select([
                'qs_orders.*',
                'qs_products.name as product_name',
                'qs_products.discount_percentage as discount_percentage',
                'qs_products.CGST',  // Include CGST
                'qs_products.SGST',  // Include SGST
                'qs_products.IGST'    // Include IGST
            ])
            ->join('qs_products', 'qs_orders.sku', '=', 'qs_products.sku');


        $filter = Request::get('filter');

        if ($filter === 'last_7_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(7));
        } elseif ($filter === 'last_14_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(14));
        } elseif ($filter === 'last_30_days') {
            $query->where('qs_orders.created_at', '>=', now()->subDays(30));
        } elseif ($filter === 'current_month') {
            $query->whereMonth('qs_orders.created_at', now()->month)
                ->whereYear('qs_orders.created_at', now()->year);
        } elseif ($filter === 'last_month') {
            $query->whereMonth('qs_orders.created_at', now()->subMonth()->month)
                ->whereYear('qs_orders.created_at', now()->subMonth()->year);
        } elseif ($filter === 'custom_date_range' && $this->startDate && $this->endDate) {
            $query->whereBetween('qs_orders.created_at', [
                $this->startDate->startOfDay(),
                $this->endDate->endOfDay()
            ]);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Order Date',
            'Order Time',
            'Woohoo Order Number',
            'Order Ref Number',
            'Order Status',
            'Voucher Type',
            'Invoice Number',
            'Customer Name',
            'Customer GSTIN',
            'State',
            'Product Name',
            'Quantity',
            'Denomination',
            'Total Amount',
            'Discount Percentage',
            'Discount Amount',
            'Payable Amount',
            'CGST',                     // Add CGST to headings
            'SGST',                     // Add SGST to headings
            'IGST',                     // Add IGST to headings
            'Payment Id'
        ];
    }

    public function map($order): array
    {


        return [


            Carbon::parse($order->created_at)->format('Y-m-d'),  // Order Date
            Carbon::parse($order->created_at)->format('H:i:s'),  // Order Time
            $order->woohoo_order_id ?? 'N/A',
            $order->refno ?? 'N/A',
            $order->order_status ?? 'N/A',                                 // Order Number
            'B2C',                                               // Voucher Type
            $order->invoice_number ?? 'N/A',                              // Invoice Number
            $order->sender_first_name ?? 'N/A',                 // Customer Name
            $order->gst_number ?? 'N/A',                         // Customer GSTIN
            $order->sender_state ?? 'N/A',                       // State
            $order->product_name,                                // Product Name
            $order->quantity,                                    // Quantity
            $order->denomination,                                // Denomination
            $order->grand_payable_amount,                        // Total Amount
            $order->discount_percentage . '%',                   // Discount Percentage
            $order->discounted_amount_value ?? 'N/A',            // Discount Amount
            $order->amount_payable_after_discount ?? 'N/A',      // Payable Amount
            $order->CGST ?? 'N/A',                               // CGST
            $order->SGST ?? 'N/A',                               // SGST
            $order->IGST ?? 'N/A',                               // IGST
            $order->id                                           // Payment Id
        ];
    }
}
