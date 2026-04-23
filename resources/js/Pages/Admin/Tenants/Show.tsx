import React, { useMemo, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type Tenant = {
    id: number;
    name: string;
    slug: string;
    contact_email: string;
    margin_percentage: string | number | null;
    wallet_limit: string | number | null;
    users_count?: number;
    orders_count?: number;
};

type CatalogRow = { id: number; name: string; sku: string | null };

type Props = {
    tenant: Tenant;
    catalogProducts: CatalogRow[];
    assignedProductIds: number[];
    canAssignProducts: boolean;
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
            <div className="max-h-64 overflow-y-auto space-y-1 text-sm border dark:border-gray-600 rounded-md p-2">
                {filtered.length === 0 ? (
                    <p className="text-gray-500 text-xs">
                        No B2B-eligible products (Business or Both). Create or retag products first.
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
            <p className="text-xs text-gray-500 dark:text-gray-400">{selected.length} selected for this tenant</p>
        </div>
    );
}

export default function Show({ tenant, catalogProducts, assignedProductIds, canAssignProducts }: Props) {
    const form = useForm({
        name: tenant.name,
        contact_email: tenant.contact_email,
        margin_percentage: tenant.margin_percentage != null ? String(tenant.margin_percentage) : '',
        wallet_limit: tenant.wallet_limit != null ? String(tenant.wallet_limit) : '',
    });

    const catalogForm = useForm({
        product_ids: [...assignedProductIds],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/panel/tenants/${tenant.id}`);
    };

    const submitCatalog = (e: React.FormEvent) => {
        e.preventDefault();
        catalogForm.post(`/panel/tenants/${tenant.id}/products`, { preserveScroll: true });
    };

    return (
        <AdminLayout>
            <Head title={tenant.name} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs items={[{ label: 'Tenants', href: '/panel/tenants' }, { label: tenant.name }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/tenants" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{tenant.name}</h1>
                </div>
                <p className="text-sm text-gray-500">
                    Slug: <code className="font-mono">{tenant.slug}</code>
                    {tenant.users_count != null && ` · ${tenant.users_count} users`}
                    {tenant.orders_count != null && ` · ${tenant.orders_count} orders`}
                </p>
                <form onSubmit={submit} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Name</label>
                        <input
                            value={form.data.name}
                            onChange={e => form.setData('name', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Contact email</label>
                        <input
                            type="email"
                            value={form.data.contact_email}
                            onChange={e => form.setData('contact_email', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Margin %</label>
                        <input
                            value={form.data.margin_percentage}
                            onChange={e => form.setData('margin_percentage', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Wallet limit</label>
                        <input
                            value={form.data.wallet_limit}
                            onChange={e => form.setData('wallet_limit', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                    </div>
                    <div className="flex gap-3">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                        >
                            Save
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                if (confirm('Suspend this tenant?')) {
                                    router.post(`/panel/tenants/${tenant.id}/suspend`);
                                }
                            }}
                            className="px-4 py-2 bg-amber-600 text-white rounded-lg text-sm"
                        >
                            Suspend
                        </button>
                    </div>
                </form>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3">
                    <div>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">B2B catalog for this tenant</h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            B2B-eligible SKUs only (catalog audience Business or Both) for this tenant&apos;s shop and price
                            list (<code className="text-xs">/panel/b2b/shop</code>).
                        </p>
                    </div>
                    {!canAssignProducts ? (
                        <p className="text-sm text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                            You do not have permission to assign products. An administrator with{' '}
                            <span className="font-mono text-xs">tenants.assign_products</span> must update this catalog.
                        </p>
                    ) : (
                        <form onSubmit={submitCatalog} className="space-y-3">
                            <CatalogPicker
                                items={catalogProducts}
                                selected={catalogForm.data.product_ids}
                                onChange={ids => catalogForm.setData('product_ids', ids)}
                            />
                            {catalogForm.errors.product_ids && (
                                <p className="text-sm text-red-600">{catalogForm.errors.product_ids}</p>
                            )}
                            <button
                                type="submit"
                                disabled={catalogForm.processing}
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50"
                            >
                                Save catalog
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
