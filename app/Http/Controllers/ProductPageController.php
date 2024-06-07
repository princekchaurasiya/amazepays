<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QsOrder;
use Auth;
use Validator;
use Illuminate\Support\Facades\Log;
use App\Models\QsProduct;

class ProductPageController extends Controller
{
    public function storePayNowData(Request $request, $slug)
    {
        $rules = [
            'quantity' => 'required|integer|min:1|max:10',
            'gift_send_option' => 'required|string',
            'delivery_mode' => 'required|string',
        ];

        // Custom error messages
        $messages = [
            'quantity.min' => 'The quantity must be at least :min.',
            'quantity.max' => 'The quantity cannot exceed :max.',
        ];


        Log::info('you are on gift page');

        Log::info($request);


        // Validate the request
        $validator = Validator::make($request->all(), $rules, $messages);

        // Check if validation fails
        if ($validator->fails()) {
            return redirect()
                ->back()
                ->withErrors($validator)
                ->withInput();
        }


        $qsOrder = new QsOrder();
        $qsOrder->user_id = Auth::user()->id;
        $qsOrder->denomination = $request->denomination;
        $qsOrder->quantity = $request->quantity;
        $qsOrder->grand_payable_amount = $request->quantity*$request->denomination;
        $qsOrder->gift_send_option = $request->gift_send_option;
        $qsOrder->delivery_mode = $request->delivery_mode;
        $qsOrder->receiver_name = $request->receiver_name;
        $qsOrder->receiver_email = $request->receiver_email;
        $qsOrder->receiver_mobile = $request->receiver_mobile;
        $qsOrder->receiver_msg = $request->receiver_msg;
        $qsOrder->save();

        // Store the qs_order ID in the session
        session()->put('session_qs_order_id', $qsOrder->id);

        $qsProd = QsProduct::where('slug', $slug)->first();
        $qsProd['prodData'] = $request->all();
        $currency = json_decode($qsProd['currency']);
        $qsProd['currency'] = $currency;
        $qsProd['images'] = json_decode($qsProd->images);
        if (\Auth::check()) {
            return view('userpanel.checkout', compact('qsProd'));
        } else {
            return redirect('/');
        }
    }
}
