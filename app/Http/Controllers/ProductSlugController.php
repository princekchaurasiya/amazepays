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
            $product = QsProduct::where('url', $request->slug)->first();

            if (!$product) {
                return view('errors.404');
            }

            $productDetails = $product->toArray();
            $productDetails['price'] = json_decode($productDetails['price']);
            $productDetails['images'] = json_decode($productDetails['images']);
            $productDetails['tnc'] = json_decode($productDetails['tnc']);

            // Ensure `minPrice` and `maxPrice` are available and valid
            if (
                !isset($productDetails['minPrice']) || !isset($productDetails['maxPrice']) ||
                $productDetails['minPrice'] <= 0 || $productDetails['maxPrice'] <= 0
            ) {
                Log::error('Invalid or missing min/max price for product ID: ' . $productDetails['id']);
                return view('userpanel.wentWrong')->with(
                    'errorMessage',
                    'Product price details are invalid. Please go to the homepage and try with a different product.'
                );
            }

            // Handle case where minPrice and maxPrice are the same
            if ($productDetails['minPrice'] === $productDetails['maxPrice']) {
                Log::info('Product has identical minPrice and maxPrice for product ID: ' . $productDetails['id']);
                $productDetails['priceRange'] = ['singlePrice' => $productDetails['minPrice']];
            } else {
                // Valid range, pass minPrice and maxPrice
                $productDetails['priceRange'] = [
                    'minPrice' => $productDetails['minPrice'],
                    'maxPrice' => $productDetails['maxPrice']
                ];
            }

            // Check if `price->type` is valid if it exists
            if (isset($productDetails['price']->type) && !in_array($productDetails['price']->type, ['RANGE', 'SLAB'])) {
                Log::warning('Invalid product price type: ' . $productDetails['price']->type . ' for product ID: ' . $productDetails['id']);
            }

            // If `price->type` is not present, log the absence and continue
            if (!isset($productDetails['price']->type)) {
                Log::info('Price type is missing for product ID: ' . $productDetails['id'] . '. Using minPrice and maxPrice for validation.');
            }

            // Render the product page
            Log::info('Product available with valid price details. Rendering product page for product ID: ' . $productDetails['id']);
            return view('userpanel.productPage', compact('productDetails'));

        } catch (Exception $e) {
            Log::error('Error fetching product by slug: ' . $e->getMessage());
            return view('userpanel.wentWrong')->with('errorMessage', 'Something Went Wrong. Please try again later.');
        }

    }
}
