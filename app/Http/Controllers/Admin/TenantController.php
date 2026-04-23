<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Admin panel -- B2B Tenant (reseller/loyalty partner) management.
 */
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%")
            )
            ->latest()
            ->paginate(25);

        return Inertia::render('Admin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Tenants/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:tenants,slug',
            'contact_email' => 'required|email',
            'margin_percentage' => 'nullable|numeric|min:0|max:100',
            'wallet_limit' => 'nullable|numeric|min:0',
        ]);

        $tenant = Tenant::create($validated);
        audit('tenant.created', $tenant);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('success', "Tenant {$tenant->name} created.");
    }

    public function show(Request $request, Tenant $tenant)
    {
        $assignedIds = $tenant->products()->pluck('products.id')->all();

        $catalogProducts = Product::query()
            ->forB2bCatalog()
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'sku', 'name'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => (string) ($p->product_name ?: $p->name),
                'sku' => $p->sku,
            ])
            ->all();

        return Inertia::render('Admin/Tenants/Show', [
            'tenant' => $tenant->loadCount(['users', 'orders']),
            'catalogProducts' => $catalogProducts,
            'assignedProductIds' => $assignedIds,
            'canAssignProducts' => $request->user()->can('tenants.assign_products'),
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'margin_percentage' => 'nullable|numeric|min:0|max:100',
            'wallet_limit' => 'nullable|numeric|min:0',
        ]);

        $old = $tenant->only(array_keys($validated));
        $tenant->update($validated);
        audit('tenant.updated', $tenant, $old, $validated);

        return back()->with('success', "Tenant {$tenant->name} updated.");
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['is_active' => false]);
        audit('tenant.suspended', $tenant);

        return back()->with('success', "Tenant {$tenant->name} suspended.");
    }

    public function assignProducts(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'product_ids' => 'present|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $tenant->products()->sync($validated['product_ids']);
        audit('tenant.products_assigned', $tenant, [], ['product_ids' => $validated['product_ids']]);

        return back()->with('success', 'Product catalog updated for this tenant.');
    }
}
