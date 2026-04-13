<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function saveGiftCardForm(Request $request)
    {
        try {
            if (! $request->has('delivery_mode')) {
                $request->merge(['delivery_mode' => 'both']);
            }
            $validated = $request->validate([
                'gift_send_option' => 'required|string|in:send_as_gift,buy_for_self',
                'receiver_name' => 'nullable|string|max:255',
                'receiver_email' => 'nullable|email|max:255',
                'receiver_mobile' => 'nullable|string|max:20',
                'receiver_msg' => 'nullable|string',
                'delivery_mode' => 'required_if:gift_send_option,send_as_gift|string|in:both,email,sms',
            ], [
                'gift_send_option.required' => 'Gift send option is required.',
                'gift_send_option.string' => 'Gift send option must be a string.',
                'gift_send_option.in' => 'Gift send option must be send_as_gift or buy_for_self.',
                'receiver_name.string' => 'Receiver name must be a string.',
                'receiver_name.max' => 'Receiver name cannot exceed 255 characters.',
                'receiver_email.email' => 'Receiver email must be a valid email address.',
                'receiver_email.max' => 'Receiver email cannot exceed 255 characters.',
                'receiver_mobile.string' => 'Receiver mobile must be a string.',
                'receiver_mobile.max' => 'Receiver mobile cannot exceed 20 characters.',
                'receiver_msg.string' => 'Receiver message must be a string.',
                'delivery_mode.required_if' => 'Delivery mode is required when sending as a gift.',
                'delivery_mode.string' => 'Delivery mode must be a string.',
                'delivery_mode.in' => 'Delivery mode must be one of: both, email, or sms.',
            ]);

            session([
                'gift_card_form' => $validated,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Gift card form data saved successfully',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save gift card form data: '.$e->getMessage(),
            ], 500);
        }
    }
}
