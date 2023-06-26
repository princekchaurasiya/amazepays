<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\QsOrder;
use App\Models\CcAvenuePayment;
use Auth;

class MyOrderController extends Controller
{
    public function displayOrder()
    {
        $orders = QsOrder::select('cc_avenue_payment.tracking_id as tracking_id','cc_avenue_payment.bank_ref_no as bank_ref_no','cc_avenue_payment.amount as amount','qs_ordered.*')->leftjoin('cc_avenue_payment','qs_ordered.order_id','=','cc_avenue_payment.order_id')->where([['qs_ordered.user_id', Auth::id()],['qs_ordered.order_status','complete']])->get();
        $orderDetails = array_reverse($orders->toArray());
        // $orders = CcAvenuePayment::where('user_id', Auth::id())->get();
        // $orderDetails = $orders->toArray();
        // $orderDetails = array_reverse($orderDetails);
        
        return view('order.myOrder', compact('orderDetails'));
    }
}
