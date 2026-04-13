import React from 'react';
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

type Props = {
    tenant: Tenant;
};

export default function Show({ tenant }: Props) {
    const form = useForm({
        name: tenant.name,
        contact_email: tenant.contact_email,
        margin_percentage: tenant.margin_percentage != null ? String(tenant.margin_percentage) : '',
        wallet_limit: tenant.wallet_limit != null ? String(tenant.wallet_limit) : '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/panel/tenants/${tenant.id}`);
    };

    return (
        <AdminLayout>
            <Head title={tenant.name} />
            <div className="space-y-6 max-w-2xl">
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
                        <button type="submit" disabled={form.processing} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
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
            </div>
        </AdminLayout>
    );
}
