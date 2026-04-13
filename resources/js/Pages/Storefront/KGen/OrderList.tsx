import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

export default function KGenOrderList({ orders }: { orders: Record<string, unknown>[] }) {
    return (
        <StorefrontLayout>
            <Head title="KGen API orders" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">Orders (API)</h1>
                <ul className="mt-6 space-y-3">
                    {orders.map((o, i) => (
                        <li key={i} className="rounded-lg border border-gray-200 bg-white p-3 text-sm">
                            <pre className="overflow-auto text-xs">{JSON.stringify(o, null, 2)}</pre>
                        </li>
                    ))}
                </ul>
                {orders.length === 0 ? <p className="text-gray-600">No orders returned.</p> : null}
            </div>
        </StorefrontLayout>
    );
}
