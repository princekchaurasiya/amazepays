<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\QsProduct;
use App\Helpers\CommonHelper;
use Exception;

class ProductSlugController extends Controller
{
    public function getProductBySlug(Request $request)
    {
        try {
            $product = QsProduct::where('url', $request->slug)->first();
            // dd($product);

            if (!$product) {
                return view('errors.404');
            }

            // Access attributes first to trigger model accessors (auto-decode JSON fields)
            // Then convert to array - this ensures price, currency, images are properly decoded
            $productDetails = $product->toArray();
            
            // Override with accessor values to ensure JSON fields are decoded
            $productDetails['price'] = $product->price;
            $productDetails['images'] = $product->images;
            $productDetails['currency'] = $product->currency;

            // Decode howToUse safely from `cpg`
            $decodedHowToUse = !empty($productDetails['amazepay_how_to_redeem'])
            ? $productDetails['amazepay_how_to_redeem']
            : (!empty($productDetails['cpg'])
                ? $this->parseHowToUse($productDetails['cpg'])
                : 'No how to redeem available.');

            $descriptionData = !empty($productDetails['amazepay_product_description'])
    ? CommonHelper::extractDescription($productDetails['amazepay_product_description'])
    : (!empty($productDetails['description'])
        ? CommonHelper::extractDescription($productDetails['description'])
        : 'No description available.');


            $tncData = !empty($productDetails['amazepay_t_and_c'])
    ? $productDetails['amazepay_t_and_c']
    : (!empty($productDetails['tnc'])
        ? (is_string($productDetails['tnc'])
            ? json_decode($productDetails['tnc'], true)
            : $productDetails['tnc'])
        : null);



            $formattedTncData = CommonHelper::extractTnc($tncData);
            $formatteddecodedHowToUse = CommonHelper::extractHowToRedeem($decodedHowToUse);

            return view('userpanel.productPage', compact(
                'productDetails',
                'formattedTncData',
                'descriptionData',
                'formatteddecodedHowToUse'
            ));
        } catch (Exception $e) {
            Log::error('Error fetching product by slug: ' . $e->getMessage());
            return view('userpanel.wentWrong')->with('errorMessage', 'Something Went Wrong. Please try again later.');
        }
    }

    private function parseHowToUse($cpgString)
    {
        try {
            if (!$cpgString) {
                return null;
            }

            $pattern = '/s:8:"howToUse";s:\d+:"([^"]+)"/';
            if (preg_match($pattern, $cpgString, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
