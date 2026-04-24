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

        $vouchagramConfigured = (bool) config('vouchagram.send.username') || (bool) config('vouchagram.pull.username');
        $gyftrEnvConfigured = (bool) env('GYFTR_CLIENT_ID');

        return Inertia::render('Admin/Providers/Index', [
            'providers' => [
                [
                    'key' => 'woohoo',
                    'label' => 'Woohoo',
                    'healthy' => (bool) config('woohoo.host') && (bool) config('woohoo.bearer_token'),
                    'href' => route('panel.woohoo.index'),
                    'note' => null,
                ],
                [
                    'key' => 'vouchagram_gyftr',
                    'label' => 'Vouchagram / Gyftr',
                    'healthy' => $vouchagramConfigured || $gyftrEnvConfigured,
                    'href' => route('panel.vouchagram.index'),
                    'note' => 'Same voucher network: Vouchagram is the parent platform (Send/Pull APIs, catalog sync, partner tools); Gyftr is a brand on that network. One dashboard covers both.',
                ],
                [
                    'key' => 'kgen',
                    'label' => 'KGen / EXLR8',
                    'healthy' => (bool) env('EXLR8_BASE_URL') && (bool) env('EXLR8_USER_ID'),
                    'href' => route('panel.kgen.index'),
                    'note' => null,
                ],
                [
                    'key' => 'vd',
                    'label' => 'Value Design',
                    'healthy' => (bool) config('valuedesign.base_url')
                        && (bool) config('valuedesign.username')
                        && (bool) config('valuedesign.password'),
                    'href' => route('panel.value-design.index'),
                    'note' => null,
                ],
                [
                    'key' => 'lysto',
                    'label' => 'Lysto / Athena',
                    'healthy' => (bool) config('lysto.base_url') && (bool) config('lysto.api_key'),
                    'href' => route('panel.lysto.gift-cards.index'),
                    'note' => null,
                ],
            ],
            'order_stats' => [
                'storefront_orders' => Order::query()->count(),
                'kgen_orders' => KGenOrder::query()->count(),
            ],
        ]);
    }
}
