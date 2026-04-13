<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    /**
     * Lightweight JSON suggestions for storefront typeahead (no Inertia).
     */
    public function suggest(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.$q.'%';

        $products = Product::query()
            ->where('show_product', true)
            ->where(function ($w) use ($like) {
                $w->where('name', 'LIKE', $like)
                    ->orWhere('product_name', 'LIKE', $like);
            })
            ->orderByRaw(
                'CASE WHEN `name` LIKE ? OR `product_name` LIKE ? THEN 0 ELSE 1 END',
                [$like, $like]
            )
            ->limit(8)
            ->get();

        return response()->json(
            $products
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->display_name,
                    'slug' => trim((string) ($p->url ?: $p->slug ?? '')),
                    'image' => $p->display_image_url,
                    'discount' => $p->discount_percentage,
                ])
                ->filter(fn (array $row) => $row['slug'] !== '')
                ->values()
        );
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->input('query', ''));
        $like = '%'.$query.'%';

        $results = Product::query()
            ->where('show_product', true)
            ->where(function ($q2) use ($like) {
                $q2->where('name', 'LIKE', $like)
                    ->orWhere('product_name', 'LIKE', $like)
                    ->orWhere('description', 'LIKE', $like);
            })
            // Bound parameters — avoids broken SQL when the search text contains `'` or `\`.
            ->orderByRaw(
                'CASE WHEN `name` LIKE ? OR `product_name` LIKE ? THEN 0 ELSE 1 END, CASE WHEN `description` LIKE ? THEN 0 ELSE 1 END',
                [$like, $like, $like]
            )
            ->get();

        return Inertia::render('Storefront/Search', [
            'results' => $results,
            'query' => $query,
        ]);
    }
}
