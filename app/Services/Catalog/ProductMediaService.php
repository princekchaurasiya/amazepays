<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductMediaService
{
    /**
     * Store an uploaded image and attach to product (global admin content — tenant_id null).
     */
    public function store(UploadedFile $file, Product $product, User $user): ProductMedia
    {
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.$ext;
        $dir = 'products/'.$product->id.'/media';
        $path = $file->storeAs($dir, $filename, 'public');

        $maxSort = (int) ProductMedia::query()
            ->where('product_id', $product->id)
            ->whereNull('tenant_id')
            ->max('sort_order');

        return ProductMedia::query()->create([
            'product_id' => $product->id,
            'tenant_id' => null,
            'collection' => 'gallery',
            'disk' => 'public',
            'path' => $path,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
            'alt_text' => $file->getClientOriginalName(),
            'sort_order' => $maxSort + 1,
            'uploaded_by' => $user->id,
        ]);
    }

    public function delete(ProductMedia $media): void
    {
        if (Storage::disk($media->disk)->exists($media->path)) {
            Storage::disk($media->disk)->delete($media->path);
        }
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
                    ->whereNull('tenant_id')
                    ->where('id', $id)
                    ->update(['sort_order' => $position++]);
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
                ->whereNull('tenant_id')
                ->update(['collection' => 'gallery']);
            $media->update(['collection' => 'hero']);
        });
    }
}
