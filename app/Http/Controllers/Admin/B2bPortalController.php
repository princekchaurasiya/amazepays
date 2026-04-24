<?php

namespace App\Http\Controllers\Admin;

use App\Data\BillingData;
use App\Data\OrderData;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderCreationException;
use App\Exceptions\WalletFrozenException;
use App\Exports\B2bPriceListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\B2b\B2bPlaceOrderRequest;
use App\Http\Requests\Wallet\SubmitWalletLoadRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\WalletLoadRequest;
use App\Services\B2b\B2bCatalogService;
use App\Services\Order\OrderCreationService;
use App\Services\Wallet\WalletLoadRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class B2bPortalController extends Controller
{
    public function __construct(
        private B2bCatalogService $catalogService,
        private OrderCreationService $orderCreationService,
        private WalletLoadRequestService $walletLoadRequestService,
    ) {}

    public function shop(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $user = $request->user();

        $filters = [
            'q' => $request->string('q')->toString(),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'currency' => $request->filled('currency') ? $request->string('currency')->toString() : null,
        ];

        $categories = $tenant ? $this->catalogService->categoriesForTenant($tenant) : [];
        $products = $tenant ? $this->catalogService->catalogRows($tenant, $user, $filters) : collect();
        $currencyOptions = $tenant ? $this->catalogService->currencyOptions($tenant) : [];
        $stats = $tenant ? $this->catalogService->tenantAssignmentStats($tenant) : [
            'assigned_active_count' => 0,
            'b2b_eligible_count' => 0,
        ];

        return Inertia::render('Admin/B2B/Shop', [
            'tenant' => $this->tenantPayload($tenant),
            'categories' => $categories,
            'products' => $products->values()->all(),
            'currencyOptions' => $currencyOptions,
            'filters' => $filters,
            'walletBalance' => (float) ($user->wallet?->balance ?? 0),
            'catalogMeta' => [
                'assignedActiveCount' => $stats['assigned_active_count'],
                'b2bEligibleCount' => $stats['b2b_eligible_count'],
            ],
            'canAssignTenantCatalog' => (bool) $user->can('tenants.assign_products'),
        ]);
    }

    /**
     * B2B clients with tenants.assign_products can curate their company's shop assortment
     * without tenants.view (full tenant admin list).
     */
    public function catalogManage(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && $user->can('tenants.assign_products'), 403);

        $tenant = $this->resolveTenant($request);
        if (! $tenant || ! $user->tenants()->where('tenants.id', $tenant->id)->exists()) {
            return Inertia::render('Admin/B2B/CatalogManage', [
                'tenant' => $this->tenantPayload($tenant),
                'catalogProducts' => [],
                'assignedProductIds' => [],
                'canManage' => false,
            ]);
        }

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

        return Inertia::render('Admin/B2B/CatalogManage', [
            'tenant' => $this->tenantPayload($tenant),
            'catalogProducts' => $catalogProducts,
            'assignedProductIds' => $assignedIds,
            'canManage' => true,
        ]);
    }

    public function updateCatalog(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('tenants.assign_products'), 403);

        $tenant = $this->resolveTenant($request);
        abort_unless($tenant && $user->tenants()->where('tenants.id', $tenant->id)->exists(), 403);

        $validated = $request->validate([
            'product_ids' => 'present|array',
            'product_ids.*' => 'integer|exists:products,id',
        ]);

        $requested = collect($validated['product_ids'])->unique()->values()->all();
        $allowedIds = Product::query()
            ->forB2bCatalog()
            ->whereIn('id', $requested)
            ->pluck('id')
            ->all();

        sort($requested);
        $allowedSorted = $allowedIds;
        sort($allowedSorted);
        if ($requested !== $allowedSorted) {
            return back()->withErrors([
                'product_ids' => 'One or more products are not B2B-eligible (catalog audience must be Business or Both).',
            ]);
        }

        $tenant->products()->sync($requested);
        audit('tenant.products_assigned', $tenant, [], ['product_ids' => $requested]);

        return redirect()->route('b2b.catalog.manage')->with('success', 'Company catalog updated.');
    }

    public function priceList(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $user = $request->user();

        $filters = [
            'q' => $request->string('q')->toString(),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'currency' => $request->filled('currency') ? $request->string('currency')->toString() : null,
        ];

        $categories = $tenant ? $this->catalogService->categoriesForTenant($tenant) : [];
        $rows = $tenant ? $this->catalogService->catalogRows($tenant, $user, $filters) : collect();
        $currencyOptions = $tenant ? $this->catalogService->currencyOptions($tenant) : [];

        return Inertia::render('Admin/B2B/PriceList', [
            'tenant' => $this->tenantPayload($tenant),
            'categories' => $categories,
            'rows' => $rows->values()->all(),
            'currencyOptions' => $currencyOptions,
            'filters' => $filters,
        ]);
    }

    public function exportPriceList(Request $request): BinaryFileResponse
    {
        $tenant = $this->resolveTenant($request);
        $user = $request->user();

        $filters = [
            'q' => $request->string('q')->toString(),
            'category_id' => $request->filled('category_id') ? (int) $request->input('category_id') : null,
            'currency' => $request->filled('currency') ? $request->string('currency')->toString() : null,
        ];

        $rows = $tenant ? $this->catalogService->catalogRows($tenant, $user, $filters) : collect();

        $filename = 'b2b-price-list-'.now()->format('Y-m-d-His').'.xlsx';

        return Excel::download(new B2bPriceListExport($rows), $filename);
    }

    public function financialActivity(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $user = $request->user();

        $dateFrom = $request->date('date_from')?->startOfDay();
        $dateTo = $request->date('date_to')?->endOfDay();
        $includeArchived = $request->boolean('include_archived');
        $tab = $request->string('tab')->toString() ?: 'all';
        if (! in_array($tab, ['all', 'payments', 'credits', 'debits'], true)) {
            $tab = 'all';
        }

        $rows = collect();

        $includeWalletTx = in_array($tab, ['all', 'credits', 'debits'], true);

        if ($user->wallet && $includeWalletTx) {
            $txQuery = $user->wallet->transactions()->orderByDesc('created_at');

            if (! $includeArchived) {
                $txQuery->where('wallet_transactions.created_at', '>=', now()->subMonths(6));
            }
            if ($dateFrom) {
                $txQuery->where('wallet_transactions.created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $txQuery->where('wallet_transactions.created_at', '<=', $dateTo);
            }

            if ($tab === 'credits') {
                $txQuery->where('type', 'credit');
            } elseif ($tab === 'debits') {
                $txQuery->where('type', 'debit');
            }

            foreach ($txQuery->get() as $t) {
                $rows->push([
                    'kind' => 'wallet',
                    'type' => $t->type,
                    'label' => $t->type === 'credit' ? 'Credit' : 'Debit',
                    'reference' => $t->reference,
                    'description' => $t->description,
                    'amount' => (float) $t->amount,
                    'status' => 'posted',
                    'created_at' => $t->created_at?->toIso8601String(),
                ]);
            }
        }

        if ($tenant && in_array($tab, ['all', 'payments'], true)) {
            $loadQuery = WalletLoadRequest::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->orderByDesc('created_at');

            if (! $includeArchived) {
                $loadQuery->where('created_at', '>=', now()->subMonths(6));
            }
            if ($dateFrom) {
                $loadQuery->where('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $loadQuery->where('created_at', '<=', $dateTo);
            }

            foreach ($loadQuery->get() as $lr) {
                $rows->push([
                    'kind' => 'load_request',
                    'type' => 'load_request',
                    'label' => 'Wallet load',
                    'reference' => (string) $lr->id,
                    'description' => trim(implode(' ', array_filter([
                        $lr->payment_mode,
                        $lr->reference_no ? 'Ref '.$lr->reference_no : null,
                    ]))),
                    'amount' => (float) $lr->amount,
                    'status' => $lr->status,
                    'created_at' => $lr->created_at?->toIso8601String(),
                ]);
            }
        }

        $rows = $rows->sortByDesc('created_at')->values();

        return Inertia::render('Admin/B2B/FinancialActivity', [
            'tenant' => $this->tenantPayload($tenant),
            'rows' => $rows->all(),
            'filters' => [
                'date_from' => $dateFrom?->format('Y-m-d'),
                'date_to' => $dateTo?->format('Y-m-d'),
                'include_archived' => $includeArchived,
                'tab' => $tab,
            ],
        ]);
    }

    public function storeOrder(B2bPlaceOrderRequest $request): RedirectResponse
    {
        $tenant = $this->resolveTenant($request);
        if (! $tenant || ! $request->user()->tenants()->where('tenants.id', $tenant->id)->exists()) {
            return back()->withErrors(['tenant' => 'Your account is not linked to this company.']);
        }

        $validated = $request->validated();
        $user = $request->user();

        $orderData = new OrderData(
            productId: (int) $validated['product_id'],
            quantity: (int) $validated['quantity'],
            denomination: (float) $validated['denomination'],
            paymentMethod: $validated['payment_method'],
            offerCode: $validated['offer_code'] ?? null,
            giftOption: 'buy_for_self',
            receiverName: null,
            receiverEmail: null,
            receiverMobile: null,
            receiverMessage: null,
            vdBrandCode: null,
        );

        $billingData = new BillingData(
            name: $user->name,
            email: $user->email,
            phone: $user->mobile ?? '',
            address: $user->billing_address ?? '',
            city: $user->billing_city ?? '',
            state: $user->billing_state ?? '',
            zip: $user->billing_zip ?? '',
        );

        try {
            $this->orderCreationService->create($user, $orderData, $billingData);
        } catch (OrderCreationException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        } catch (InsufficientBalanceException $e) {
            return back()->withErrors(['order' => 'Insufficient wallet balance for this order.']);
        } catch (WalletFrozenException $e) {
            return back()->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('b2b.orders.index')->with('success', 'Order placed successfully.');
    }

    public function orders(Request $request): Response
    {
        $request->validate([
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d',
        ]);

        $tenant = $this->resolveTenant($request);
        $orders = collect();

        if ($tenant) {
            $query = Order::query()
                ->where('tenant_id', $tenant->id)
                ->with(['user:id,name,email'])
                ->latest();

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->input('date_to'));
            }

            $orders = $query
                ->limit(100)
                ->get(['id', 'user_id', 'tenant_id', 'order_status', 'grand_payable_amount', 'created_at', 'order_number']);
        }

        return Inertia::render('Admin/B2B/Orders', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ] : null,
            'orders' => $orders,
            'filters' => $request->only('date_from', 'date_to'),
        ]);
    }

    public function team(Request $request): Response
    {
        $tenant = $this->resolveTenant($request);
        $members = collect();

        if ($tenant) {
            $members = $tenant->users()
                ->get(['users.id', 'users.name', 'users.email', 'users.mobile'])
                ->map(function ($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'mobile' => $u->mobile,
                        'role' => $u->pivot->role ?? null,
                        'is_primary' => (bool) ($u->pivot->is_primary ?? false),
                    ];
                });
        }

        return Inertia::render('Admin/B2B/Team', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ] : null,
            'members' => $members,
        ]);
    }

    public function wallet(Request $request): Response
    {
        $user = $request->user();
        $tenant = $this->resolveTenant($request);
        $wallet = $user->wallet;

        $loadRequests = WalletLoadRequest::query()
            ->where('user_id', $user->id)
            ->when($tenant, fn ($q) => $q->where('tenant_id', $tenant->id))
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn (WalletLoadRequest $r) => [
                'id' => $r->id,
                'amount' => (float) $r->amount,
                'payment_mode' => $r->payment_mode,
                'reference_no' => $r->reference_no,
                'status' => $r->status,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Admin/B2B/Wallet', [
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'credit_limit' => $tenant->credit_limit,
                'current_balance' => $tenant->current_balance,
            ] : null,
            'wallet' => $wallet ? [
                'balance' => (float) $wallet->balance,
            ] : ['balance' => 0.0],
            'load_requests' => $loadRequests,
        ]);
    }

    public function storeWalletLoadRequest(SubmitWalletLoadRequest $request): RedirectResponse
    {
        $this->walletLoadRequestService->submitForUser($request->user(), $request);

        return back()->with('success', 'Load request submitted. You will be notified after review.');
    }

    private function resolveTenant(Request $request): ?Tenant
    {
        if (app()->bound('current_tenant') && app('current_tenant') instanceof Tenant) {
            return app('current_tenant');
        }

        return $request->user()?->currentTenant();
    }

    /**
     * @return array{id: int, name: string, slug?: string, credit_limit?: mixed, current_balance?: mixed}|null
     */
    private function tenantPayload(?Tenant $tenant): ?array
    {
        if (! $tenant) {
            return null;
        }

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'credit_limit' => $tenant->credit_limit,
            'current_balance' => $tenant->current_balance,
        ];
    }
}
