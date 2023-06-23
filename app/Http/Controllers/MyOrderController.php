<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\QsOrder;
use Auth;

class MyOrderController extends Controller
{
    public function displayOrder()
    {   
        $orders = QsOrder::where('user_id', Auth::id())->get();
        // dd($orders);
        foreach ($orders as $key => $value) {
           $value->product  = json_decode($value["product"]);
            // foreach ($cards as $k => $card) {
            //     $card->c = $card->code;
            // }
            // dd($cards[0]->code);

            // foreach ($value["product"] as $prdt) {
            //     # code...
            // }
        }
        // dd($orders->produc);
        // $jsonData = $orders->product;
        // dd($jsonData);
        // $orders = QsOrder::all();
        // dd($orders);
        return view('order.myOrder', compact('orders'));
        // return view('order.myOrder')->with('orders', json_decode($orders, true));;
        // return 'nfjdhfgdlkgjridfkhjirtklfjhtklgjlk';
    }
}
