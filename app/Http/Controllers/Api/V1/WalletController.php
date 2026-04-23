<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\SubmitWalletLoadRequest;
use App\Http\Traits\ApiResponse;
use App\Models\WalletLoadRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletLoadRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 -- Wallet endpoints.
 */
class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(private WalletLoadRequestService $loadRequestService) {}

    /** Get the user's current wallet balance. */
    public function balance(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        return $this->ok('Wallet balance retrieved.', [
            'balance' => $wallet?->balance ?? 0,
            'currency' => 'INR',
            'is_frozen' => $wallet?->is_frozen ?? false,
        ]);
    }

    /** List wallet transactions with pagination. */
    public function transactions(Request $request): JsonResponse
    {
        $wallet = $request->user()->wallet;

        if (! $wallet) {
            return $this->ok('No transactions found.', ['transactions' => []]);
        }

        $transactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->paginate(20);

        return $this->paginated($transactions);
    }

    /** Submit a wallet load request (bank transfer proof). */
    public function requestLoad(SubmitWalletLoadRequest $request): JsonResponse
    {
        $loadRequest = $this->loadRequestService->submitForUser($request->user(), $request);

        return $this->created('Load request submitted. It will be reviewed within 2-4 business hours.', [
            'request_id' => $loadRequest->id,
            'amount' => $loadRequest->amount,
            'status' => $loadRequest->status,
        ]);
    }

    /** Check the status of a wallet load request. */
    public function loadRequestStatus(Request $request, WalletLoadRequest $loadRequest): JsonResponse
    {
        if ($loadRequest->user_id !== $request->user()->id) {
            return $this->notFound();
        }

        return $this->ok('Load request status.', [
            'id' => $loadRequest->id,
            'amount' => $loadRequest->amount,
            'payment_mode' => $loadRequest->payment_mode,
            'reference_no' => $loadRequest->reference_no,
            'status' => $loadRequest->status,
            'created_at' => $loadRequest->created_at,
            'updated_at' => $loadRequest->updated_at,
        ]);
    }
}
