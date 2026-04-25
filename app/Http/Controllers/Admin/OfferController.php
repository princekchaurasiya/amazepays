<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('offers.view');

        $offers = Offer::with('tenant')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->status, function ($q) use ($request) {
                $q->where('is_active', $request->status === 'active');
            })
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Offers/Index', [
            'offers' => $offers,
            'filters' => $request->only(['search', 'status', 'type']),
            'types' => Offer::$offerTypes ?? [],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('offers.create');

        return Inertia::render('Admin/Offers/Form', [
            'offer' => null,
            'tenants' => Tenant::active()->pluck('name', 'id'),
            'products' => Product::visible()->select('id', 'name', 'sku')->orderBy('name')->limit(500)->get(),
            'brands' => Brand::query()->select('id', 'name')->orderBy('name')->get(),
            'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('offers.create');

        $validated = $this->validateOffer($request);

        $offer = Offer::create($validated);

        audit('offer.created', $offer, [], $validated);

        return redirect()->route('panel.offers.index')
            ->with('success', 'Offer created successfully.');
    }

    public function edit(Offer $offer): Response
    {
        $this->authorize('offers.update');

        return Inertia::render('Admin/Offers/Form', [
            'offer' => $offer,
            'tenants' => Tenant::active()->pluck('name', 'id'),
            'products' => Product::visible()->select('id', 'name', 'sku')->orderBy('name')->limit(500)->get(),
            'brands' => Brand::query()->select('id', 'name')->orderBy('name')->get(),
            'categories' => Category::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $this->authorize('offers.update');

        $old = $offer->toArray();
        $validated = $this->validateOffer($request, $offer);

        $offer->update($validated);

        audit('offer.updated', $offer, $old, $validated);

        return back()->with('success', 'Offer updated successfully.');
    }

    public function toggle(Offer $offer)
    {
        $this->authorize('offers.toggle');

        $offer->update(['is_active' => ! $offer->is_active]);

        audit('offer.toggled', $offer, [], ['is_active' => $offer->is_active]);

        return back()->with('success', 'Offer status updated.');
    }

    public function destroy(Offer $offer)
    {
        $this->authorize('offers.delete');

        audit('offer.deleted', $offer, $offer->toArray(), []);
        $offer->delete();

        return redirect()->route('panel.offers.index')
            ->with('success', 'Offer deleted.');
    }

    private function validateOffer(Request $request, ?Offer $offer = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:offers,code'.($offer ? ",{$offer->id}" : ''),
            'type' => 'required|in:flat_discount,percentage_discount,cashback,buy_x_get_y,first_purchase,category_specific,brand_specific,product_specific',
            'discount_value' => 'required_if:type,flat_discount,cashback|numeric|min:0',
            'discount_percentage' => 'required_if:type,percentage_discount|numeric|min:0|max:100',
            'min_order_value' => 'numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'integer|min:1',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'tenant_id' => 'nullable|exists:tenants,id',
            'applicable_product_ids' => 'nullable|array',
            'applicable_product_ids.*' => 'integer|exists:products,id',
            'applicable_category_ids' => 'nullable|array',
            'applicable_category_ids.*' => 'integer|exists:categories,id',
            'applicable_brand_ids' => 'nullable|array',
            'applicable_brand_ids.*' => 'integer|exists:brands,id',
            'description' => 'nullable|string',
        ]);
    }
}
