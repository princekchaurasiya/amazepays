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

        $recentOrders = User::join('qs_ordered', 'users.id', '=', 'qs_ordered.user_id')
            ->join('qs_products', 'qs_ordered.sku', '=', 'qs_products.sku')
            ->where('users.id', $user->id)
            ->orderBy('qs_ordered.created_at', 'desc')
            ->get(['qs_ordered.*', 'qs_products.*']);

        foreach ($recentOrders as $order) {
            return view('order.myOrder')->with('order', $order);
        }


    }
}
