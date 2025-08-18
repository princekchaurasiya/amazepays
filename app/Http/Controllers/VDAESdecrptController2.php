<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\VDWebApiService;
use App\Helpers\AesHelper;
use App\Models\BrandDetail;
use Illuminate\Support\Facades\Crypt;
use Exception;

class VDAESdecrptController2 extends Controller
{
    public function handleEncryptedPayload(VDWebApiService $vdWebApiService)
    {
        // Step 1: Get token
        $tokenResponse = $vdWebApiService->generateToken();

        if (!$tokenResponse || !isset($tokenResponse['token'])) {
            return response()->json(['error' => 'Token generation failed']);
        }

        $token = $tokenResponse['token'];

        // Step 2: Get encrypted payload using token
        $encryptedPayload = $vdWebApiService->getEncryptedPayload($token);

        if (!$encryptedPayload) {
            return response()->json(['error' => 'Failed to retrieve payload']);
        }

        // Step 3: Decrypt payload
        $key = env('AES_KEY');
        $iv = env('AES_IV');

        $json = \App\Helpers\AesHelper::decryptPayload($encryptedPayload, $key, $iv);

        if (!$json) {
            try {
            return openssl_decrypt(
                base64_decode($encrypted),
                'AES-256-CBC',
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            } catch (Exception $e) {
                return false;
            }
            return response()->json(['error' => 'Decryption failed']);
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return response()->json(['error' => 'Invalid decrypted structure']);
        }

        // Step 4: Save to DB
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

        return response()->json(['success' => 'Brands synced and decrypted successfully']);
    }
}
