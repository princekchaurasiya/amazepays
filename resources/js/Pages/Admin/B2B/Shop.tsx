import React, { FormEvent, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import CategoryNav, { type StorefrontCategory } from '@/Components/Storefront/CategoryNav';
import { Search, ShoppingCart, X } from 'lucide-react';

type Category = StorefrontCategory;
type ProductRow = {
    id: number;
    name: string;
    sku: string | null;
    image_url: string | null;
    currency_code: string;
    price_range_label: string;
    representative_denomination: number;
    discount_percentage: number;
    discount_source: string | null;
    sample_grand_total: number;
    category_ids: number[];
};

type Props = {
    tenant: { id: number; name: string; slug?: string } | null;
    categories: Category[];
    products: ProductRow[];
    currencyOptions: string[];
    filters: { q: string; category_id: number | null; currency: string | null };
    walletBalance: number;
    catalogMeta: { assignedActiveCount: number; b2bEligibleCount: number };
    canAssignTenantCatalog: boolean;
};

function shopEmptyMessage(
    filtersActive: boolean,
    meta: { assignedActiveCount: number; b2bEligibleCount: number },
): string {
    if (filtersActive) {
        return 'No products match your filters. Try clearing search, category, or currency.';
    }
    if (meta.assignedActiveCount === 0) {
        return 'No products are assigned to your company yet. If you have access to Company catalog in the sidebar, you can choose B2B-eligible products yourself; otherwise ask a platform administrator (Tenants / B2B).';
    }
    if (meta.b2bEligibleCount === 0) {
        return "Products are assigned, but none are B2B-eligible. Set each product's catalog audience to Business or Both, then assign them again.";
    }
    return 'No products to show right now. Clear filters or check logs for b2b_catalog.serialize_failed.';
}

export default function Shop({
    tenant,
    categories,
    products,
    currencyOptions,
    filters,
    walletBalance,
    catalogMeta,
    canAssignTenantCatalog,
}: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [currency, setCurrency] = useState(filters.currency ?? '');
    const [categoryId, setCategoryId] = useState<number | null>(filters.category_id ?? null);
    const [orderProduct, setOrderProduct] = useState<ProductRow | null>(null);

    const form = useForm({
        product_id: 0,
        quantity: 1,
        denomination: 0,
        payment_method: 'wallet',
        offer_code: '' as string | null,
    });

    const applyFilters = (next?: { category?: number | null }) => {
        const cat = next?.category !== undefined ? next.category : categoryId;
        router.get(
            '/panel/b2b/shop',
            {
                q: q || undefined,
                currency: currency || undefined,
                category_id: cat ?? undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const openOrder = (p: ProductRow) => {
        setOrderProduct(p);
        form.setData({
            product_id: p.id,
            quantity: 1,
            denomination: p.representative_denomination,
            payment_method: 'wallet',
            offer_code: '',
        });
    };

    const submitOrder = (e: FormEvent) => {
        e.preventDefault();
        form.post('/panel/b2b/orders', {
            preserveScroll: true,
            onSuccess: () => setOrderProduct(null),
        });
    };

    const filtersActive =
        Boolean((filters.q ?? '').trim()) ||
        filters.category_id != null ||
        Boolean((filters.currency ?? '').trim());

    return (
        <AdminLayout>
            <Head title="Shop" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Shop</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Browse products available to your company. Prices use your B2B margin (sample totals shown for
                            qty 1 at the minimum denomination).
                        </p>
                    </div>
                    <div className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <span className="text-gray-500 dark:text-gray-400">Wallet: </span>
                        <span className="font-semibold text-gray-900 dark:text-white">
                            ₹{walletBalance.toLocaleString('en-IN', { minimumFractionDigits: 2 })}
                        </span>
                    </div>
                </div>

                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        No tenant linked to this user.
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-end">
                            <div className="min-w-0 flex-1">
                                <CategoryNav
                                    variant="panel"
                                    categories={categories}
                                    selectedCategoryId={categoryId}
                                    onSelectCategory={(id) => {
                                        setCategoryId(id);
                                        applyFilters({ category: id });
                                    }}
                                />
                            </div>
                            <div className="flex flex-shrink-0 flex-wrap items-center gap-2">
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                                    <input
                                        value={q}
                                        onChange={(e) => setQ(e.target.value)}
                                        onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                        placeholder="Search name or SKU"
                                        className="w-56 rounded-lg border border-gray-300 py-2 pl-8 pr-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                                {currencyOptions.length > 0 && (
                                    <select
                                        value={currency}
                                        onChange={(e) => setCurrency(e.target.value)}
                                        className="rounded-lg border border-gray-300 py-2 px-3 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    >
                                        <option value="">All currencies</option>
                                        {currencyOptions.map((c) => (
                                            <option key={c} value={c}>
                                                {c}
                                            </option>
                                        ))}
                                    </select>
                                )}
                                <button
                                    type="button"
                                    onClick={() => applyFilters()}
                                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Apply
                                </button>
                            </div>
                        </div>

                        {products.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-600 dark:border-gray-600 dark:bg-gray-800/50 dark:text-gray-300">
                                <p className="max-w-lg mx-auto text-sm leading-relaxed">{shopEmptyMessage(filtersActive, catalogMeta)}</p>
                                {tenant &&
                                    canAssignTenantCatalog &&
                                    !filtersActive &&
                                    catalogMeta.b2bEligibleCount === 0 && (
                                        <p className="mt-4">
                                            <Link
                                                href="/panel/b2b/catalog"
                                                className="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                                            >
                                                Open Company catalog to choose products
                                            </Link>
                                        </p>
                                    )}
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">
                                {products.map((p) => (
                                    <div
                                        key={p.id}
                                        className="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div className="flex h-24 items-center justify-center bg-gray-50 px-2 dark:bg-gray-900/50 sm:h-28">
                                            {p.image_url ? (
                                                <img src={p.image_url} alt="" className="max-h-[4.5rem] max-w-[92%] object-contain" />
                                            ) : (
                                                <ShoppingCart className="h-8 w-8 text-gray-300" />
                                            )}
                                        </div>
                                        <div className="flex flex-1 flex-col p-2.5 sm:p-3">
                                            <p className="line-clamp-2 text-xs font-medium leading-snug text-gray-900 dark:text-white sm:text-sm">
                                                {p.name}
                                            </p>
                                            <p className="mt-0.5 text-[11px] text-gray-500 dark:text-gray-400 sm:text-xs">{p.price_range_label}</p>
                                            <p className="mt-1.5 text-[10px] leading-tight text-gray-500 dark:text-gray-400 sm:text-xs">
                                                Sample (×1): {p.currency_code}{' '}
                                                {Number(p.sample_grand_total).toLocaleString('en-IN', {
                                                    minimumFractionDigits: 2,
                                                })}{' '}
                                                <span className="text-emerald-600 dark:text-emerald-400">
                                                    (-{Number(p.discount_percentage).toFixed(2)}%)
                                                </span>
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() => openOrder(p)}
                                                className="mt-2 w-full rounded-md bg-indigo-600 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 sm:py-2 sm:text-sm"
                                            >
                                                Order
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>

            {orderProduct && (
                <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/40 p-4 sm:items-center">
                    <div className="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <div className="mb-4 flex items-start justify-between gap-2">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Place order</h2>
                                <p className="text-sm text-gray-500 dark:text-gray-400">{orderProduct.name}</p>
                            </div>
                            <button
                                type="button"
                                onClick={() => setOrderProduct(null)}
                                className="rounded-md p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700"
                                aria-label="Close"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <form onSubmit={submitOrder} className="space-y-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Denomination (face value)
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min={1}
                                    required
                                    value={form.data.denomination}
                                    onChange={(e) => form.setData('denomination', parseFloat(e.target.value) || 0)}
                                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300">Quantity</label>
                                <input
                                    type="number"
                                    min={1}
                                    max={50}
                                    required
                                    value={form.data.quantity}
                                    onChange={(e) => form.setData('quantity', parseInt(e.target.value, 10) || 1)}
                                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Offer code (optional)
                                </label>
                                <input
                                    value={form.data.offer_code ?? ''}
                                    onChange={(e) => form.setData('offer_code', e.target.value || null)}
                                    className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            {form.errors.order && (
                                <p className="text-sm text-red-600 dark:text-red-400">{form.errors.order}</p>
                            )}
                            <div className="flex gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setOrderProduct(null)}
                                    className="flex-1 rounded-lg border border-gray-300 py-2 text-sm font-medium text-gray-700 dark:border-gray-600 dark:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="flex-1 rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {form.processing ? 'Placing…' : 'Pay with wallet'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
