<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Log;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\QsProduct;
use Exception;

class ProductSlugController extends Controller
{
    // Product SKU API for Woohoo should be called once only
    public function getProductBySlug(Request $request)
    {
        try {
            $productDetails = QsProduct::where('slug', $request->slug)
                ->first()
                ->toArray();

            // Convert certain JSON fields back to objects
            $productDetails['price'] = json_decode($productDetails['price']);
            $productDetails['images'] = json_decode($productDetails['images']);
            $productDetails['tnc'] = json_decode($productDetails['tnc']);

            // Pass the product details to the view and render it
            return view('userpanel.productPage', compact('productDetails'));
        } catch (Exception $e) {
            // Log the error message
            Log::error('Error fetching product by slug: ' . $e->getMessage());

            // Return an error message if an exception occurs during the process
            return view('order.order-status', ['transactionStatusMessage' => $e->getMessage()]);
        }
    }
}
