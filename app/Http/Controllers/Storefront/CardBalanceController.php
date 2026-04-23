<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Voucher\WoohooBalanceService;
use Illuminate\Http\Request;

class CardBalanceController extends Controller
{
    public function __construct(
        private WoohooBalanceService $balanceService,
    ) {}

    public function index(Request $request)
    {
        return inertia('Storefront/CardBalance', [
            'result' => session('card_balance_result'),
        ]);
    }

    public function check(Request $request)
    {
        $allowed = ['cardNumber', 'pin', 'sku'];
        $unknown = array_values(array_diff(array_keys($request->all()), $allowed));
        if ($unknown !== []) {
            return back()->withErrors([
                'unexpected_fields' => 'Unexpected input fields detected: '.implode(', ', $unknown),
            ])->withInput();
        }

        $validated = $request->validate([
            'cardNumber' => 'required|string|regex:/^[0-9A-Za-z]{8,50}$/',
            'pin' => 'nullable|string|max:25',
            'sku' => 'nullable|string|max:64',
        ]);

        $response = $this->balanceService->check(
            (string) $validated['cardNumber'],
            isset($validated['pin']) ? (string) $validated['pin'] : null,
            isset($validated['sku']) ? (string) $validated['sku'] : null,
        );

        return redirect()
            ->route('card.balance')
            ->with('card_balance_result', $response);
    }
}

