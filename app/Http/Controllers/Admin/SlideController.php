<?php

namespace App\Http\Controllers\Admin;

use App\Domains\Content\Models\ContentSection;
use App\Domains\Content\Models\ContentSectionItem;
use App\Domains\Media\Models\MediaAsset;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SlideController extends Controller
{
    private const HERO_SECTION_SLUG = 'hero-banners';

    private const HERO_SECTION_TYPE = 'carousel';

    public function index(): Response
    {
        $this->authorize('settings.view');

        $tenantId = $this->resolveTenantId(request());
        $heroSectionId = $this->ensureHeroSection($tenantId);

        $slides = ContentSectionItem::query()
            ->where('tenant_id', $tenantId)
            ->where('content_section_id', $heroSectionId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(24)
            ->through(fn (ContentSectionItem $i) => [
                'id' => $i->id,
                'priority' => $i->priority,
                'status' => $i->is_enabled ? 1 : 0,
                'display_on_page' => 'homepage',
                'img_alt_tag' => data_get($i->metadata, 'alt', null),
                'desktop_image' => $this->absoluteStorageUrl($i->webMedia?->path),
                'image_mobile' => $this->absoluteStorageUrl($i->mobileMedia?->path),
                'product_id' => $i->product_id,
                'category_id' => $i->category_id,
                'brand_id' => $i->brand_id,
            ]);

        return Inertia::render('Admin/Slides/Index', [
            'slides' => $slides,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId(request());
        $this->ensureHeroSection($tenantId);

        return Inertia::render('Admin/Slides/Form', [
            'slide' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        $heroSectionId = $this->ensureHeroSection($tenantId);

        $desk = $this->storeUploaded($request->file('desktop_image_file'), 'desktop');
        $mob = $this->storeUploaded($request->file('image_mobile_file'), 'mobile');

        $webMediaId = $desk ? $this->createMediaAsset($tenantId, $desk, $request) : null;
        $mobileMediaId = $mob ? $this->createMediaAsset($tenantId, $mob, $request) : null;

        $payload = $this->validated($request, true);

        ContentSectionItem::create([
            'tenant_id' => $tenantId,
            'content_section_id' => $heroSectionId,
            'sort_order' => (int) ($payload['priority'] ?? 0),
            'is_enabled' => ((int) ($payload['status'] ?? 1)) === 1,
            'priority' => 0,
            'title' => $payload['big_header'] ?? null,
            'subtitle' => $payload['small_header'] ?? null,
            'web_media_asset_id' => $webMediaId,
            'mobile_media_asset_id' => $mobileMediaId,
            'cta_type' => filled($payload['cta_link'] ?? null) ? 'url' : null,
            'cta_value' => $payload['cta_link'] ?? null,
            'redirect_url' => $payload['cta_link'] ?? null,
            'product_id' => $payload['product_id'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'brand_id' => $payload['brand_id'] ?? null,
            'metadata' => array_filter([
                'alt' => $payload['img_alt_tag'] ?? null,
                'custom_url' => $payload['custom_url'] ?? null,
                'link_type' => $payload['link_type'] ?? null,
            ]),
        ]);

        return redirect()->route('panel.settings.hero-slides.index')->with('success', 'Slide created.');
    }

    public function edit(ContentSectionItem $slide): Response
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId(request());
        $heroSectionId = $this->ensureHeroSection($tenantId);
        abort_if($slide->tenant_id !== $tenantId || $slide->content_section_id !== $heroSectionId, 404);

        return Inertia::render('Admin/Slides/Form', [
            'slide' => [
                'id' => $slide->id,
                'priority' => $slide->sort_order,
                'status' => $slide->is_enabled ? 1 : 0,
                'display_on_page' => 'homepage',
                'img_alt_tag' => data_get($slide->metadata, 'alt', ''),
                'small_header' => $slide->subtitle,
                'big_header' => $slide->title,
                'cta_link' => $slide->redirect_url,
                'slider_location' => null,
                'product_id' => $slide->product_id,
                'category_id' => $slide->category_id,
                'brand_id' => $slide->brand_id,
                'custom_url' => data_get($slide->metadata, 'custom_url', ''),
                'link_type' => data_get($slide->metadata, 'link_type', ''),
                'desktop_image_url' => $this->absoluteStorageUrl($slide->webMedia?->path),
                'image_mobile_url' => $this->absoluteStorageUrl($slide->mobileMedia?->path),
            ],
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, ContentSectionItem $slide): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId($request);
        $heroSectionId = $this->ensureHeroSection($tenantId);
        abort_if($slide->tenant_id !== $tenantId || $slide->content_section_id !== $heroSectionId, 404);

        $payload = $this->validated($request, false);

        if ($request->hasFile('desktop_image_file')) {
            $path = $this->storeUploaded($request->file('desktop_image_file'), 'desktop');
            if ($path) {
                $this->replaceMedia($tenantId, $slide, $slide->web_media_asset_id, $path, $request, 'web_media_asset_id');
            }
        }

        if ($request->hasFile('image_mobile_file')) {
            $path = $this->storeUploaded($request->file('image_mobile_file'), 'mobile');
            if ($path) {
                $this->replaceMedia($tenantId, $slide, $slide->mobile_media_asset_id, $path, $request, 'mobile_media_asset_id');
            }
        }

        $slide->update([
            'sort_order' => (int) ($payload['priority'] ?? $slide->sort_order),
            'is_enabled' => ((int) ($payload['status'] ?? ($slide->is_enabled ? 1 : 0))) === 1,
            'title' => $payload['big_header'] ?? null,
            'subtitle' => $payload['small_header'] ?? null,
            'redirect_url' => $payload['cta_link'] ?? null,
            'cta_type' => filled($payload['cta_link'] ?? null) ? 'url' : null,
            'cta_value' => $payload['cta_link'] ?? null,
            'product_id' => $payload['product_id'] ?? null,
            'category_id' => $payload['category_id'] ?? null,
            'brand_id' => $payload['brand_id'] ?? null,
            'metadata' => array_filter([
                'alt' => $payload['img_alt_tag'] ?? null,
                'custom_url' => $payload['custom_url'] ?? null,
                'link_type' => $payload['link_type'] ?? null,
            ]),
        ]);

        return back()->with('success', 'Slide updated.');
    }

    public function destroy(ContentSectionItem $slide): RedirectResponse
    {
        $this->authorize('settings.update');

        $tenantId = $this->resolveTenantId(request());
        $heroSectionId = $this->ensureHeroSection($tenantId);
        abort_if($slide->tenant_id !== $tenantId || $slide->content_section_id !== $heroSectionId, 404);

        $this->deleteMedia($slide->web_media_asset_id);
        $this->deleteMedia($slide->mobile_media_asset_id);
        $slide->delete();

        return redirect()->route('panel.settings.hero-slides.index')->with('success', 'Slide removed.');
    }

    /**
     * @return array{products: Collection, categories: Collection, brands: Collection}
     */
    private function formOptions(): array
    {
        return [
            'products' => Product::visible()->select('id', 'name', 'sku')->orderBy('name')->limit(500)->get(),
            'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::query()->select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $isCreate): array
    {
        $request->validate([
            'desktop_image_file' => 'nullable|image|max:6144',
            'image_mobile_file' => 'nullable|image|max:6144',
            'small_header' => 'nullable|string|max:255',
            'big_header' => 'nullable|string|max:255',
            'cta_value' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:2048',
            'priority' => 'nullable|integer|min:0|max:999999',
            'slider_location' => 'nullable|string|max:64',
            'status' => 'required|integer|in:0,1',
            'img_alt_tag' => 'nullable|string|max:255',
            'display_on_page' => 'nullable|string|max:64',
            'product_id' => 'nullable|integer|exists:products,id',
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'custom_url' => 'nullable|string|max:2048',
            'link_type' => 'nullable|string|max:64',
        ]);

        $data = $request->only([
            'small_header',
            'big_header',
            'cta_value',
            'cta_link',
            'priority',
            'slider_location',
            'status',
            'img_alt_tag',
            'display_on_page',
            'product_id',
            'category_id',
            'brand_id',
            'custom_url',
            'link_type',
        ]);

        if ($isCreate && empty($data['display_on_page'])) {
            $data['display_on_page'] = 'homepage';
        }

        return $data;
    }

    private function storeUploaded(?UploadedFile $file, string $prefix): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs('slides', $prefix.'-'.Str::uuid()->toString().'.'.$ext, 'public');

        return $path ?: null;
    }

    private function absoluteStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return url(Storage::url($path));
    }

    private function createMediaAsset(int $tenantId, string $path, Request $request): int
    {
        $row = MediaAsset::create([
            'tenant_id' => $tenantId,
            'path' => $path,
            'disk' => 'public',
            'created_by' => $request->user()?->id,
        ]);

        return (int) $row->id;
    }

    private function replaceMedia(int $tenantId, ContentSectionItem $item, ?int $existingId, string $newPath, Request $request, string $targetColumn): void
    {
        if ($existingId) {
            $existing = MediaAsset::query()->where('tenant_id', $tenantId)->where('id', $existingId)->first();
            if ($existing) {
                if ($existing->path && Storage::disk('public')->exists($existing->path)) {
                    Storage::disk('public')->delete($existing->path);
                }
                $existing->update(['path' => $newPath]);

                return;
            }
        }

        $newId = $this->createMediaAsset($tenantId, $newPath, $request);
        $item->update([$targetColumn => $newId]);
    }

    private function deleteMedia(?int $mediaId): void
    {
        if (! $mediaId) {
            return;
        }

        $asset = MediaAsset::find($mediaId);
        if (! $asset) {
            return;
        }

        if ($asset->path && Storage::disk('public')->exists($asset->path)) {
            Storage::disk('public')->delete($asset->path);
        }

        $asset->delete();
    }

    private function ensureHeroSection(int $tenantId): int
    {
        $existing = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->where('slug', self::HERO_SECTION_SLUG)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $row = ContentSection::create([
            'tenant_id' => $tenantId,
            'surface' => 'storefront_home',
            'slug' => self::HERO_SECTION_SLUG,
            'type' => self::HERO_SECTION_TYPE,
            'status' => 'active',
            'is_enabled' => true,
            'sort_order' => 0,
            'platform' => 'both',
            'title' => null,
            'subtitle' => null,
            'metadata' => null,
            'created_by' => request()->user()?->id,
        ]);

        return (int) $row->id;
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

        return 1;
    }
}
