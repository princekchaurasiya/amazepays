<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PaymentSessionController extends Controller
{
    public function show(Request $request, Payment $payment): Response
    {
        $this->authorize('dashboard.view');

        $payment->loadMissing(['order', 'user']);

        return Inertia::render('Admin/Payments/Sessions/Show', [
            'payment' => $payment,
        ]);
    }
}

