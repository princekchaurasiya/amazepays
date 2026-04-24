<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\TransactionPin;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * API v1 -- Transaction PIN management.
 *
 * Users must set a 4-digit PIN before performing sensitive wallet/order operations.
 */
class TransactionPinController extends Controller
{
    use ApiResponse;

    /** Set a new transaction PIN (first-time setup). */
    public function set(Request $request): ResponsePayload
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4',
            'pin_confirm' => 'required|same:pin',
        ]);

        $user = $request->user();

        if (TransactionPin::where('user_id', $user->id)->exists()) {
            return $this->error('PIN_ALREADY_SET', 'Transaction PIN is already set. Use the change endpoint.', 422);
        }

        TransactionPin::create([
            'user_id' => $user->id,
            'pin' => Hash::make($validated['pin']),
        ]);

        return $this->created('Transaction PIN set successfully.');
    }

    /** Change the transaction PIN (requires current PIN). */
    public function change(Request $request): ResponsePayload
    {
        $validated = $request->validate([
            'current_pin' => 'required|digits:4',
            'new_pin' => 'required|digits:4|different:current_pin',
            'pin_confirm' => 'required|same:new_pin',
        ]);

        $user = $request->user();
        $pin = TransactionPin::where('user_id', $user->id)->first();

        if (! $pin || ! Hash::check($validated['current_pin'], $pin->pin)) {
            throw ValidationException::withMessages(['current_pin' => 'Current PIN is incorrect.']);
        }

        $pin->update(['pin' => Hash::make($validated['new_pin'])]);

        return $this->ok('Transaction PIN changed successfully.');
    }

    /** Verify the transaction PIN (used by middleware for step-up auth). */
    public function verify(Request $request): ResponsePayload
    {
        $validated = $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $user = $request->user();
        $pin = TransactionPin::where('user_id', $user->id)->first();

        if (! $pin || ! Hash::check($validated['pin'], $pin->pin)) {
            return $this->error('INVALID_PIN', 'The transaction PIN is incorrect.', 401);
        }

        session(['transaction_pin_verified_at' => now()]);

        return $this->ok('PIN verified.');
    }
}
