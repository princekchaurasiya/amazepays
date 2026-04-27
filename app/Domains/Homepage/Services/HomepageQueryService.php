<?php

namespace App\Domains\Homepage\Services;

use App\Domains\Content\Models\ContentSection;
use App\Domains\Homepage\Cache\HomepageCacheInvalidator;
use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomepageQueryService
{
    public function __construct(
        private HomepageCacheInvalidator $cacheInvalidator,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function homepageDocument(int $tenantId, string $platform, string $surface = 'storefront_home'): array
    {
        $platform = $platform === 'web' ? 'web' : ($platform === 'mobile' ? 'mobile' : 'mobile');

        $cacheKey = "homepage:document:v1:tenant:{$tenantId}:surface:{$surface}:platform:{$platform}";

        $cacheTtl = now()->addMinutes(5);
        $compute = function () use ($tenantId, $platform, $surface) {
            $now = now();

            $latest = null;
            $publishedSectionIds = [];
            if (Schema::hasTable('homepage_layout_versions')) {
                $latest = DB::table('homepage_layout_versions')
                    ->where('tenant_id', $tenantId)
                    ->orderByDesc('version')
                    ->first(['version', 'published_at', 'layout_snapshot']);

                if ($latest && isset($latest->layout_snapshot)) {
                    $snap = json_decode((string) $latest->layout_snapshot, true);
                    $ids = is_array($snap) ? ($snap['section_ids'] ?? []) : [];
                    if (is_array($ids)) {
                        $publishedSectionIds = array_values(array_filter(array_map('intval', $ids), fn ($v) => $v > 0));
                    }
                }
            }

            $sectionsQuery = ContentSection::query()
                ->where('tenant_id', $tenantId)
                ->where('surface', $surface)
                ->where('status', 'active')
                ->where('is_enabled', true)
                ->when($publishedSectionIds !== [], fn ($q) => $q->whereIn('id', $publishedSectionIds))
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->whereIn('platform', [$platform, 'both'])
                ->with(['items' => function ($q) use ($now) {
                    $q->where('is_enabled', true)
                        ->where(function ($q2) use ($now) {
                            $q2->whereNull('start_at')->orWhere('start_at', '<=', $now);
                        })
                        ->where(function ($q2) use ($now) {
                            $q2->whereNull('end_at')->orWhere('end_at', '>=', $now);
                        })
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->with(['webMedia', 'mobileMedia']);
                }])
                ->orderByDesc('priority')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            // If a published order exists, preserve that exact order.
            $sections = $sectionsQuery;
            if ($publishedSectionIds !== []) {
                $order = array_flip($publishedSectionIds);
                $sections = $sectionsQuery->sortBy(fn (ContentSection $s) => $order[$s->id] ?? 999999)->values();
            }

            $categories = Category::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->orderBy('display_order')
                ->get(['id', 'name', 'slug', 'icon_url', 'banner_url', 'display_order']);

            return [
                'hero_banners' => $this->extractHeroBanners($sections),
                'sections' => $this->toPublicSections($sections),
                'popup_offer' => (object) [],
                'categories' => $categories->map(fn (Category $c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'icon_url' => $c->icon_url,
                    'banner_url' => $c->banner_url,
                    'display_order' => $c->display_order,
                ])->values()->all(),
                'versioning' => [
                    'version' => (int) ($latest->version ?? 0),
                    'published_at' => $latest?->published_at,
                ],
            ];
        };

        // Some cache stores (e.g. file) don't support tags.
        try {
            return Cache::tags(['tenant', (string) $tenantId, 'homepage'])->remember($cacheKey, $cacheTtl, $compute);
        } catch (\BadMethodCallException|\InvalidArgumentException $e) {
            return Cache::remember($cacheKey, $cacheTtl, $compute);
        }
    }

    /**
     * @param  Collection<int, ContentSection>  $sections
     * @return array<int, mixed>
     */
    private function extractHeroBanners(Collection $sections): array
    {
        $hero = $sections->first(fn (ContentSection $s) => $s->type === 'hero_carousel' || $s->type === 'carousel');
        if (! $hero) {
            return [];
        }

        return $hero->items->map(function ($item) {
            return $this->toPublicItem($item);
        })->values()->all();
    }

    /**
     * @param  Collection<int, ContentSection>  $sections
     * @return array<int, mixed>
     */
    private function toPublicSections(Collection $sections): array
    {
        return $sections->map(function (ContentSection $section) {
            return [
                'type' => $section->type,
                'title' => $section->title,
                'subtitle' => $section->subtitle,
                'slug' => $section->slug,
                'status' => $section->status,
                'sort_order' => $section->sort_order,
                'start_at' => optional($section->start_at)->toISOString(),
                'end_at' => optional($section->end_at)->toISOString(),
                'platform' => $section->platform,
                'background_color' => $section->background_color,
                'text_color' => $section->text_color,
                'metadata' => $section->metadata ?? (object) [],
                'items' => $section->items->map(fn ($item) => $this->toPublicItem($item))->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function toPublicItem($item): array
    {
        $webPath = $item->webMedia?->path;
        $mobilePath = $item->mobileMedia?->path;

        return [
            'sort_order' => $item->sort_order,
            'image_url' => $webPath ? url($webPath) : null,
            'mobile_image_url' => $mobilePath ? url($mobilePath) : null,
            'web_image_url' => $webPath ? url($webPath) : null,
            'cta_text' => $item->cta_text,
            'cta_type' => $item->cta_type,
            'cta_value' => $item->cta_value,
            'deeplink' => $item->deeplink,
            'redirect_url' => $item->redirect_url,
            // Optional structured links
            'product_id' => $item->product_id,
            'category_id' => $item->category_id,
            'brand_id' => $item->brand_id,
            'metadata' => $item->metadata ?? (object) [],
        ];
    }
}

