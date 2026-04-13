<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KGenOrder;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('providers.view');

        return Inertia::render('Admin/Providers/Index', [
            'providers' => [
                [
                    'key' => 'woohoo',
                    'label' => 'Woohoo',
                    'healthy' => (bool) config('woohoo.host') && (bool) config('woohoo.bearer_token'),
                    'href' => route('admin.woohoo.index'),
                ],
                [
                    'key' => 'vouchagram',
                    'label' => 'Vouchagram',
                    'healthy' => (bool) config('vouchagram.send.username') || (bool) config('vouchagram.pull.username'),
                    'href' => route('admin.vouchagram.index'),
                ],
                [
                    'key' => 'kgen',
                    'label' => 'KGen / EXLR8',
                    'healthy' => (bool) env('EXLR8_BASE_URL') && (bool) env('EXLR8_USER_ID'),
                    'href' => route('admin.kgen.index'),
                ],
                [
                    'key' => 'vd',
                    'label' => 'Value Design',
                    'healthy' => (bool) env('VD_API_URL') || (bool) env('VALUE_DESIGN_API_URL'),
                    'href' => route('admin.value-design.index'),
                ],
                [
                    'key' => 'ezpin',
                    'label' => 'EZPin',
                    'healthy' => (bool) env('EZPIN_API_KEY'),
                    'href' => null,
                ],
                [
                    'key' => 'gyftr',
                    'label' => 'Gyftr',
                    'healthy' => (bool) env('GYFTR_CLIENT_ID'),
                    'href' => null,
                ],
                [
                    'key' => 'lysto',
                    'label' => 'Lysto / Athena',
                    'healthy' => (bool) env('ATHENA_BASE_URL') || (bool) env('LYSTO_API_KEY'),
                    'href' => url('/admin/lysto/dashboard'),
                ],
            ],
            'order_stats' => [
                'storefront_orders' => Order::query()->count(),
                'kgen_orders' => KGenOrder::query()->count(),
            ],
        ]);
    }
}
