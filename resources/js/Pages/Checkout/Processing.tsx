import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

export default function Processing({
    order_id,
    status = 'Processing',
    amount = 0,
    merchant_order_id = '',
    payment_id = '',
}: {
    order_id?: number | string | null;
    status?: string;
    amount?: number | string;
    merchant_order_id?: string;
    payment_id?: string | number;
}) {
    return (
        <CheckoutLayout>
            <Head title="Processing payment" />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                <div className="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600" />
                <h1 className="mt-6 text-xl font-bold text-gray-900">Processing your payment</h1>
                <p className="mt-2 text-sm text-gray-600">Please do not refresh or use the back button.</p>
                <dl className="mt-8 space-y-2 text-left text-sm text-gray-600">
                    <div className="flex justify-between gap-4">
                        <dt>Status</dt>
                        <dd className="font-medium text-gray-900">{status}</dd>
                    </div>
                    {order_id ? (
                        <div className="flex justify-between gap-4">
                            <dt>Order</dt>
                            <dd className="font-mono text-gray-900">{String(order_id)}</dd>
                        </div>
                    ) : null}
                    {merchant_order_id ? (
                        <div className="flex justify-between gap-4">
                            <dt>Reference</dt>
                            <dd className="break-all font-mono text-xs text-gray-900">{merchant_order_id}</dd>
                        </div>
                    ) : null}
                    {payment_id ? (
                        <div className="flex justify-between gap-4">
                            <dt>Payment</dt>
                            <dd className="font-mono text-gray-900">{String(payment_id)}</dd>
                        </div>
                    ) : null}
                    <div className="flex justify-between gap-4">
                        <dt>Amount</dt>
                        <dd className="font-medium text-gray-900">
                            &#8377;{Number(amount || 0).toLocaleString('en-IN')}
                        </dd>
                    </div>
                </dl>
                <Link href={paths.myOrders} className="mt-8 inline-block text-sm text-brand-600 hover:underline">
                    View my orders
                </Link>
            </div>
        </CheckoutLayout>
    );
}
