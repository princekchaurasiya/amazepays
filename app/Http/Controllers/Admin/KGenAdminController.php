<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KGenOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KGenAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        $recentOrders = KGenOrder::query()->latest()->limit(10)->get();

        return Inertia::render('Admin/KGen/Index', [
            'configured' => $this->isConfigured(),
            'recentOrders' => $recentOrders
                ->map(fn (KGenOrder $o) => $o->only([
                    'id', 'external_ref', 'variant_id', 'status', 'payable_amount', 'created_at',
                ]))
                ->values()
                ->all(),
            'stats' => [
                'total_orders' => KGenOrder::query()->count(),
            ],
        ]);
    }

    private function isConfigured(): bool
    {
        return (bool) env('EXLR8_BASE_URL') && (bool) env('EXLR8_USER_ID') && (bool) env('dpID');
    }
}
