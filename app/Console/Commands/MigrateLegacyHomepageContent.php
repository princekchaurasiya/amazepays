<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MigrateLegacyHomepageContent extends Command
{
    protected $signature = 'content:migrate-legacy-homepage {--tenant_id=} {--surface=storefront_home}';

    protected $description = 'Migrate legacy homepage_sections/slides into content_sections/content_section_items.';

    public function handle(): int
    {
        $tenantId = $this->resolveTenantId();
        $surface = (string) $this->option('surface');

        if (! Schema::hasTable('content_sections') || ! Schema::hasTable('content_section_items') || ! Schema::hasTable('media_assets')) {
            $this->error('Target CMS tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $migratedAny = false;

        if (Schema::hasTable('homepage_sections')) {
            $migratedAny = $this->migrateLegacyHomepageSections($tenantId, $surface) || $migratedAny;
        } else {
            $this->line('Skipping homepage_sections: table not found.');
        }

        if (Schema::hasTable('slides')) {
            $migratedAny = $this->migrateLegacySlides($tenantId, $surface) || $migratedAny;
        } else {
            $this->line('Skipping slides: table not found.');
        }

        if (! $migratedAny) {
            $this->info('No legacy rows migrated (or legacy tables missing).');
        } else {
            $this->info('Legacy homepage migration completed.');
        }

        return self::SUCCESS;
    }

    private function resolveTenantId(): int
    {
        $tenantId = $this->option('tenant_id');
        if ($tenantId !== null && $tenantId !== '') {
            return (int) $tenantId;
        }

        if (! Schema::hasTable('tenants')) {
            // In an environment without tenants, we still need a value because the new tables require tenant_id.
            // Using 1 is consistent with typical single-tenant setups.
            return 1;
        }

        $first = DB::table('tenants')->orderBy('id')->value('id');

        return (int) ($first ?? 1);
    }

    private function migrateLegacyHomepageSections(int $tenantId, string $surface): bool
    {
        $rows = DB::table('homepage_sections')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->line('homepage_sections: no rows.');

            return false;
        }

        $created = 0;

        foreach ($rows as $row) {
            $legacyId = (int) $row->id;
            $sectionType = (string) ($row->section_type ?? $row->section_name ?? 'custom');

            $slugBase = (string) ($row->section_name ?? $row->section_type ?? ('legacy-section-'.$legacyId));
            $slug = Str::slug($slugBase);
            if ($slug === '') {
                $slug = 'legacy-section-'.$legacyId;
            }

            $exists = DB::table('content_sections')
                ->where('tenant_id', $tenantId)
                ->where('surface', $surface)
                ->where('slug', $slug)
                ->exists();

            if ($exists) {
                continue;
            }

            $metadata = [
                'legacy' => [
                    'source_table' => 'homepage_sections',
                    'source_id' => $legacyId,
                ],
                'config' => $this->safeJsonToArray($row->config ?? null),
            ];

            DB::table('content_sections')->insert([
                'tenant_id' => $tenantId,
                'surface' => $surface,
                'slug' => $slug,
                'type' => $this->mapLegacySectionType($sectionType),
                'status' => ((int) ($row->status ?? 1)) === 1 ? 'active' : 'draft',
                'is_enabled' => ((int) ($row->status ?? 1)) === 1,
                'sort_order' => (int) ($row->sort_order ?? 0),
                'platform' => 'both',
                'start_at' => null,
                'end_at' => null,
                'priority' => 0,
                'title' => $row->title ?? null,
                'subtitle' => null,
                'background_color' => null,
                'text_color' => null,
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            $created++;
        }

        $this->info("homepage_sections: migrated {$created} section(s).");

        return $created > 0;
    }

    private function migrateLegacySlides(int $tenantId, string $surface): bool
    {
        $rows = DB::table('slides')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->line('slides: no rows.');

            return false;
        }

        $heroSlug = 'hero-banners';
        $heroSectionId = $this->ensureSection($tenantId, $surface, $heroSlug, 'carousel', [
            'legacy' => ['source_table' => 'slides'],
        ]);

        $created = 0;

        foreach ($rows as $row) {
            $legacyId = (int) $row->id;

            $already = DB::table('content_section_items')
                ->where('tenant_id', $tenantId)
                ->where('content_section_id', $heroSectionId)
                ->whereJsonContains('metadata->legacy->source_table', 'slides')
                ->whereJsonContains('metadata->legacy->source_id', $legacyId)
                ->exists();

            if ($already) {
                continue;
            }

            $webPath = $row->desktop_image ?? $row->image_url ?? null;
            $mobilePath = $row->image_mobile ?? $row->mobile_image_url ?? null;

            $webMediaId = $webPath ? $this->upsertMediaAsset($tenantId, (string) $webPath) : null;
            $mobileMediaId = $mobilePath ? $this->upsertMediaAsset($tenantId, (string) $mobilePath) : null;

            $metadata = [
                'legacy' => [
                    'source_table' => 'slides',
                    'source_id' => $legacyId,
                ],
                'raw' => [
                    'link_type' => $row->link_type ?? null,
                    'custom_url' => $row->custom_url ?? null,
                ],
            ];

            DB::table('content_section_items')->insert([
                'tenant_id' => $tenantId,
                'content_section_id' => $heroSectionId,
                'sort_order' => (int) ($row->priority ?? $row->display_order ?? 0),
                'is_enabled' => ((int) ($row->status ?? 1)) === 1,
                'start_at' => null,
                'end_at' => null,
                'priority' => 0,
                'title' => $row->big_header ?? $row->title ?? null,
                'subtitle' => $row->small_header ?? $row->subtitle ?? null,
                'web_media_asset_id' => $webMediaId,
                'mobile_media_asset_id' => $mobileMediaId,
                'cta_text' => null,
                'cta_type' => $row->cta_link ? 'url' : null,
                'cta_value' => $row->cta_link ?? null,
                'deeplink' => null,
                'redirect_url' => $row->cta_link ?? null,
                'product_id' => $row->product_id ?? null,
                'category_id' => $row->category_id ?? null,
                'brand_id' => null, // legacy slides used storefront_brands; unified onto brands in Phase A code refactor
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            $created++;
        }

        $this->info("slides: migrated {$created} item(s) into section {$heroSlug}.");

        return $created > 0;
    }

    private function ensureSection(int $tenantId, string $surface, string $slug, string $type, array $metadata): int
    {
        $existing = DB::table('content_sections')
            ->where('tenant_id', $tenantId)
            ->where('surface', $surface)
            ->where('slug', $slug)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        DB::table('content_sections')->insert([
            'tenant_id' => $tenantId,
            'surface' => $surface,
            'slug' => $slug,
            'type' => $type,
            'status' => 'active',
            'is_enabled' => true,
            'sort_order' => 0,
            'platform' => 'both',
            'start_at' => null,
            'end_at' => null,
            'priority' => 0,
            'title' => null,
            'subtitle' => null,
            'background_color' => null,
            'text_color' => null,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        return (int) DB::table('content_sections')
            ->where('tenant_id', $tenantId)
            ->where('surface', $surface)
            ->where('slug', $slug)
            ->value('id');
    }

    private function upsertMediaAsset(int $tenantId, string $path): int
    {
        $path = trim($path);

        $existing = DB::table('media_assets')
            ->where('tenant_id', $tenantId)
            ->where('path', $path)
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        DB::table('media_assets')->insert([
            'tenant_id' => $tenantId,
            'path' => $path,
            'disk' => 'public',
            'mime' => null,
            'width' => null,
            'height' => null,
            'sha256' => null,
            'alt_text' => null,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);

        return (int) DB::table('media_assets')
            ->where('tenant_id', $tenantId)
            ->where('path', $path)
            ->value('id');
    }

    private function mapLegacySectionType(string $sectionType): string
    {
        $key = Str::of($sectionType)->lower()->toString();

        return match ($key) {
            'banner' => 'banner_single',
            'brands' => 'voucher_slider',
            'categories' => 'category_carousel',
            'hot_deals', 'other_deals' => 'featured_products',
            'kgen' => 'custom',
            default => 'custom',
        };
    }

    private function safeJsonToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}

