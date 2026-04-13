import React from 'react';
import { Head, Link } from '@inertiajs/react';
import CheckoutLayout from '@/Layouts/CheckoutLayout';
import { paths } from '@/lib/paths';

export default function Status({
    status,
    msg,
    amount,
}: {
    status: 'success' | 'failure' | string;
    msg: string;
    amount?: number | string | null;
}) {
    const ok = status === 'success';
    return (
        <CheckoutLayout>
            <Head title={msg} />
            <div className="mx-auto max-w-lg px-4 py-16 text-center">
                <div
                    className={`mx-auto flex h-16 w-16 items-center justify-center rounded-full text-2xl font-bold ${
                        ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
                    }`}
                >
                    {ok ? '\u2713' : '\u2717'}
                </div>
                <h1 className={`mt-6 text-xl font-bold ${ok ? 'text-emerald-800' : 'text-red-800'}`}>{msg}</h1>
                {amount != null && amount !== '' ? (
                    <p className="mt-2 text-sm text-gray-600">
                        Amount: &#8377;{Number(amount).toLocaleString('en-IN')}
                    </p>
                ) : null}
                <div className="mt-8 flex flex-wrap justify-center gap-4">
                    <Link href={paths.home} className="text-sm font-medium text-brand-600 hover:underline">
                        Continue shopping
                    </Link>
                    <Link href={paths.myOrders} className="text-sm font-medium text-brand-600 hover:underline">
                        My orders
                    </Link>
                </div>
            </div>
        </CheckoutLayout>
    );
}
