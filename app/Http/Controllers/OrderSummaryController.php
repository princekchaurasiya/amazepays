<?php

namespace App\Http\Controllers;

use App\Models\OrderSummary;

class OrderSummaryController extends Controller
{
    public function index()
    {
        $orderSummaries = OrderSummary::with(['order', 'payment'])->get();

        return view('order_summary.index', compact('orderSummaries'));
    }
}

