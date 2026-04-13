import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { ShoppingCart, Search } from 'lucide-react';

type OrderRow = {
    id: number;
    order_number: string | null;
    status: string | null;
    grand_total: string | number | null;
    created_at: string | null;
    user?: { email?: string; name?: string };
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    orders: Paginated<OrderRow>;
    filters: { search?: string; status?: string };
};

export default function Index({ orders, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [status, setStatus] = useState(filters.status ?? '');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/orders', { search: search || undefined, status: status || undefined }, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Orders" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Orders' }]} />
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <ShoppingCart className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Orders</h1>
                    </div>
                </div>
                <form onSubmit={applyFilters} className="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-xs text-gray-500 mb-1">Search</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                placeholder="Order # or email"
                                className="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>
                    <div className="w-40">
                        <label className="block text-xs text-gray-500 mb-1">Status</label>
                        <input
                            value={status}
                            onChange={e => setStatus(e.target.value)}
                            placeholder="e.g. pending"
                            className="w-full px-3 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700"
                        />
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Order #</th>
                                <th className="px-5 py-3">Customer</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Total</th>
                                <th className="px-5 py-3">Date</th>
                                <th className="px-5 py-3 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {orders.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-5 py-12 text-center text-gray-500">No orders found.</td>
                                </tr>
                            ) : (
                                orders.data.map(row => (
                                    <tr key={row.id} className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3 font-mono text-xs">{row.order_number || row.id}</td>
                                        <td className="px-5 py-3">{row.user?.email || row.user?.name || '—'}</td>
                                        <td className="px-5 py-3 capitalize">{row.status || '—'}</td>
                                        <td className="px-5 py-3">
                                            {row.grand_total != null && row.grand_total !== ''
                                                ? `₹${Number(row.grand_total).toLocaleString('en-IN')}`
                                                : '—'}
                                        </td>
                                        <td className="px-5 py-3 text-gray-500">{row.created_at ? new Date(row.created_at).toLocaleString() : '—'}</td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons viewHref={`/panel/orders/${row.id}`} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {orders.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500">
                            <span>
                                {orders.from}–{orders.to} of {orders.total}
                            </span>
                            <div className="flex gap-2">
                                {orders.current_page > 1 && (
                                    <Link
                                        href={`/panel/orders?page=${orders.current_page - 1}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {orders.current_page < orders.last_page && (
                                    <Link
                                        href={`/panel/orders?page=${orders.current_page + 1}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
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
