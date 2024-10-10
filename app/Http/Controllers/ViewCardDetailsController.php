<?php

namespace App\Http\Controllers;
use App\Models\QsOrder;

use Illuminate\Http\Request;

class ViewCardDetailsController extends Controller
{
    public function index(Request $request)
    {

        $orderId = $request->input('orderId');
        $imgDetail = $request->input('imageDetail');
        \Log::info('Woohoo! Order ID: ' . $orderId .$imgDetail) ;

        $orderData = QsOrder::where('woohoo_order_id', $orderId)->first();

        // $cardData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')), true);

        // \Log::info('Encryption Key: ' . env('ENCRYPTION_KEY'));

        $cardsData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')));

        // $cardData =  {{ dd(decrypt($orderItem->cards, env('ENCRYPTION_KEY'))) }};

        return view('order.viewCard')->with(['cardArray' => $cardsData, 'imgDetail' => $imgDetail]);

    }
}
