<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\QsOrder;
use App\Models\User;
use App\Models\CcAvenuePayment;
use App\Helpers\CommonHelper;
use Auth;

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
}
