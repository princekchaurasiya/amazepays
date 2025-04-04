<?php

namespace App\Http\Controllers;
use App\Models\QsOrder;
use App\Models\QsProduct;
use App\Helpers\CommonHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ViewCardDetailsController extends Controller
{
    public function index(Request $request, $orderId = null)
    {
        try {
            // Get orderId from route parameter if not provided in request
            $orderId = $orderId ?? $request->input('orderId');
            
            if (!$orderId) {
                return redirect()->back()->with('error', 'Invalid order ID');
            }

            // Get order with product details
            $orderData = QsOrder::with('product')
                ->where('woohoo_order_id', $orderId)
                ->where('user_id', auth()->id()) // Ensure user can only view their own orders
                ->firstOrFail();

            // Decrypt card data
            try {
                $cardsData = json_decode(decrypt($orderData->cards, env('ENCRYPTION_KEY')));
            } catch (\Exception $e) {
                Log::error('Card decryption failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Unable to retrieve card details');
            }

            // Get product image using CommonHelper
            $productImage = null;
            if ($orderData->product) {
                $productImage = CommonHelper::getProductImage($orderData->product);
            }

            return view('order.viewCard')->with([
                'cardArray' => $cardsData,
                'productImage' => $productImage,
                'order' => $orderData
            ]);

        } catch (\Exception $e) {
            Log::error('View card details error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Unable to retrieve order details');
        }
    }
}
