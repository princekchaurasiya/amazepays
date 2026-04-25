<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('categories.manage');

        $categories = Category::query()
            ->withCount('products')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        $productIdsByCategory = DB::table('category_product')
            ->whereIn('category_id', $categories->pluck('id'))
            ->get()
            ->groupBy('category_id')
            ->map(fn ($rows) => $rows->pluck('product_id')->values()->all());

        $categoryRows = $categories->map(function (Category $c) use ($productIdsByCategory) {
            return [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'order' => (float) $c->order,
                'thumbnail' => $c->thumbnail ? Storage::disk('public')->url($c->thumbnail) : null,
                'accent_color' => $c->accent_color,
                'products_count' => $c->products_count,
                'product_ids' => $productIdsByCategory->get($c->id, []),
            ];
        })->values()->all();

        $products = Product::query()
            ->select('id', 'name', 'product_name', 'sku')
            ->orderByRaw('COALESCE(product_name, name)')
            ->limit(2000)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'label' => $p->display_name,
                'sku' => $p->sku,
            ])
            ->values()
            ->all();

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categoryRows,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('categories.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'order' => ['nullable', 'numeric'],
            'accent_color' => ['nullable', 'string', 'max:7'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
        ]);

        $accent = isset($validated['accent_color']) ? trim((string) $validated['accent_color']) : '';

        $data = [
            'name' => $validated['name'],
            'order' => $validated['order'] ?? 0,
            'accent_color' => preg_match('/^#[0-9A-Fa-f]{6}$/', $accent) ? $accent : null,
        ];

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $category = Category::create($data);

        audit('category.created', $category, [], $data);

        return redirect()->route('panel.categories.index')
            ->with('success', 'Category created.');
    }

    public function update(Request $request, Category $category)
    {
        $this->authorize('categories.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'order' => ['nullable', 'numeric'],
            'accent_color' => ['nullable', 'string', 'max:7'],
            'thumbnail' => ['nullable', 'image', 'max:2048'],
        ]);

        $old = $category->only(['name', 'order', 'thumbnail', 'accent_color']);

        $data = [
            'name' => $validated['name'],
            'order' => $validated['order'] ?? $category->order,
        ];

        if (array_key_exists('accent_color', $validated)) {
            $accent = trim((string) $validated['accent_color']);
            $data['accent_color'] = ($accent !== '' && preg_match('/^#[0-9A-Fa-f]{6}$/', $accent)) ? $accent : null;
        }

        if ($request->hasFile('thumbnail')) {
            if ($category->thumbnail) {
                Storage::disk('public')->delete($category->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $category->update($data);

        audit('category.updated', $category, $old, $data);

        return redirect()->route('panel.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $this->authorize('categories.manage');

        $snapshot = $category->only(['id', 'name', 'slug', 'thumbnail']);
        audit('category.deleted', $category, $snapshot, []);

        if ($category->thumbnail) {
            Storage::disk('public')->delete($category->thumbnail);
        }

        $category->delete();

        return redirect()->route('panel.categories.index')
            ->with('success', 'Category deleted.');
    }

    public function assignProducts(Request $request, Category $category)
    {
        $this->authorize('categories.manage');

        $validated = $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $ids = $validated['product_ids'] ?? [];
        $category->products()->sync($ids);

        audit('category.products_assigned', $category, [], ['product_ids' => $ids]);

        return back()->with('success', 'Category products updated.');
    }
}
