<?php

namespace App\Services\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Models\StorefrontBrand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SlidePresentationService
{
    /** Active homepage hero slides (same rules as legacy storefront). */
    public function homepageSlides(): Collection
    {
        return Slide::query()
            ->where('status', 1)
            ->where('display_on_page', 'homepage')
            ->orderBy('priority', 'asc')
            ->orderByRaw('priority IS NULL ASC')
            ->get();
    }

    /**
     * Resolve target slugs for product/category/brand links (batch, no N+1).
     *
     * @param  Collection<int, Slide>  $slides
     */
    public function attachSlugs(Collection $slides): void
    {
        $productIds = [];
        $categoryIds = [];
        $brandIds = [];

        foreach ($slides as $slide) {
            if ($slide->product_id) {
                $productIds[] = $slide->product_id;
            }
            if ($slide->category_id) {
                $categoryIds[] = $slide->category_id;
            }
            if ($slide->brand_id) {
                $brandIds[] = $slide->brand_id;
            }
        }

        $products = ! empty($productIds)
            ? Product::whereIn('id', array_unique($productIds))->pluck('slug', 'id')->all()
            : [];
        $categories = ! empty($categoryIds)
            ? Category::whereIn('id', array_unique($categoryIds))->pluck('slug', 'id')->all()
            : [];
        $brands = ! empty($brandIds)
            ? StorefrontBrand::whereIn('id', array_unique($brandIds))->pluck('slug', 'id')->all()
            : [];

        foreach ($slides as $slide) {
            if ($slide->product_id && isset($products[$slide->product_id])) {
                $slide->slug = $products[$slide->product_id];
            } elseif ($slide->category_id && isset($categories[$slide->category_id])) {
                $slide->slug = $categories[$slide->category_id];
            } elseif ($slide->brand_id && isset($brands[$slide->brand_id])) {
                $slide->slug = $brands[$slide->brand_id];
            } else {
                $slide->slug = null;
            }
        }
    }

    public function absoluteStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return url(Storage::url($path));
    }

    public function isLinked(Slide $slide): bool
    {
        if (filled($slide->custom_url) || filled($slide->cta_link)) {
            return true;
        }

        if ($slide->product_id || $slide->category_id || $slide->brand_id) {
            return filled($slide->slug ?? null);
        }

        return false;
    }

    /**
     * Payload for Inertia storefront + mobile API (absolute image URLs).
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(Slide $slide): array
    {
        return [
            'id' => $slide->id,
            'desktop_image' => $this->absoluteStorageUrl($slide->desktop_image),
            'image_mobile' => $this->absoluteStorageUrl($slide->image_mobile),
            'slug' => $slide->slug ?? null,
            'product_id' => $slide->product_id,
            'category_id' => $slide->category_id,
            'brand_id' => $slide->brand_id,
            'is_linked' => $this->isLinked($slide),
            'img_alt_tag' => $slide->img_alt_tag,
            'cta_link' => $slide->cta_link,
            'custom_url' => $slide->custom_url,
            'link_type' => $slide->link_type,
        ];
    }
}
