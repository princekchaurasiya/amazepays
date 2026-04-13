<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\SecurityEventLog;
use App\Models\StorefrontBrand;
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
            'ziggy' => fn () => [
                'location' => $request->url(),
            ],
            'storefrontCategories' => fn () => Category::query()->orderBy('order')->get(),
            'storefrontBrandsNav' => fn () => StorefrontBrand::query()->orderBy('order')->limit(24)->get(),
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
        ]);
    }
}
