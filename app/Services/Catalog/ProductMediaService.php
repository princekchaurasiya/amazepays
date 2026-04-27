<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductMediaService
{
    /**
     * Store an uploaded image and attach to product (global admin content — tenant_id null).
     */
    public function store(UploadedFile $file, Product $product, User $user): ProductMedia
    {
        $maxOrder = (int) ProductMedia::query()
            ->where('product_id', $product->id)
            ->max('display_order');

        return ProductMedia::query()->create([
            'product_id' => $product->id,
            'type' => 'gallery',
            // Current schema stores the URL in DB; we generate a stable placeholder name.
            // UI can upload to S3/CDN later and update the URL.
            'url' => (string) $file->getClientOriginalName(),
            'alt_text' => $file->getClientOriginalName(),
            'display_order' => $maxOrder + 1,
            'is_primary' => false,
        ]);
    }

    public function delete(ProductMedia $media): void
    {
        $media->delete();
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(Product $product, array $orderedIds): void
    {
        DB::transaction(function () use ($product, $orderedIds) {
            $position = 0;
            foreach ($orderedIds as $id) {
                ProductMedia::query()
                    ->where('product_id', $product->id)
                    ->where('id', $id)
                    ->update(['display_order' => $position++]);
            }
        });
    }

    /**
     * Mark one media as hero; others in gallery stay gallery.
     */
    public function setHero(Product $product, ProductMedia $media): void
    {
        if ((int) $media->product_id !== (int) $product->id) {
            abort(422, 'Media does not belong to this product.');
        }

        DB::transaction(function () use ($product, $media) {
            ProductMedia::query()
                ->where('product_id', $product->id)
                ->update(['type' => 'gallery', 'is_primary' => false]);
            $media->update(['type' => 'hero', 'is_primary' => true]);
        });
    }
}
