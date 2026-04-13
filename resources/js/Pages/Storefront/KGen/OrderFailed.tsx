import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function KGenOrderFailed({ order }: { order: Record<string, unknown> }) {
    return (
        <StorefrontLayout>
            <Head title="Order failed" />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-xl font-bold text-red-700">
                    {'\u2717'}
                </div>
                <h1 className="mt-4 text-xl font-bold text-gray-900">Order could not be completed</h1>
                <p className="mt-2 text-sm text-gray-600">Reference: {String(order.external_ref ?? order.id ?? '—')}</p>
                <Link href={paths.kgenProducts} className="mt-8 inline-block text-sm font-medium text-brand-600 hover:underline">
                    Try again
                </Link>
            </div>
        </StorefrontLayout>
    );
}
