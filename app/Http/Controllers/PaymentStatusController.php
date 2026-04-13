<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentStatusController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $status = $request->query('status', 'failure');
        if (! in_array($status, ['success', 'failure'], true)) {
            $status = 'failure';
        }
        $msg = $status === 'success' ? 'Payment Successful' : 'Payment Failed';

        return Inertia::render('Checkout/Status', [
            'status' => $status,
            'msg' => $msg,
        ]);
    }
}
