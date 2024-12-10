<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

            // Decode howToUse safely from `cpg`
            $decodedHowToUse = $this->parseHowToUse($productDetails['cpg']);



            Log::info('Decoded howToUse', ['decodedHowToUse' => $decodedHowToUse]);

            $productDetails['price'] = isset($productDetails['price']) ? json_decode($productDetails['price']) : null;
            $productDetails['images'] = isset($productDetails['images']) ? json_decode($productDetails['images']) : [];


            $descriptionData = isset($productDetails['description'])
                ? $productDetails['description']
                : "No description available.";



                // $tncData = is_string($productDetails['tnc'])
                // ? json_decode($productDetails['tnc'])
                // : $productDetails['tnc'];


            $tncData = is_string($productDetails['tnc'])
                ? json_decode($productDetails['tnc'], true) // Decode JSON as associative array
                : $productDetails['tnc'];




                $decodedHowToUse = $this->parseHowToUse($productDetails['cpg']);



                $formatteddecodedHowToUse = extractHowToRedeem( $decodedHowToUse);





                $formattedTncData = extractTnc($tncData);



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

    /**
     * Parses the 'cpg' data for the howToUse property safely.
     */
    private function parseHowToUse($cpgString)
    {
        try {
            if (!$cpgString) {
                Log::info('Empty CPG string provided.');
                return null;
            }

            Log::info('Raw CPG String', ['cpgString' => $cpgString]);

            // Attempt regex extraction for 'howToUse'
            $pattern = '/s:8:"howToUse";s:\d+:"([^"]+)"/';

            if (preg_match($pattern, $cpgString, $matches)) {
                Log::info('Regex match successful', ['howToUse' => $matches[1]]);
                return $matches[1];
            }

            Log::info('Regex failed to match the howToUse string.');
            return null;

        } catch (\Exception $e) {
            Log::error('Unexpected error during regex extraction', ['error' => $e->getMessage()]);
            return null;
        }
    }


    /**
     * Safely extracts howToUse from the redeemData array.
     */
    private function extractHowToUse($redeemData)
    {
        try {
            if (isset($redeemData['cpg'])) {
                $cpgDecoded = @unserialize($redeemData['cpg']);
                if ($cpgDecoded && isset($cpgDecoded['howToUse'])) {
                    Log::info('Decoded howToUse from redeemData', ['howToUse' => $cpgDecoded['howToUse']]);
                    return $cpgDecoded['howToUse'];
                }

                Log::info('No valid howToUse found in decoded data.');
            } else {
                Log::info('No cpg data available in redeemData to decode.');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to decode redeemData for howToUse.', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
