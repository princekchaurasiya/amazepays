import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

type CardRow = Record<string, unknown>;

export default function OrderStatus({
    transactionStatusMessage,
    isSuccessful,
    errorMessage,
    cardsArray = [],
}: {
    transactionStatusMessage?: string;
    isSuccessful?: boolean;
    errorMessage?: string;
    cardsArray?: CardRow[];
}) {
    const message = transactionStatusMessage ?? errorMessage ?? 'Something went wrong.';
    const ok = isSuccessful === true;

    return (
        <CheckoutLayout>
            <Head title="Order status" />
            <div className="mx-auto max-w-3xl px-4 py-12 text-center">
                <div
                    className={`mx-auto flex h-16 w-16 items-center justify-center rounded-full text-2xl font-bold ${
                        ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                    }`}
                >
                    {ok ? '\u2713' : '\u2717'}
                </div>
                <h1 className={`mt-6 text-xl font-bold ${ok ? 'text-emerald-800' : 'text-red-800'}`}>{message}</h1>
                {ok ? (
                    <p className="mt-2 text-sm text-gray-600">Thank you for your purchase! We have received your order.</p>
                ) : (
                    <p className="mt-4 text-sm text-gray-600">
                        We could not complete this order.{' '}
                        <Link href={paths.home} className="font-medium text-brand-600 hover:underline">
                            Return home
                        </Link>{' '}
                        and try again.
                    </p>
                )}

                {ok && cardsArray.length > 0 ? (
                    <div className="mt-10 text-left">
                        <h2 className="text-center text-lg font-semibold text-gray-900">Your voucher details</h2>
                        <div className="mt-4 grid gap-4 sm:grid-cols-2">
                            {cardsArray.map((card, index) => (
                                <div
                                    key={index}
                                    className="rounded-xl border-2 border-emerald-500/40 bg-emerald-50/50 p-4 text-sm shadow-sm"
                                >
                                    <p className="mb-2 font-semibold text-emerald-800">Voucher {index + 1}</p>
                                    <dl className="space-y-1">
                                        {Object.entries(card).map(([k, v]) => (
                                            <div key={k} className="flex justify-between gap-2">
                                                <dt className="text-gray-500">{k}</dt>
                                                <dd className="break-all text-right font-mono text-gray-900">{String(v ?? '—')}</dd>
                                            </div>
                                        ))}
                                    </dl>
                                </div>
                            ))}
                        </div>
                        <p className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900">
                            Please save these details. You will also receive them by email/SMS when available.
                        </p>
                    </div>
                ) : null}

                <Link href={paths.myOrders} className="mt-8 inline-block text-sm font-medium text-brand-600 hover:underline">
                    View your orders
                </Link>
            </div>
        </CheckoutLayout>
    );
}
