import React, { useEffect, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
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
    const page = usePage();
    const appEnv = (page.props as any)?.app?.env as string | undefined;
    const isLocal = appEnv === 'local' || appEnv === 'testing';

    const normalizedStatus = useMemo(() => String(status || '').toLowerCase(), [status]);
    const isTerminal = normalizedStatus === 'failed' || normalizedStatus === 'cancelled' || normalizedStatus === 'canceled';

    useEffect(() => {
        const orderIdNum = order_id ? Number(order_id) : 0;
        if (!orderIdNum || !Number.isFinite(orderIdNum)) return;
        if (isTerminal) return;

        let cancelled = false;
        let tries = 0;
        const maxTries = isLocal ? 60 : 120; // ~2-4 minutes at 2s interval

        const tick = async () => {
            tries += 1;
            try {
                const res = await fetch(`/api/v1/orders/${orderIdNum}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                const stRaw = (data?.data?.status ?? data?.status ?? '') as string;
                const st = String(stRaw).toLowerCase();

                if (st === 'completed' || st === 'fulfilled' || st === 'complete') {
                    window.location.href = `/payment/success?amount=${encodeURIComponent(String(amount || 0))}`;
                    return;
                }
                if (st === 'failed') {
                    window.location.href = `/payment/failed?amount=${encodeURIComponent(String(amount || 0))}`;
                    return;
                }
                if (st === 'cancelled') {
                    window.location.href = `/payment/cancelled?amount=${encodeURIComponent(String(amount || 0))}`;
                    return;
                }
            } catch {
                // ignore transient network errors
            }

            if (!cancelled && tries < maxTries) {
                setTimeout(tick, 2000);
            }
        };

        tick();
        return () => {
            cancelled = true;
        };
    }, [order_id, amount, isLocal, isTerminal]);

    return (
        <CheckoutLayout>
            <Head title="Processing payment" />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                {isTerminal ? (
                    <>
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-50 text-red-600">
                            <span className="text-2xl font-bold">!</span>
                        </div>
                        <h1 className="mt-6 text-xl font-bold text-gray-900">Payment failed</h1>
                        <p className="mt-2 text-sm text-gray-600">This payment could not be completed. Please try again.</p>
                        <div className="mt-6 flex flex-col gap-3">
                            <Link
                                href={paths.myOrders}
                                className="inline-flex items-center justify-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                View my orders
                            </Link>
                            <Link
                                href={paths.cart}
                                className="inline-flex items-center justify-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-50"
                            >
                                Go to cart
                            </Link>
                        </div>
                    </>
                ) : (
                    <>
                        <div className="mx-auto h-16 w-16 animate-spin rounded-full border-4 border-emerald-200 border-t-emerald-600" />
                        <h1 className="mt-6 text-xl font-bold text-gray-900">Processing your payment</h1>
                        <p className="mt-2 text-sm text-gray-600">Please do not refresh or use the back button.</p>
                    </>
                )}
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
                {!isTerminal ? (
                    <Link href={paths.myOrders} className="mt-8 inline-block text-sm text-brand-600 hover:underline">
                        View my orders
                    </Link>
                ) : null}
            </div>
        </CheckoutLayout>
    );
}
