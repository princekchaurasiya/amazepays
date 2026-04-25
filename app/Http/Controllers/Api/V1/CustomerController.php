<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    use ApiResponse;

    public function show(Request $request, User $customer): ResponsePayload
    {
        // For now, only allow self-lookups. This matches the current mobile usage
        // pattern and avoids exposing PII by ID enumeration.
        if ((int) $customer->id !== (int) $request->user()->id) {
            return $this->notFound();
        }

        return $this->ok('customers.retrieved', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
            ],
        ]);
    }
}
