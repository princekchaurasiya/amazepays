import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AdminExportButton from '@/Components/Admin/AdminExportButton';
import { Search } from 'lucide-react';

type Category = { id: number; name: string; slug: string };
type Row = {
    id: number;
    name: string;
    sku: string | null;
    currency_code: string;
    price_range_label: string;
    representative_denomination: number;
    discount_percentage: number;
    discount_source: string | null;
    sample_grand_total: number;
};

type Props = {
    tenant: { id: number; name: string } | null;
    categories: Category[];
    rows: Row[];
    currencyOptions: string[];
    filters: { q: string; category_id: number | null; currency: string | null };
};

export default function PriceList({ tenant, categories, rows, currencyOptions, filters }: Props) {
    const [q, setQ] = useState(filters.q ?? '');
    const [currency, setCurrency] = useState(filters.currency ?? '');
    const [categoryId, setCategoryId] = useState<number | null>(filters.category_id ?? null);

    const queryParams = () => ({
        q: q || undefined,
        currency: currency || undefined,
        category_id: categoryId ?? undefined,
    });

    const search = () => {
        router.get('/panel/b2b/price-list', queryParams(), { preserveState: true, replace: true });
    };

    const exportUrl = () => {
        const p = new URLSearchParams();
        if (q) p.set('q', q);
        if (currency) p.set('currency', currency);
        if (categoryId != null) p.set('category_id', String(categoryId));
        const qs = p.toString();
        return `/panel/b2b/price-list/export${qs ? `?${qs}` : ''}`;
    };

    return (
        <AdminLayout>
            <Head title="Price list" />
            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Price list</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Buying rates for your assigned catalog (indicative totals use qty 1 at minimum denomination).
                        </p>
                    </div>
                    {tenant && (
                        <AdminExportButton
                            href={exportUrl()}
                            label="Download Excel"
                            requiredPermission="b2b.price_list.view"
                        />
                    )}
                </div>

                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        No tenant linked to this user.
                    </div>
                ) : (
                    <>
                        <div className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div className="relative min-w-[200px] flex-1">
                                <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-gray-400" />
                                <input
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && search()}
                                    placeholder="Search"
                                    className="w-full rounded-lg border border-gray-300 py-2 pl-8 pr-3 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                            <select
                                value={categoryId ?? ''}
                                onChange={(e) =>
                                    setCategoryId(e.target.value ? parseInt(e.target.value, 10) : null)
                                }
                                className="rounded-lg border border-gray-300 py-2 px-3 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">All categories</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            {currencyOptions.length > 0 && (
                                <select
                                    value={currency}
                                    onChange={(e) => setCurrency(e.target.value)}
                                    className="rounded-lg border border-gray-300 py-2 px-3 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white"
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
                                onClick={search}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                            >
                                Search
                            </button>
                        </div>

                        <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-gray-100 bg-gray-50 text-left text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                        <th className="px-4 py-3 font-medium">SKU</th>
                                        <th className="px-4 py-3 font-medium">Product</th>
                                        <th className="px-4 py-3 font-medium">Currency</th>
                                        <th className="px-4 py-3 font-medium">Range</th>
                                        <th className="px-4 py-3 font-medium">Discount</th>
                                        <th className="px-4 py-3 font-medium">Sample total (×1)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map((r) => (
                                        <tr key={r.id} className="border-t border-gray-100 dark:border-gray-700">
                                            <td className="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                                                {r.sku ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-gray-900 dark:text-white">{r.name}</td>
                                            <td className="px-4 py-3">{r.currency_code}</td>
                                            <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{r.price_range_label}</td>
                                            <td className="px-4 py-3 text-emerald-600 dark:text-emerald-400">
                                                −{Number(r.discount_percentage).toFixed(2)}%
                                                <span className="ml-1 text-xs text-gray-400">({r.discount_source})</span>
                                            </td>
                                            <td className="px-4 py-3">
                                                {r.currency_code}{' '}
                                                {Number(r.sample_grand_total).toLocaleString('en-IN', {
                                                    minimumFractionDigits: 2,
                                                })}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {rows.length === 0 && (
                                <p className="p-8 text-center text-gray-500 dark:text-gray-400">No rows to show.</p>
                            )}
                        </div>
                    </>
                )}
            </div>
        </AdminLayout>
    );
}
