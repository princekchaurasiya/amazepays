import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function OrderDetail({
    cardArray = [],
    productImage,
    order,
}: {
    cardArray: Record<string, unknown>[];
    productImage?: string | null;
    order: Record<string, unknown>;
}) {
    return (
        <StorefrontLayout>
            <Head title="Card details" />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <Link href={paths.myOrders} className="text-sm text-brand-600 hover:underline">
                    ← Back to orders
                </Link>
                <h1 className="mt-4 text-2xl font-bold text-gray-900">Gift card details</h1>
                {productImage && <img src={productImage} alt="" className="mt-4 h-32 rounded-lg object-cover" />}
                <p className="mt-2 text-sm text-gray-600">Order ref: {String(order.refno ?? order.woohoo_order_id ?? '')}</p>
                <div className="mt-6 space-y-4">
                    {cardArray.map((c, i) => (
                        <div key={i} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                            <dl className="grid gap-2 text-sm">
                                {Object.entries(c).map(([k, v]) => (
                                    <div key={k} className="flex justify-between gap-4">
                                        <dt className="text-gray-500">{k}</dt>
                                        <dd className="font-mono text-gray-900">{String(v ?? '—')}</dd>
                                    </div>
                                ))}
                            </dl>
                        </div>
                    ))}
                </div>
            </div>
        </StorefrontLayout>
    );
}
