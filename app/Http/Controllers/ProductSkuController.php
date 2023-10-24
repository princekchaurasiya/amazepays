<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\CommonHelper;
use Illuminate\Support\Facades\Log;
use DB;
use Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\QsProductSkuDetail;
use App\QsProduct;

class ProductSkuController extends Controller
{
    //prodcut sku api for woohoo should be called once only
    public function getProductbySKU(Request $request)
    {
        try {
            $productSku = $request->slug;
            $getprdtDetails = QsProduct::where('sku', '=',  $productSku)
                ->first()
                ->toArray();

            // dd($getprdtDetails);

            // Convert certain JSON fields back to objects
            $getprdtDetails['price'] = json_decode($getprdtDetails['price']);
            $getprdtDetails['images'] = json_decode($getprdtDetails['images']);
            $getprdtDetails['tnc'] = json_decode($getprdtDetails['tnc']);

            // Pass the product details to the view and render it
            return view('userpanel/gift_card_detail_page', compact('getprdtDetails'));
        } catch (Exception $e) {
            // Return an error message if an exception occurs during the process
            return $e->getMessage();
        }
    }
}
