<?php

namespace App\Http\Controllers;
use App\Models\QsOrder;

use Illuminate\Http\Request;

class ViewCardDetails extends Controller
{
    public function index(Request $request)
    {
        $orderId = $request->input('orderId');
        \Log::info('Woohoo! Order ID: ' . $orderId);
        $orderData = QsOrder::where('woohoo_order_id', $orderId)->first();


        $recentOrders = User::join('qs_ordered', 'users.id', '=', 'qs_ordered.user_id')
    ->join('qs_products', 'qs_ordered.sku', '=', 'qs_products.sku')
    ->where('users.id', $user->id)
    ->orderBy('qs_ordered.created_at', 'desc')
    ->get(['qs_ordered.*', 'qs_products.*']);



        // $cardData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')), true);
        \Log::info('Encrypted Data: ' . $orderData->cards);

        \Log::info('Encryption Key: ' . env('ENCRYPTION_KEY'));


        $cardsData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')));


        // $cardData =  {{ dd(decrypt($orderItem->cards, env('ENCRYPTION_KEY'))) }};
        dd($cardsData);
    }
}
