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

        $recentOrders = QsOrder::join('users', 'users.id', '=', 'qs_ordered.user_id')
            ->join('qs_products as qsp', 'qs_ordered.sku', '=', 'qsp.sku')
            ->where('users.id', $user->id)
            ->orderBy('qs_ordered.created_at', 'desc')
            ->select('qs_ordered.id as qs_id', 'qs_ordered.*', 'qsp.*', 'users.*')
            ->get();

        return view('order.myOrder')->with('order', $recentOrders);
    }
}
