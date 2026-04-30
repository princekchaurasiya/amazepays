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

class HomepageSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = (int) (Tenant::query()->min('id') ?? 1);

        // Fix products that have no catalog audience (ensures they pass forStorefrontCatalog scope)
        Product::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('catalog_audience')
            ->update(['catalog_audience' => 'both']);

        $this->command->info('Creating default homepage sections for tenant: ' . $tenantId);

        // --- 1. Shop by Category Section ---
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
        ContentSectionItem::where('content_section_id', $categorySection->id)->delete();
        $categories = Category::query()->where('tenant_id', $tenantId)->get();
        foreach ($categories as $index => $category) {
            ContentSectionItem::create([
                'tenant_id' => $tenantId,
                'content_section_id' => $categorySection->id,
                'category_id' => $category->id,
                'sort_order' => $index + 1,
                'is_enabled' => true,
                'cta_type' => 'category',
            ]);
        }

        // --- 2. Featured Brands Section ---
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
        ContentSectionItem::where('content_section_id', $brandSection->id)->delete();
        $brands = Brand::query()
            ->where('tenant_id', $tenantId)
            ->whereNotIn('name', ['Woohoo', 'API TESTING SIT'])
            ->whereNotIn('slug', ['woohoo', 'api-testing-sit'])
            ->get();
        foreach ($brands as $index => $brand) {
            ContentSectionItem::create([
                'tenant_id' => $tenantId,
                'content_section_id' => $brandSection->id,
                'brand_id' => $brand->id,
                'sort_order' => $index + 1,
                'is_enabled' => true,
                'cta_type' => 'brand',
            ]);
        }

        // --- 3. Woohoo Products Section ---
        $woohooSection = ContentSection::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'woohoo-products'],
            [
                'surface' => 'storefront_home',
                'type' => 'featured_products',
                'title' => 'Woohoo Gift Cards',
                'status' => 'active',
                'is_enabled' => true,
                'platform' => 'both',
                'sort_order' => 30,
                'priority' => 80,
            ]
        );
        ContentSectionItem::where('content_section_id', $woohooSection->id)->delete();
        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('source_provider', 'woohoo')
            ->forStorefrontCatalog()
            ->limit(100)
            ->get();
        foreach ($products as $index => $product) {
            ContentSectionItem::create([
                'tenant_id' => $tenantId,
                'content_section_id' => $woohooSection->id,
                'product_id' => $product->id,
                'sort_order' => $index + 1,
                'is_enabled' => true,
                'cta_type' => 'product',
            ]);
        }

        // --- 4. Featured Products Section (Fallback/Other) ---
        $productSection = ContentSection::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => 'featured-products'],
            [
                'surface' => 'storefront_home',
                'type' => 'featured_products',
                'title' => 'Handpicked for You',
                'status' => 'active',
                'is_enabled' => true,
                'platform' => 'both',
                'sort_order' => 40,
                'priority' => 70,
            ]
        );
        ContentSectionItem::where('content_section_id', $productSection->id)->delete();
        $otherProducts = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('source_provider', '!=', 'woohoo')
            ->forStorefrontCatalog()
            ->limit(20)
            ->get();
        foreach ($otherProducts as $index => $product) {
            ContentSectionItem::create([
                'tenant_id' => $tenantId,
                'content_section_id' => $productSection->id,
                'product_id' => $product->id,
                'sort_order' => $index + 1,
                'is_enabled' => true,
                'cta_type' => 'product',
            ]);
        }

        // --- 5. Publish the layout ---
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
