import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type BrandOpt = { code: string; name: string };
type StoreRow = Record<string, unknown>;

export default function StoresWorkspace({
    mode,
    brands = [],
    stores = [],
    brandCode,
    pagination,
    filters = {},
    brandcodes = [],
    brandnames = [],
    countries = [],
    states = [],
    cities = [],
    errorFlash,
    allBrands = [],
}: {
    mode: 'form' | 'list_fetch' | 'list_filter' | 'brand_pick';
    brands?: BrandOpt[];
    stores?: StoreRow[] | { data: StoreRow[]; links?: unknown[]; current_page?: number };
    brandCode?: unknown;
    pagination?: { current_page?: number; last_page?: number; total?: number };
    filters?: Record<string, string>;
    /** For brand_pick: id + name from Brand::all() */
    allBrands?: { id: number; name: string; brand_code?: string }[];
    brandcodes?: string[];
    brandnames?: string[];
    countries?: string[];
    states?: string[];
    cities?: string[];
    errorFlash?: string | null;
}) {
    const storeList: StoreRow[] = Array.isArray(stores)
        ? stores
        : Array.isArray((stores as { data?: StoreRow[] })?.data)
          ? (stores as { data: StoreRow[] }).data
          : [];

    const filterForm = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const fd = new FormData(e.currentTarget);
        const q = Object.fromEntries(fd.entries()) as Record<string, string>;
        router.get('/panel/value-design/stores/filter', q, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Stores" />
            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <h1 className="text-2xl font-bold text-gray-900">VD stores</h1>
                    <Link href="/panel/value-design/stores/select" className="text-sm font-medium text-indigo-600 hover:underline">
                        Select brand / fetch
                    </Link>
                </div>
                {errorFlash ? (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{errorFlash}</div>
                ) : null}

                {mode === 'brand_pick' ? (
                    <ul className="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
                        {allBrands.map((b) => (
                            <li key={b.id} className="px-4 py-3 text-sm">
                                {b.name}{' '}
                                <span className="text-gray-500">({b.brand_code ?? '—'})</span>
                            </li>
                        ))}
                    </ul>
                ) : null}

                {mode === 'form' ? (
                    <form
                        action="/panel/value-design/stores/fetch"
                        method="post"
                        className="max-w-md space-y-4 rounded-lg border border-gray-200 bg-white p-6"
                    >
                        <input type="hidden" name="_token" value={document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? ''} />
                        <label className="block text-sm font-medium text-gray-700">Brand code</label>
                        <select name="brand_code" required className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Choose…</option>
                            {brands.map((b) => (
                                <option key={b.code} value={b.code}>
                                    {b.name} ({b.code})
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Fetch stores from API
                        </button>
                    </form>
                ) : null}

                {mode === 'list_filter' ? (
                    <form onSubmit={filterForm} className="grid gap-3 rounded-lg border border-gray-200 bg-white p-4 md:grid-cols-3">
                        <select name="brand_code" defaultValue={filters.brand_code ?? ''} className="rounded-md border px-2 py-1 text-sm">
                            <option value="">Brand code</option>
                            {brandcodes.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                        <select name="brand_name" defaultValue={filters.brand_name ?? ''} className="rounded-md border px-2 py-1 text-sm">
                            <option value="">Brand name</option>
                            {brandnames.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                        <select name="country" defaultValue={filters.country ?? ''} className="rounded-md border px-2 py-1 text-sm">
                            <option value="">Country</option>
                            {countries.map((c) => (
                                <option key={c} value={c}>
                                    {c}
                                </option>
                            ))}
                        </select>
                        <button type="submit" className="rounded-md bg-gray-900 px-3 py-1 text-sm text-white md:col-span-3">
                            Apply filters
                        </button>
                    </form>
                ) : null}

                <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-2 py-2 text-left">Store</th>
                                <th className="px-2 py-2 text-left">Brand</th>
                                <th className="px-2 py-2 text-left">City</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {storeList.map((s, i) => (
                                <tr key={i}>
                                    <td className="px-2 py-2">{(s.store_name as string) ?? '—'}</td>
                                    <td className="px-2 py-2">{(s.brand_name as string) ?? (s.brand_code as string) ?? '—'}</td>
                                    <td className="px-2 py-2">{(s.city as string) ?? '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                {pagination ? (
                    <p className="text-xs text-gray-500">
                        Page {pagination.current_page ?? 1} / {pagination.last_page ?? 1} — {pagination.total ?? storeList.length}{' '}
                        rows
                    </p>
                ) : null}
                {mode === 'list_fetch' && brandCode != null ? (
                    <p className="text-xs text-gray-500">Fetched for brand codes: {JSON.stringify(brandCode)}</p>
                ) : null}
            </div>
        </AdminLayout>
    );
}
