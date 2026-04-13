import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type OrderRow = Record<string, unknown>;

export default function KGenIndex({
    configured,
    recentOrders,
    stats,
}: {
    configured: boolean;
    recentOrders: OrderRow[];
    stats: { total_orders: number };
}) {
    return (
        <AdminLayout>
            <Head title="KGen" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900">KGen / EXLR8</h1>
                <p className="mt-2 text-sm text-gray-600">
                    Status:{' '}
                    <span className={configured ? 'font-medium text-emerald-700' : 'font-medium text-amber-700'}>
                        {configured ? 'Core credentials present' : 'Missing EXLR8_BASE_URL / credentials / dpID'}
                    </span>
                </p>
                <p className="mt-4 text-sm text-gray-600">Total tracked orders: {stats.total_orders}</p>

                <div className="mt-6 flex flex-wrap gap-3">
                    <a
                        href="/kgen-products"
                        target="_blank"
                        rel="noreferrer"
                        className="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700"
                    >
                        Public catalog
                    </a>
                    <Link
                        href="/panel/providers"
                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        All providers
                    </Link>
                </div>

                <h2 className="mt-10 text-lg font-semibold text-gray-900">Recent orders</h2>
                <div className="mt-4 space-y-2">
                    {recentOrders.length === 0 ? (
                        <p className="text-sm text-gray-500">No KGen orders yet.</p>
                    ) : (
                        recentOrders.map((o, i) => (
                            <pre key={i} className="overflow-auto rounded-lg bg-gray-50 p-3 text-xs dark:bg-gray-900">
                                {JSON.stringify(o, null, 2)}
                            </pre>
                        ))
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
