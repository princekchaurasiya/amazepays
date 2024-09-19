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
    public function getProductBySlug(Request $request)
    {

        try {
            $product = QsProduct::where('slug', $request->slug)->first();


            if (!$product) {
                // If product is not found, show a friendly error message
                // abort(404);

                // return view('userpanel.wentWrong')->with('errorMessage', 'Requested product does not exist. Please try with a different product.');

                return view('errors.404');
            }

            $productDetails = $product->toArray();
            $productDetails['price'] = json_decode($productDetails['price']);
            $productDetails['images'] = json_decode($productDetails['images']);
            $productDetails['tnc'] = json_decode($productDetails['tnc']);

            // Use dd to inspect the productDetails


            // Check if price contains a valid type attribute
            if (!isset($productDetails['price']->type) || !in_array($productDetails['price']->type, ['RANGE', 'SLAB'])) {
                log::error('Product details are not available. Please try with a different product.');
                return view('userpanel.wentWrong')->with('errorMessage', 'Product details are not available.  go to homepage and try with a different product.');
            }
            else{
                log::info('Product avaialble');
                return view('userpanel.productPage', compact('productDetails'));
            }



        } catch (Exception $e) {
            Log::error('Error fetching product by slug: ' . $e->getMessage());

            return view('userpanel.wentWrong')->with('errorMessage', 'Something Went Wrong. Please try again later.');
        }
    }
}
