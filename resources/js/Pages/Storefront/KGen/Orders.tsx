import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type Row = Record<string, unknown>;

type Paginated = {
    data: Row[];
    links?: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page?: number; last_page?: number };
};

export default function KGenOrders({ orders }: { orders: Paginated }) {
    const rows = orders?.data ?? [];

    return (
        <StorefrontLayout>
            <Head title="KGen orders" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">Your KGen orders</h1>
                <div className="mt-6 space-y-3">
                    {rows.length === 0 ? (
                        <p className="text-gray-600">No orders yet.</p>
                    ) : (
                        rows.map((o, i) => (
                            <div key={String(o.id ?? i)} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                <p className="font-medium text-gray-900">Ref: {String(o.external_ref ?? o.externalRef ?? '—')}</p>
                                <p className="text-sm text-gray-600">Status: {String(o.status ?? '—')}</p>
                                <p className="text-sm text-gray-600">
                                    Amount: &#8377;{Number(o.payable_amount ?? o.mrp ?? 0).toLocaleString('en-IN')}
                                </p>
                            </div>
                        ))
                    )}
                </div>
                {orders?.links && orders.links.length > 3 ? (
                    <nav className="mt-8 flex flex-wrap gap-2">
                        {orders.links.map((l, idx) =>
                            l.url ? (
                                <Link
                                    key={idx}
                                    href={l.url}
                                    className={`rounded-lg px-3 py-1 text-sm ${
                                        l.active ? 'bg-gray-900 text-white' : 'border border-gray-200 bg-white text-gray-700'
                                    }`}
                                >
                                    <span dangerouslySetInnerHTML={{ __html: l.label }} />
                                </Link>
                            ) : (
                                <span
                                    key={idx}
                                    className="rounded-lg px-3 py-1 text-sm text-gray-400"
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ),
                        )}
                    </nav>
                ) : null}
                <p className="mt-6 text-sm">
                    <Link href={paths.kgenProducts} className="text-brand-600 hover:underline">
                        Catalog
                    </Link>
                </p>
            </div>
        </StorefrontLayout>
    );
}
