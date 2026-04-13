import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

export default function WoohooResponse({
    order,
    woohoo,
    isSuccess,
}: {
    order: Record<string, unknown>;
    woohoo: unknown;
    isSuccess: boolean;
}) {
    return (
        <CheckoutLayout>
            <Head title={isSuccess ? 'Order complete' : 'Order issue'} />
            <div className="mx-auto max-w-xl px-4 py-12">
                <div className="text-center">
                    <div
                        className={`mx-auto flex h-14 w-14 items-center justify-center rounded-full text-xl font-bold ${
                            isSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800'
                        }`}
                    >
                        {isSuccess ? '\u2713' : '!'}
                    </div>
                    <h1 className="mt-4 text-xl font-bold text-gray-900">
                        {isSuccess ? 'Payment received' : 'We need a moment'}
                    </h1>
                    <p className="mt-2 text-sm text-gray-600">
                        {isSuccess
                            ? 'Your order is being finalized. You will receive confirmation shortly.'
                            : 'There was a problem creating your gift card order. If you were charged, support will assist with a refund or resend.'}
                    </p>
                </div>
                <dl className="mt-8 space-y-2 rounded-xl border border-gray-200 bg-white p-4 text-sm">
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500">Reference</dt>
                        <dd className="font-mono text-gray-900">{String(order.refno ?? order.woohoo_order_id ?? '—')}</dd>
                    </div>
                    <div className="flex justify-between gap-4">
                        <dt className="text-gray-500">Status</dt>
                        <dd className="text-gray-900">{String(order.order_status ?? '—')}</dd>
                    </div>
                </dl>
                {import.meta.env.DEV && woohoo != null ? (
                    <pre className="mt-4 max-h-48 overflow-auto rounded-lg bg-gray-100 p-3 text-xs">{JSON.stringify(woohoo, null, 2)}</pre>
                ) : null}
                <div className="mt-8 flex justify-center gap-6">
                    <Link href={paths.myOrders} className="text-sm font-medium text-brand-600 hover:underline">
                        My orders
                    </Link>
                    <Link href={paths.home} className="text-sm font-medium text-brand-600 hover:underline">
                        Home
                    </Link>
                </div>
            </div>
        </CheckoutLayout>
    );
}
