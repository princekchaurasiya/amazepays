<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AesHelper;
use App\Models\VDBrandDetail;

class VDAESdecrptController extends Controller
{
    public function decryptAndStoreBrandData()
    {
        $key = env('AES_KEY');
        $iv = env('AES_IV');

        $encryptedPayload = 'your-encrypted-payload-here'; // usually from API response

        $json = AesHelper::decryptPayload($encryptedPayload, $key, $iv);

        if (!$json) {
            return response()->json(['error' => 'Decryption failed']);
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return response()->json(['error' => 'Invalid decrypted data']);
        }

        foreach ($data as $brand) {
            $images = json_decode(str_replace("'", '"', $brand['Images']), true); // fix single quotes

            BrandDetail::updateOrCreate(
                ['brand_code' => $brand['BrandCode']],
                [
                    'brand_name' => $brand['BrandName'],
                    'discount' => $brand['Discount'] ?? null,
                    'brand_type' => $brand['Brandtype'] ?? null,
                    'denominations' => $brand['DenominationList'] ?? null,
                    'stock' => $brand['StockAvailable'] ?? 0,
                    'category' => $brand['Category'] ?? null,
                    'description' => $brand['Description'] ?? null,
                    'thumbnail_image' => $images['thumbnail'] ?? null,
                    'featured_image' => $images['featured'] ?? null,
                    'terms' => json_encode($brand['ImportantInstruction'] ?? []),
                    'redeem_steps' => json_encode($brand['RedeemSteps'] ?? []),
                ]
            );
        }

        return response()->json(['success' => 'Decrypted data stored successfully']);
    }

    public function showBrands()
    {
        $brands = BrandDetail::all();
        return view('brands.list', compact('brands'));
    }
}
