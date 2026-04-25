<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Content\Models\ContentSection;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HomepageBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('settings.view');

        $tenantId = $this->resolveTenantId($request);

        $sections = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ContentSection $s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'type' => $s->type,
                'title' => $s->title,
                'is_enabled' => (bool) $s->is_enabled,
                'sort_order' => (int) $s->sort_order,
                'platform' => $s->platform,
                'start_at' => optional($s->start_at)->toISOString(),
                'end_at' => optional($s->end_at)->toISOString(),
                'priority' => (int) $s->priority,
                'metadata' => $s->metadata ?? [],
            ])
            ->values()
            ->all();

        $types = [
            'banner_single',
            'banner_dual',
            'banner_square',
            'carousel',
            'voucher_slider',
            'grid_2',
            'grid_3',
            'featured_products',
            'custom',
        ];

        return Inertia::render('Admin/HomepageBuilder/Index', [
            'sections' => $sections,
            'sectionTypes' => array_map(fn (string $t) => ['value' => $t, 'label' => str_replace('_', ' ', ucwords($t, '_'))], $types),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9_-]+$/', Rule::unique('content_sections', 'slug')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('surface', 'storefront_home'))],
            'type' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'platform' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'metadata' => ['nullable', 'array'],
        ]);

        $maxSort = (int) ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->max('sort_order');

        ContentSection::create([
            'tenant_id' => $tenantId,
            'surface' => 'storefront_home',
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'status' => 'active',
            'is_enabled' => $validated['is_enabled'] ?? true,
            'sort_order' => $validated['sort_order'] ?? ($maxSort + 1),
            'platform' => $validated['platform'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'title' => $validated['title'] ?? null,
            'subtitle' => null,
            'background_color' => null,
            'text_color' => null,
            'metadata' => $validated['metadata'] ?? null,
            'created_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Section created.');
    }

    public function update(Request $request, ContentSection $section): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:64'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_enabled' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'platform' => ['required', Rule::in(['web', 'mobile', 'both'])],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after:start_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'metadata' => ['nullable', 'array'],
        ]);

        $section->update([
            'type' => $validated['type'],
            'title' => $validated['title'] ?? null,
            'is_enabled' => $validated['is_enabled'] ?? $section->is_enabled,
            'sort_order' => $validated['sort_order'] ?? $section->sort_order,
            'platform' => $validated['platform'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return back()->with('success', 'Section updated.');
    }

    public function destroy(Request $request, ContentSection $section): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('content_sections', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('surface', 'storefront_home'))],
        ]);

        foreach ($validated['ids'] as $index => $id) {
            ContentSection::where('tenant_id', $tenantId)->where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return back()->with('success', 'Order saved.');
    }

    public function publish(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);

        $ids = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->orderByDesc('priority')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $last = (int) DB::table('homepage_layout_versions')->where('tenant_id', $tenantId)->max('version');
        $version = $last > 0 ? $last + 1 : 1;

        DB::table('homepage_layout_versions')->insert([
            'tenant_id' => $tenantId,
            'version' => $version,
            'published_at' => now(),
            'layout_snapshot' => json_encode(['surface' => 'storefront_home', 'section_ids' => $ids], JSON_THROW_ON_ERROR),
            'experiment_key' => null,
            'created_by' => $request->user()?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', "Homepage layout published (v{$version}).");
    }

    private function resolveTenantId(Request $request): int
    {
        $tenant = $request->attributes->get('tenant');
        if ($tenant && method_exists($tenant, 'getKey')) {
            return (int) $tenant->getKey();
        }

        $id = app()->bound('current_tenant_id') ? app('current_tenant_id') : null;
        if (is_int($id) && $id > 0) {
            return $id;
        }

        if (! Schema::hasTable('tenants')) {
            return 1;
        }

        return (int) (DB::table('tenants')->orderBy('id')->value('id') ?? 1);
    }
}

