import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

export default function Create() {
    const form = useForm({
        name: '',
        slug: '',
        contact_email: '',
        margin_percentage: '',
        wallet_limit: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/panel/tenants');
    };

    return (
        <AdminLayout>
            <Head title="New tenant" />
            <div className="space-y-6 max-w-2xl">
                <Breadcrumbs items={[{ label: 'Tenants', href: '/panel/tenants' }, { label: 'Create' }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/tenants" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">New tenant</h1>
                </div>
                <form onSubmit={submit} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Name</label>
                        <input
                            value={form.data.name}
                            onChange={e => form.setData('name', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            required
                        />
                        {form.errors.name && <p className="text-red-500 text-xs mt-1">{form.errors.name}</p>}
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Slug (unique)</label>
                        <input
                            value={form.data.slug}
                            onChange={e => form.setData('slug', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 font-mono"
                            required
                        />
                        {form.errors.slug && <p className="text-red-500 text-xs mt-1">{form.errors.slug}</p>}
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Contact email</label>
                        <input
                            type="email"
                            value={form.data.contact_email}
                            onChange={e => form.setData('contact_email', e.target.value)}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            required
                        />
                        {form.errors.contact_email && <p className="text-red-500 text-xs mt-1">{form.errors.contact_email}</p>}
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
                    <button type="submit" disabled={form.processing} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-50">
                        Create tenant
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
