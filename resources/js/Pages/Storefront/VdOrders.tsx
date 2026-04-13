import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type Row = Record<string, unknown>;

export default function VdOrders({ orders = [] }: { orders: Row[] }) {
    return (
        <StorefrontLayout>
            <Head title="Value Design orders" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <nav className="mb-6 flex gap-4 text-sm text-gray-600">
                    <Link href={paths.profile} className="hover:text-brand-600">
                        Profile
                    </Link>
                    <Link href={paths.myOrders} className="hover:text-brand-600">
                        Gift card orders
                    </Link>
                    <span className="font-medium text-gray-900">Value Design</span>
                </nav>
                <h1 className="text-2xl font-bold text-gray-900">Value Design requests</h1>
                <div className="mt-6 space-y-4">
                    {orders.length === 0 ? (
                        <p className="text-gray-600">No records found.</p>
                    ) : (
                        orders.map((o, i) => (
                            <div key={String(o.order_id ?? o.req_id ?? i)} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <p className="font-semibold text-gray-900">Order {String(o.order_id ?? '—')}</p>
                                <p className="text-sm text-gray-600">SKU: {String(o.sku_code ?? '—')}</p>
                                <p className="text-sm text-gray-600">Amount: &#8377;{Number(o.amount ?? 0).toLocaleString('en-IN')}</p>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </StorefrontLayout>
    );
}
