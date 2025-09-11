<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\GetEvcRequest;

class GetEvcRequestController extends Controller
{
    public function create()
    {
        // Store the gift card form data in database if it exists in query parameters
        if (request()->has('denomination') && request()->has('quantity')) {
            $this->storeGiftCardData();
        }
        
        return view('get_evc_requests.create');
    }

    private function storeGiftCardData()
    {
        try {
            // Create a new record with the gift card data
            $giftCardData = [
                'no_of_card' => request('quantity', 1),
                'amount' => request('denomination', 100),
                'firstname' => 'Gift Card',
                'lastname' => 'Order',
                'email' => 'giftcard@example.com',
                'mobile_no' => '0000000000',
                'address' => 'Gift Card Address',
                'city' => 'Gift Card City',
                'state' => 'Gift Card State',
                'country' => 'IN',
                'pincode' => '000000',
                'curr' => 'INR',
                'order_id' => Str::upper(Str::random(20)),
                'receipt_no' => Str::upper(Str::random(12)),
                'req_id' => Str::upper(Str::random(16)),
                'distributor_id' => "VDAmazepay97",
                'sku_code' => "VD-KFC",
                // Additional gift card specific fields
                'gift_send_option' => request('gift_send_option'),
                'delivery_mode' => request('delivery_mode'),
                'receiver_name' => request('receiver_name'),
                'receiver_email' => request('receiver_email'),
                'receiver_mobile' => request('receiver_mobile'),
                'receiver_msg' => request('receiver_msg'),
                'vd_discount' => request('vd_discount'),
                'vd_brand_code' => request('vd_brand_code'),
            ];

            $evcRequest = GetEvcRequest::create($giftCardData);
            return view('payment.success', [
                'orderId' => $evcRequest->order_id,
                'requestRefNo' => $evcRequest->req_id,
            ]);
        } catch (\Exception $e) {
            // Log error but don't stop the flow
            \Log::error('Error storing gift card data: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_of_card' => 'required|integer|min:1',
            'amount' => 'required|numeric',
            'firstname' => 'required|string',
            'lastname' => 'required|string',
            'email' => 'required|email',
            'mobile_no' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'pincode' => 'required|string',
            'curr' => 'required|string',
        ]);

        // Generate IDs automatically
        $validated['order_id'] = Str::upper(Str::random(20)); // Example: PQL98PQ9IUISPQQID74
        $validated['receipt_no'] = Str::upper(Str::random(12)); // Example: V9IQUZAOJLIY
        $validated['req_id'] = Str::upper(Str::random(16)); // Example: 1LLZAOJU92YTkk55
        $validated['distributor_id'] = "VDAmazepay97";
        $validated['sku_code'] = "VD-KFC";
        GetEvcRequest::create($validated);

        return redirect()->back()->with('success', 'Request saved successfully!');
    }

    public function show($orderId, $requestRefNo)
    {
        $evcRequest = GetEvcRequest::where('order_id', $orderId)
                        ->where('req_id', $requestRefNo)
                        ->first();

        if (!$evcRequest) {
            return view('get_evc_requests.show', [
                'evcRequest' => null,
                'orderId' => $orderId,
                'requestRefNo' => $requestRefNo,
            ]);
        }

        return view('get_evc_requests.show', compact('evcRequest'));
    }

}
