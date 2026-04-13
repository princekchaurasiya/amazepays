import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function KGenOrderSuccess({ order }: { order: Record<string, unknown> }) {
    return (
        <StorefrontLayout>
            <Head title="Order success" />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-xl font-bold text-emerald-700">
                    {'\u2713'}
                </div>
                <h1 className="mt-4 text-xl font-bold text-gray-900">Order placed</h1>
                <p className="mt-2 text-sm text-gray-600">Reference: {String(order.external_ref ?? '—')}</p>
                <Link href={paths.kgenOrders} className="mt-8 inline-block text-sm font-medium text-brand-600 hover:underline">
                    View orders
                </Link>
            </div>
        </StorefrontLayout>
    );
}
