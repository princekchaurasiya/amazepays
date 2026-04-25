<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Category;
use App\Models\SecurityEventLog;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'app' => [
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'mobile' => $request->user()->mobile ?? null,
                    'two_factor_enabled' => $request->user()->two_factor_enabled ?? false,
                    'roles' => $request->user()->getRoleNames()->values()->all(),
                    'permissions' => $request->user()->getAllPermissions()->pluck('name')->values()->all(),
                ] : null,
            ],
            'security' => $request->user() && $request->user()->hasAnyRole(['super-admin', 'admin'])
                ? [
                    'active_threats' => SecurityEventLog::unresolved()
                        ->highSeverity()
                        ->where('created_at', '>=', now()->subHour())
                        ->count(),
                ]
                : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
            'cart' => fn () => $this->shareCartSummary($request),
            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
            'storefrontCategories' => fn () => Category::query()->orderBy('display_order')->get(),
            'storefrontBrandsNav' => fn () => Brand::query()->orderBy('display_order')->limit(24)->get(),
            'company' => fn () => [
                'official_name' => config('companyDefaultValues.company_official_name'),
                'address' => config('companyDefaultValues.company_address'),
                'email' => config('companyDefaultValues.company_email'),
                'cin' => config('companyDefaultValues.company_cin'),
                'contact_no' => config('companyDefaultValues.company_contact_no'),
                'website' => config('companyDefaultValues.company_website'),
                'about_link' => config('companyDefaultValues.company_new_website_link'),
            ],
            'social' => fn () => [
                'facebook' => env('FACEBOOK_URL', '#'),
                'linkedin' => env('LINKEDIN_URL', '#'),
                'instagram' => env('INSTAGRAM_URL', '#'),
            ],
            'i18n' => fn () => [
                'storefront' => [
                    'product' => __('storefront.product'),
                    'order_detail' => __('storefront.order_detail'),
                ],
                'checkout' => __('checkout'),
                'auth_ui' => __('auth_ui'),
                'admin' => __('admin'),
            ],
        ]);
    }

    /**
     * @return array{count:int,quantity:int,total:float}
     */
    private function shareCartSummary(Request $request): array
    {
        $cart = $request->user()?->cart()->with('items')->first();
        $items = $cart?->items ?? collect();

        $count = $items->count();
        $quantity = 0;
        $total = 0.0;

        foreach ($items as $item) {
            $q = (int) ($item->quantity ?? 0);
            $line = (float) ($item->line_total ?? 0.0);
            if ($q > 0) {
                $quantity += $q;
            }
            if ($line > 0) {
                $total += $line;
            }
        }

        return [
            'count' => $count,
            'quantity' => $quantity,
            'total' => $total,
        ];
    }
}
