<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Content\Models\ContentSection;
use App\Domains\Content\Models\ContentSectionItem;
use App\Http\Controllers\Controller;
use App\Enums\ResponseCode;
use App\Support\Http\ResponsePayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

final class HomepageBuilderItemsController extends Controller
{
    public function index(Request $request, ContentSection $section): ResponsePayload
    {
        $this->authorize('settings.view');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $items = ContentSectionItem::query()
            ->where('tenant_id', $tenantId)
            ->where('content_section_id', $section->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (ContentSectionItem $i) => [
                'id' => $i->id,
                'sort_order' => (int) $i->sort_order,
                'is_enabled' => (bool) $i->is_enabled,
                'title' => $i->title,
                'subtitle' => $i->subtitle,
                'cta_type' => $i->cta_type,
                'cta_text' => $i->cta_text,
                'redirect_url' => $i->redirect_url,
                'product_id' => $i->product_id,
                'category_id' => $i->category_id,
                'brand_id' => $i->brand_id,
                'metadata' => $i->metadata ?? [],
            ])
            ->values()
            ->all();

        return ResponsePayload::ok(null, [
            'section_id' => $section->id,
            'items' => $items,
        ]);
    }

    public function store(Request $request, ContentSection $section): ResponsePayload
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $validated = $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_enabled' => ['sometimes', 'boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'cta_type' => ['nullable', 'string', 'max:32'],
            'redirect_url' => ['nullable', 'string', 'max:2048'],
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'metadata' => ['nullable', 'array'],
        ]);

        $linkCount = (int) (! empty($validated['product_id'])) + (int) (! empty($validated['category_id'])) + (int) (! empty($validated['brand_id']));
        if ($linkCount !== 1) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, null, [
                'message' => 'Exactly one of product_id, category_id, brand_id is required.',
            ], 422);
        }

        $nextSort = (int) ContentSectionItem::query()
            ->where('tenant_id', $tenantId)
            ->where('content_section_id', $section->id)
            ->max('sort_order');

        $item = ContentSectionItem::create([
            'tenant_id' => $tenantId,
            'content_section_id' => $section->id,
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : ($nextSort + 1),
            'is_enabled' => $validated['is_enabled'] ?? true,
            'start_at' => null,
            'end_at' => null,
            'priority' => 0,
            'title' => $validated['title'] ?? null,
            'subtitle' => $validated['subtitle'] ?? null,
            'web_media_asset_id' => null,
            'mobile_media_asset_id' => null,
            'cta_text' => $validated['cta_text'] ?? null,
            'cta_type' => $validated['cta_type'] ?? null,
            'cta_value' => null,
            'deeplink' => null,
            'redirect_url' => $validated['redirect_url'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return ResponsePayload::created(null, [
            'item' => [
                'id' => $item->id,
                'sort_order' => (int) $item->sort_order,
                'is_enabled' => (bool) $item->is_enabled,
                'title' => $item->title,
                'subtitle' => $item->subtitle,
                'cta_type' => $item->cta_type,
                'cta_text' => $item->cta_text,
                'redirect_url' => $item->redirect_url,
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'brand_id' => $item->brand_id,
                'metadata' => $item->metadata ?? [],
            ],
        ]);
    }

    public function bulkStore(Request $request, ContentSection $section): ResponsePayload
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $validated = $request->validate([
            'kind' => ['required', 'string', Rule::in(['product', 'brand', 'category'])],
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['integer', 'min:1'],
        ]);

        $kind = (string) $validated['kind'];
        /** @var list<int> $ids */
        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $ids = array_values(array_filter($ids, fn ($v) => $v > 0));
        if ($ids === []) {
            return ResponsePayload::fail(ResponseCode::VALIDATION_FAILED, null, ['message' => 'ids must be non-empty'], 422);
        }

        $fkColumn = match ($kind) {
            'product' => 'product_id',
            'brand' => 'brand_id',
            'category' => 'category_id',
        };
        $table = match ($kind) {
            'product' => 'products',
            'brand' => 'brands',
            'category' => 'categories',
        };

        // Filter to existing ids (defensive).
        $existing = DB::table($table)->whereIn('id', $ids)->pluck('id')->map(fn ($v) => (int) $v)->values()->all();
        if ($existing === []) {
            return ResponsePayload::ok(null, ['inserted' => 0, 'skipped_existing' => 0]);
        }

        $existingItemIds = ContentSectionItem::query()
            ->where('tenant_id', $tenantId)
            ->where('content_section_id', $section->id)
            ->whereIn($fkColumn, $existing)
            ->pluck($fkColumn)
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();
        $existingSet = array_fill_keys($existingItemIds, true);

        $toInsert = array_values(array_filter($existing, fn ($id) => ! isset($existingSet[$id])));

        if ($toInsert === []) {
            return ResponsePayload::ok(null, ['inserted' => 0, 'skipped_existing' => count($existingItemIds)]);
        }

        $now = now();
        $baseSort = (int) ContentSectionItem::query()
            ->where('tenant_id', $tenantId)
            ->where('content_section_id', $section->id)
            ->max('sort_order');

        $rows = [];
        $sort = $baseSort;
        foreach ($toInsert as $id) {
            $sort++;
            $rows[] = [
                'tenant_id' => $tenantId,
                'content_section_id' => $section->id,
                'sort_order' => $sort,
                'is_enabled' => true,
                'start_at' => null,
                'end_at' => null,
                'priority' => 0,
                'title' => null,
                'subtitle' => null,
                'web_media_asset_id' => null,
                'mobile_media_asset_id' => null,
                'cta_text' => null,
                'cta_type' => $kind,
                'cta_value' => null,
                'deeplink' => null,
                'redirect_url' => null,
                'product_id' => null,
                'category_id' => null,
                'brand_id' => null,
                $fkColumn => (int) $id,
                'metadata' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows) {
            ContentSectionItem::query()->insert($rows);
        });

        return ResponsePayload::ok(null, [
            'inserted' => count($rows),
            'skipped_existing' => count($existingItemIds),
        ]);
    }

    public function update(Request $request, ContentSectionItem $item): ResponsePayload
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($item->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'is_enabled' => ['sometimes', 'boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:255'],
            'cta_type' => ['nullable', 'string', 'max:32'],
            'redirect_url' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
        ]);

        $item->update([
            ...(array_key_exists('sort_order', $validated) ? ['sort_order' => (int) $validated['sort_order']] : []),
            ...(array_key_exists('is_enabled', $validated) ? ['is_enabled' => (bool) $validated['is_enabled']] : []),
            'title' => $validated['title'] ?? $item->title,
            'subtitle' => $validated['subtitle'] ?? $item->subtitle,
            'cta_text' => $validated['cta_text'] ?? $item->cta_text,
            'cta_type' => $validated['cta_type'] ?? $item->cta_type,
            'redirect_url' => $validated['redirect_url'] ?? $item->redirect_url,
            ...(array_key_exists('metadata', $validated) ? ['metadata' => $validated['metadata']] : []),
        ]);

        return ResponsePayload::ok(null, ['updated' => true]);
    }

    public function destroy(Request $request, ContentSectionItem $item): ResponsePayload
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($item->tenant_id !== $tenantId, 404);

        $item->delete();

        return ResponsePayload::ok(null, ['deleted' => true]);
    }

    public function reorder(Request $request, ContentSection $section): ResponsePayload
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        abort_if($section->tenant_id !== $tenantId || $section->surface !== 'storefront_home', 404);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => [
                'integer',
                Rule::exists('content_section_items', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->where('content_section_id', $section->id)),
            ],
        ]);

        DB::transaction(function () use ($validated, $tenantId, $section) {
            foreach ($validated['ids'] as $idx => $id) {
                ContentSectionItem::query()
                    ->where('tenant_id', $tenantId)
                    ->where('content_section_id', $section->id)
                    ->where('id', (int) $id)
                    ->update(['sort_order' => $idx + 1]);
            }
        });

        return ResponsePayload::ok(null, ['reordered' => true]);
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

