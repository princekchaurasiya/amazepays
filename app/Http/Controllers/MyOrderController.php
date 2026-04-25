<?php

namespace App\Http\Controllers;

use App\Helpers\ProductImageHelper;
use App\Models\Order;
use App\Models\User;
use Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class MyOrderController extends Controller
{
    public function displayOrder()
    {
        $user = Auth::user();

        $recentOrders = Order::join('users', 'users.id', '=', 'orders.user_id')
            ->join('products as qsp', 'orders.sku', '=', 'qsp.sku')
            ->leftJoin('storefront_brands as ab', 'qsp.brand_id', '=', 'ab.id')
            ->where('users.id', $user->id)
            ->orderBy('orders.created_at', 'desc')
            ->select(
                'orders.id as order_id',
                'orders.woohoo_order_id',
                'orders.refno',
                'orders.order_status',
                'orders.denomination',
                'orders.quantity',
                'orders.discounted_amount_value',
                'orders.amount_payable_after_discount',
                'qsp.name as product_name',
                'ab.name as brand_name',
                'qsp.brandName',
                'qsp.sku',
                'qsp.custom_image',
                'qsp.images'
            )
            ->get();

        // Transform the data to handle image URLs using ProductImageHelper
        $recentOrders->transform(function ($order) {
            // Create a temporary object with the necessary fields for ProductImageHelper
            $productData = (object) [
                'custom_image' => $order->custom_image,
                'images' => $order->images,
            ];

            $order->display_image = ProductImageHelper::getProductImage($productData);

            $orderStatus = strtoupper((string) ($order->order_status ?? ''));
            $order->is_clickable = ! empty($order->woohoo_order_id) && in_array($orderStatus, ['COMPLETE', 'PAID'], true);
            $order->is_faded = ! $order->is_clickable && ! in_array($orderStatus, ['COMPLETE', 'PAID'], true);
            $order->view_card_url = $order->is_clickable
                ? route('view-card-details', ['orderId' => $order->woohoo_order_id])
                : null;

            return $order;
        });

        return Inertia::render('Storefront/Orders', [
            'orders' => $recentOrders->map(function ($o) {
                return [
                    'order_id' => $o->order_id,
                    'product_name' => $o->product_name,
                    'brand_name' => $o->brand_name ?? $o->brandName,
                    'order_status' => $o->order_status,
                    'amount_payable_after_discount' => $o->amount_payable_after_discount,
                    'view_card_url' => $o->view_card_url,
                    'display_image' => $o->display_image,
                ];
            })->values()->all(),
        ]);
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

        return Inertia::render('Storefront/VdOrders', [
            'orders' => $recentOrders->map(fn ($o) => (array) $o)->values()->all(),
        ]);
    }
}
