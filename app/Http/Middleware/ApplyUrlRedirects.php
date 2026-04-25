<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\UrlRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyUrlRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle browser-ish GET/HEAD traffic. Do not interfere with API/webhooks.
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        if ($request->is('api/*')) {
            return $next($request);
        }

        if ($request->is('up') || $request->is('up/*')) {
            return $next($request);
        }

        $tenantId = $this->resolveTenantId($request);
        if (! $tenantId) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');

        $redirect = UrlRedirect::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('source_path', $path)
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        $now = now();
        if (($redirect->active_from && $redirect->active_from->isFuture()) || ($redirect->active_until && $redirect->active_until->isPast())) {
            abort(410);
        }

        $redirect->forceFill([
            'hit_count' => (int) ($redirect->hit_count ?? 0) + 1,
            'last_hit_at' => $now,
        ])->save();

        $status = (int) ($redirect->http_status_code ?? 301);
        if (! in_array($status, [301, 302, 307, 308], true)) {
            $status = 301;
        }

        return redirect()->to($redirect->destination_url, $status);
    }

    private function resolveTenantId(Request $request): ?int
    {
        // Prefer already-resolved tenant middleware state.
        $tenant = $request->attributes->get('tenant');
        if ($tenant instanceof Tenant) {
            return (int) $tenant->id;
        }

        // Fallback: header-based tenant resolution.
        $slug = (string) $request->header('X-Tenant-Slug', '');
        if ($slug !== '') {
            return Tenant::query()->where('slug', $slug)->value('id');
        }

        return null;
    }
}

