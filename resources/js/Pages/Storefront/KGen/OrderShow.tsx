import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function KGenOrderShow({ order }: { order: Record<string, unknown> }) {
    return (
        <StorefrontLayout>
            <Head title="KGen order" />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <Link href="/get-kgenorders" className="text-sm text-brand-600 hover:underline">
                    Back to API order lookup
                </Link>
                <h1 className="mt-4 text-2xl font-bold text-gray-900">Order detail</h1>
                <pre className="mt-6 max-h-[480px] overflow-auto rounded-xl border border-gray-200 bg-gray-50 p-4 text-xs">
                    {JSON.stringify(order, null, 2)}
                </pre>
            </div>
        </StorefrontLayout>
    );
}
