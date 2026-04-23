import React, { FormEvent, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { format } from 'date-fns';
import { StatusBadge } from '@/Components/Admin';

type OrderRow = {
    id: number;
    user_id: number | null;
    order_status: string;
    grand_payable_amount: string | number;
    created_at: string;
    order_number?: string | null;
    user?: { name?: string; email?: string } | null;
};

type Props = {
    tenant: { id: number; name: string } | null;
    orders: OrderRow[];
    filters?: { date_from?: string; date_to?: string };
};

export default function Orders({ tenant, orders, filters = {} }: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from ?? '');
    const [dateTo, setDateTo] = useState(filters.date_to ?? '');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/panel/b2b/orders',
            { date_from: dateFrom || undefined, date_to: dateTo || undefined },
            { preserveState: true },
        );
    };

    return (
        <AdminLayout>
            <Head title="My orders" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">My orders</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Orders placed under your B2B tenant account.
                    </p>
                </div>
                {tenant && (
                    <form
                        onSubmit={applyFilters}
                        className="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
                    >
                        <div>
                            <label className="mb-1 block text-xs text-gray-500 dark:text-gray-400">From</label>
                            <input
                                type="date"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                                className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <div>
                            <label className="mb-1 block text-xs text-gray-500 dark:text-gray-400">To</label>
                            <input
                                type="date"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                                className="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            />
                        </div>
                        <button
                            type="submit"
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        >
                            Filter
                        </button>
                    </form>
                )}
                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        No tenant linked to this user.
                    </div>
                ) : orders.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-10 text-center text-gray-500 dark:border-gray-600 dark:bg-gray-800/50 dark:text-gray-400">
                        No orders yet for {tenant.name}.
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-100 bg-gray-50 text-left text-gray-500 dark:border-gray-700 dark:bg-gray-700/50 dark:text-gray-400">
                                    <th className="px-5 py-3 font-medium">Order</th>
                                    <th className="px-5 py-3 font-medium">Customer</th>
                                    <th className="px-5 py-3 font-medium">Status</th>
                                    <th className="px-5 py-3 font-medium">Amount</th>
                                    <th className="px-5 py-3 font-medium">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.map((order) => (
                                    <tr
                                        key={order.id}
                                        className="border-t border-gray-100 dark:border-gray-700"
                                    >
                                        <td className="px-5 py-3 font-mono text-indigo-600">
                                            #{order.order_number ?? order.id}
                                        </td>
                                        <td className="px-5 py-3">{order.user?.name ?? '—'}</td>
                                        <td className="px-5 py-3">
                                            <StatusBadge status={order.order_status} />
                                        </td>
                                        <td className="px-5 py-3">
                                            ₹{Number(order.grand_payable_amount).toLocaleString('en-IN')}
                                        </td>
                                        <td className="px-5 py-3 text-gray-500">
                                            {format(new Date(order.created_at), 'dd MMM yyyy, HH:mm')}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
