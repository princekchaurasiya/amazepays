<?php

namespace App\Http\Controllers;

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

        $recentOrders = Order::query()
            ->joinSub(
                DB::table('order_items')
                    ->selectRaw('order_id, MIN(id) as first_item_id')
                    ->groupBy('order_id'),
                'first_item',
                fn ($join) => $join->on('first_item.order_id', '=', 'orders.id')
            )
            ->join('order_items as oi', 'oi.id', '=', 'first_item.first_item_id')
            ->join('products as qsp', 'oi.product_id', '=', 'qsp.id')
            ->leftJoinSub(
                DB::table('product_media')
                    ->selectRaw('product_id, MIN(url) as image_url')
                    ->groupBy('product_id'),
                'pm',
                fn ($join) => $join->on('pm.product_id', '=', 'qsp.id')
            )
            ->leftJoin('brands as ab', 'qsp.brand_id', '=', 'ab.id')
            ->leftJoin('provider_orders as po', function ($join) {
                $join->on('po.order_id', '=', 'orders.id')
                    ->where('po.provider', '=', 'woohoo');
            })
            ->where('orders.user_id', $user->id)
            ->orderBy('orders.created_at', 'desc')
            ->select([
                'orders.id as order_id',
                'orders.order_number as refno',
                'orders.status as order_status',
                DB::raw('(oi.unit_amount_minor / 100) as denomination'),
                'oi.quantity',
                DB::raw('NULL as discounted_amount_value'),
                DB::raw('(orders.grand_total_minor / 100) as amount_payable_after_discount'),
                DB::raw('qsp.name as product_name'),
                'ab.name as brand_name',
                DB::raw('oi.sku_snapshot as sku'),
                DB::raw('pm.image_url as image_url'),
                DB::raw('po.provider_order_id as woohoo_order_id'),
            ])
            ->get();

        // Attach display image (Phase-3 schema: product_media.url).
        $recentOrders->transform(function ($order) {
            $order->display_image = $order->image_url ?? null;

            $orderStatus = strtolower((string) ($order->order_status ?? ''));
            $order->is_clickable = ! empty($order->woohoo_order_id) && in_array($orderStatus, ['fulfilled', 'completed', 'complete', 'paid'], true);
            $order->is_faded = ! $order->is_clickable && ! in_array($orderStatus, ['fulfilled', 'completed', 'complete', 'paid'], true);
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
                    'brand_name' => $o->brand_name,
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
