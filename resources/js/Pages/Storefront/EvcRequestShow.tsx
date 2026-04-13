import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

export default function EvcRequestShow({
    evcRequest,
    orderId,
    requestRefNo,
}: {
    evcRequest: Record<string, unknown> | null;
    orderId: string;
    requestRefNo: string;
}) {
    return (
        <StorefrontLayout>
            <Head title="EVC request" />
            <div className="mx-auto max-w-2xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">EVC request</h1>
                <p className="mt-2 text-sm text-gray-600">
                    Order ID: <span className="font-mono">{orderId}</span> — Ref:{' '}
                    <span className="font-mono">{requestRefNo}</span>
                </p>
                {evcRequest ? (
                    <pre className="mt-6 max-h-[60vh] overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs">
                        {JSON.stringify(evcRequest, null, 2)}
                    </pre>
                ) : (
                    <p className="mt-6 text-sm text-amber-800">No matching request found in our records.</p>
                )}
                <Link href={paths.home} className="mt-8 inline-block text-sm font-medium text-brand-600 hover:underline">
                    Back to home
                </Link>
            </div>
        </StorefrontLayout>
    );
}
