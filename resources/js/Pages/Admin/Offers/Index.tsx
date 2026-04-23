import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { Tag, Plus, Search } from 'lucide-react';

type OfferRow = {
    id: number;
    name: string;
    code: string | null;
    type: string;
    is_active: boolean;
    discount_value?: string | number | null;
    discount_percentage?: string | number | null;
    tenant?: { name: string };
    applicable_product_ids?: number[] | null;
    applicable_brand_ids?: number[] | null;
    applicable_category_ids?: number[] | null;
};

function applicableSummary(row: OfferRow): string {
    const p = row.applicable_product_ids?.length ?? 0;
    const b = row.applicable_brand_ids?.length ?? 0;
    const c = row.applicable_category_ids?.length ?? 0;
    const parts: string[] = [];
    if (p) parts.push(`${p} product${p === 1 ? '' : 's'}`);
    if (b) parts.push(`${b} brand${b === 1 ? '' : 's'}`);
    if (c) parts.push(`${c} categor${c === 1 ? 'y' : 'ies'}`);
    return parts.length ? parts.join(', ') : 'All / none set';
}

function discountLabel(row: OfferRow): string {
    const v = row.discount_value != null && row.discount_value !== '' ? Number(row.discount_value) : null;
    const pct = row.discount_percentage != null && row.discount_percentage !== '' ? Number(row.discount_percentage) : null;
    if (v != null && v > 0) {
        return 'Rs.' + v;
    }
    if (pct != null && pct > 0) {
        return pct + '%';
    }
    return '—';
}

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    offers: Paginated<OfferRow>;
    filters: { search?: string; status?: string; type?: string };
    types: string[];
};

export default function Index({ offers, filters, types }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');
    const [type, setType] = useState(filters.type ?? '');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/offers', { search: search || undefined, status: status || undefined, type: type || undefined }, { preserveState: true });
    };

    const q = () =>
        `search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}&type=${encodeURIComponent(type)}`;

    return (
        <AdminLayout>
            <Head title="Offers" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Offers' }]} />
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Tag className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Offers</h1>
                    </div>
                    <Link
                        href="/panel/offers/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium"
                    >
                        <Plus size={18} />
                        New offer
                    </Link>
                </div>
                <form onSubmit={applyFilters} className="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                    <div className="flex-1 min-w-[180px]">
                        <label className="block text-xs text-gray-500 mb-1">Search</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                            />
                        </div>
                    </div>
                    <div className="w-36">
                        <label className="block text-xs text-gray-500 mb-1">Status</label>
                        <select
                            value={status}
                            onChange={e => setStatus(e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        >
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div className="w-48">
                        <label className="block text-xs text-gray-500 mb-1">Type</label>
                        <select
                            value={type}
                            onChange={e => setType(e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                        >
                            <option value="">All types</option>
                            {types.map(t => (
                                <option key={t} value={t}>{t}</option>
                            ))}
                        </select>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Name</th>
                                <th className="px-5 py-3">Code</th>
                                <th className="px-5 py-3">Type</th>
                                <th className="px-5 py-3">Discount</th>
                                <th className="px-5 py-3">Applicable</th>
                                <th className="px-5 py-3">Tenant</th>
                                <th className="px-5 py-3">Active</th>
                                <th className="px-5 py-3 w-36 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {offers.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-5 py-12 text-center text-gray-500">No offers found.</td>
                                </tr>
                            ) : (
                                offers.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3 font-medium">{row.name}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.code || '—'}</td>
                                        <td className="px-5 py-3 text-xs">{row.type}</td>
                                        <td className="px-5 py-3 text-xs font-medium">{discountLabel(row)}</td>
                                        <td className="px-5 py-3 text-xs text-gray-600 max-w-[200px] truncate" title={applicableSummary(row)}>
                                            {applicableSummary(row)}
                                        </td>
                                        <td className="px-5 py-3">{row.tenant?.name || '—'}</td>
                                        <td className="px-5 py-3">{row.is_active ? 'Yes' : 'No'}</td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons
                                                editHref={`/panel/offers/${row.id}/edit`}
                                                toggleOn={row.is_active}
                                                onToggle={() =>
                                                    router.patch(`/panel/offers/${row.id}/toggle`, {}, { preserveScroll: true })
                                                }
                                                toggleLabel={row.is_active ? 'Deactivate' : 'Activate'}
                                                toggleTitle={row.is_active ? 'Deactivate offer' : 'Activate offer'}
                                                onDelete={() =>
                                                    router.delete(`/panel/offers/${row.id}`, { preserveScroll: true })
                                                }
                                                deleteConfirmTitle="Delete this offer?"
                                                deleteConfirmMessage={`Remove “${row.name}” permanently? This cannot be undone.`}
                                            />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {offers.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>{offers.from}–{offers.to} of {offers.total}</span>
                            <div className="flex gap-2">
                                {offers.current_page > 1 && (
                                    <Link
                                        href={`/panel/offers?page=${offers.current_page - 1}&${q()}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {offers.current_page < offers.last_page && (
                                    <Link
                                        href={`/panel/offers?page=${offers.current_page + 1}&${q()}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
