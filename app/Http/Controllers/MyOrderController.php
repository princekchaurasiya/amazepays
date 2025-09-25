<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\QsOrder;
use App\Models\User;

use App\Helpers\CommonHelper;
use Auth;
use Illuminate\Support\Facades\DB;

class MyOrderController extends Controller
{
    public function displayOrder()
    {
        $user = Auth::user();

        $recentOrders = QsOrder::join('users', 'users.id', '=', 'qs_orders.user_id')
            ->join('qs_products as qsp', 'qs_orders.sku', '=', 'qsp.sku')
            ->leftJoin('amazepay_available_brands as ab', 'qsp.brand_id', '=', 'ab.id')
            ->where('users.id', $user->id)
            ->orderBy('qs_orders.created_at', 'desc')
            ->select(
                'qs_orders.id as qs_id',
                'qs_orders.woohoo_order_id',
                'qs_orders.refno',
                'qs_orders.order_status',
                'qs_orders.cards',
                'qs_orders.denomination',
                'qs_orders.quantity',
                'qs_orders.discounted_amount_value',
                'qs_orders.amount_payable_after_discount',
                'qsp.name as product_name',
                'ab.name as brand_name',
                'qsp.brandName',
                'qsp.sku',
                'qsp.custom_image',
                'qsp.images'
            )
            ->get();

        // Transform the data to handle image URLs using CommonHelper
        $recentOrders->transform(function ($order) {
            // Create a temporary object with the necessary fields for CommonHelper
            $productData = (object)[
                'custom_image' => $order->custom_image,
                'images' => $order->images
            ];
            
            $order->display_image = CommonHelper::getProductImage($productData);
            return $order;
        });

        return view('order.myOrder')->with('order', $recentOrders);
    }

    public function displayValueDesignOrder()
{
    $user = Auth::user();

    $recentOrders = DB::table('get_evc_requests')
        ->where('email', $user->email) // assuming email links to user
        ->orderBy('created_at', 'desc')
        ->select(
            'order_id',
            'distributor_id',
            'sku_code',
            'no_of_card',
            'amount',
            'receipt_no',
            'req_id',
            'firstname',
            'lastname',
            'email',
            'mobile_no',
            'address',
            'city',
            'state',
            'country',
            'pincode',
            'curr',
            'gift_send_option',
            'delivery_mode',
            'receiver_name',
            'receiver_email',
            'receiver_mobile',
            'receiver_msg',
            'vd_discount',
            'vd_brand_code'
        )
        ->get();

    // Attach static glam logo image
    $recentOrders->transform(function ($order) {
        $order->display_image = asset('images/glam_logo.png');
        return $order;
    });

    return view('order.myValueDesignOrder')->with('order', $recentOrders);
}

}
