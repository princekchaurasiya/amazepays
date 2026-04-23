import React, { useMemo, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type CatalogRow = { id: number; name: string; sku: string | null };

type Props = {
    tenant: { id: number; name: string; slug?: string } | null;
    catalogProducts: CatalogRow[];
    assignedProductIds: number[];
    canManage: boolean;
};

function CatalogPicker({
    items,
    selected,
    onChange,
}: {
    items: CatalogRow[];
    selected: number[];
    onChange: (ids: number[]) => void;
}) {
    const [q, setQ] = useState('');
    const filtered = useMemo(() => {
        const s = q.trim().toLowerCase();
        if (!s) return items;
        return items.filter(
            i =>
                i.name.toLowerCase().includes(s) ||
                (i.sku || '').toLowerCase().includes(s) ||
                String(i.id).includes(s),
        );
    }, [items, q]);

    const toggle = (id: number) => {
        if (selected.includes(id)) {
            onChange(selected.filter(x => x !== id));
        } else {
            onChange([...selected, id]);
        }
    };

    return (
        <div className="border dark:border-gray-600 rounded-lg p-4 space-y-2">
            <input
                value={q}
                onChange={e => setQ(e.target.value)}
                placeholder="Search name or SKU…"
                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 dark:border-gray-600"
            />
            <div className="max-h-72 overflow-y-auto space-y-1 text-sm border dark:border-gray-600 rounded-md p-2">
                {filtered.length === 0 ? (
                    <p className="text-gray-500 text-xs">
                        No B2B-eligible products in the platform catalog (audience Business or Both). Admins can add or retag products, then refresh this page.
                    </p>
                ) : (
                    filtered.map(row => (
                        <label
                            key={row.id}
                            className="flex items-center gap-2 py-1 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1"
                        >
                            <input type="checkbox" checked={selected.includes(row.id)} onChange={() => toggle(row.id)} />
                            <span className="flex-1 truncate">
                                {row.name}
                                {row.sku && <span className="text-gray-400 font-mono text-xs ml-1">({row.sku})</span>}
                            </span>
                        </label>
                    ))
                )}
            </div>
            <p className="text-xs text-gray-500 dark:text-gray-400">{selected.length} selected</p>
        </div>
    );
}

export default function CatalogManage({ tenant, catalogProducts, assignedProductIds, canManage }: Props) {
    const form = useForm({
        product_ids: [...assignedProductIds],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/panel/b2b/catalog', { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title="Company catalog" />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs
                    items={[
                        { label: 'Shop', href: '/panel/b2b/shop' },
                        { label: 'Company catalog' },
                    ]}
                />
                <div className="flex items-center gap-4">
                    <Link href="/panel/b2b/shop" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Company catalog</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Choose B2B-eligible SKUs (catalog audience Business or Both) for{' '}
                            {tenant ? <strong>{tenant.name}</strong> : 'your company'} shop and price list.
                        </p>
                    </div>
                </div>

                {!canManage || !tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        {!tenant
                            ? 'No company (tenant) is linked to your account, or you are not a member of the resolved company.'
                            : 'You cannot manage this catalog for the current company.'}
                    </div>
                ) : (
                    <form onSubmit={submit} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-6 space-y-4">
                        <CatalogPicker
                            items={catalogProducts}
                            selected={form.data.product_ids}
                            onChange={ids => form.setData('product_ids', ids)}
                        />
                        {form.errors.product_ids && <p className="text-sm text-red-600 dark:text-red-400">{form.errors.product_ids}</p>}
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                        >
                            Save company catalog
                        </button>
                    </form>
                )}
            </div>
        </AdminLayout>
    );
}
