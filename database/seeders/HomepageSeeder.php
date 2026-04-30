<?php

namespace Database\Seeders;

use App\Domains\Content\Models\ContentSection;
use App\Domains\Content\Models\ContentSectionItem;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) (Tenant::query()->min('id') ?? 1);

        $this->command->info('Creating default homepage sections for tenant: ' . $tenantId);

        // 1. Shop by Category Section
        $categorySection = ContentSection::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'shop-by-category'],
            [
                'surface' => 'storefront_home',
                'type' => 'grid_2',
                'title' => 'Shop by Category',
                'status' => 'active',
                'is_enabled' => true,
                'platform' => 'both',
                'sort_order' => 10,
                'priority' => 100,
            ]
        );

        $categories = Category::query()->where('tenant_id', $tenantId)->get();
        foreach ($categories as $index => $category) {
            ContentSectionItem::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'content_section_id' => $categorySection->id,
                    'category_id' => $category->id,
                ],
                [
                    'sort_order' => $index + 1,
                    'is_enabled' => true,
                    'cta_type' => 'category',
                ]
            );
        }

        // 2. Featured Brands Section (Excluding 'Woohoo')
        $brandSection = ContentSection::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'featured-brands'],
            [
                'surface' => 'storefront_home',
                'type' => 'voucher_slider',
                'title' => 'Featured Brands',
                'status' => 'active',
                'is_enabled' => true,
                'platform' => 'both',
                'sort_order' => 20,
                'priority' => 90,
            ]
        );

        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->where('name', '!=', 'Woohoo')
            ->where('slug', '!=', 'woohoo')
            ->get();
        foreach ($brands as $index => $brand) {
            ContentSectionItem::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'content_section_id' => $brandSection->id,
                    'brand_id' => $brand->id,
                ],
                [
                    'sort_order' => $index + 1,
                    'is_enabled' => true,
                    'cta_type' => 'brand',
                ]
            );
        }

        // 3. Featured Products Section
        $productSection = ContentSection::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'featured-products'],
            [
                'surface' => 'storefront_home',
                'type' => 'featured_products',
                'title' => 'Handpicked for You',
                'status' => 'active',
                'is_enabled' => true,
                'platform' => 'both',
                'sort_order' => 30,
                'priority' => 80,
            ]
        );

        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->forStorefrontCatalog()
            ->limit(50)
            ->get();
        foreach ($products as $index => $product) {
            ContentSectionItem::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'content_section_id' => $productSection->id,
                    'product_id' => $product->id,
                ],
                [
                    'sort_order' => $index + 1,
                    'is_enabled' => true,
                    'cta_type' => 'product',
                ]
            );
        }

        // 4. Publish the layout
        $this->command->info('Publishing homepage layout...');
        $ids = ContentSection::query()
            ->where('tenant_id', $tenantId)
            ->where('surface', 'storefront_home')
            ->orderByDesc('priority')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        DB::table('homepage_layout_versions')->updateOrInsert(
            ['tenant_id' => $tenantId, 'version' => 1],
            [
                'published_at' => now(),
                'layout_snapshot' => json_encode(['surface' => 'storefront_home', 'section_ids' => $ids]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('Homepage seeded successfully!');
    }
}
