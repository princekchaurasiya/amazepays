<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\QsOrder;
use App\Models\User;
use App\Models\CcAvenuePayment;
use Auth;

class MyOrderController extends Controller
{
    public function displayOrder()
    {
        $user = Auth::user();

        $recentOrders = QsOrder::join('users', 'users.id', '=', 'qs_orders.user_id')
            ->join('qs_products as qsp', 'qs_orders.sku', '=', 'qsp.sku')
            ->where('users.id', $user->id)
            ->orderBy('qs_orders.created_at', 'desc')
            ->select('qs_orders.id as qs_id', 'qs_orders.*', 'qsp.*', 'users.*')
            ->get();

        return view('order.myOrder')->with('order', $recentOrders);
    }
}
