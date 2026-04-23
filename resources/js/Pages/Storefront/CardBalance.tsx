import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

type BalanceResult = {
    success: boolean;
    status_code: number;
    message?: string;
    data?: {
        cardNumber?: string;
        balance?: string;
        expiry?: string;
        status?: string;
        currency?: { code?: string; symbol?: string };
    };
};

export default function CardBalance({ result }: { result?: BalanceResult | null }) {
    const form = useForm({
        cardNumber: '',
        pin: '',
        sku: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/card-balance/check');
    };

    return (
        <StorefrontLayout>
            <Head title="Check Card Balance" />
            <div className="mx-auto max-w-2xl px-4 py-10">
                <h1 className="text-3xl font-bold text-gray-900">Check card balance</h1>
                <p className="mt-2 text-sm text-gray-600">Enter your card details to view latest balance and card status.</p>

                <form onSubmit={submit} className="mt-6 space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">Card Number</label>
                        <input
                            value={form.data.cardNumber}
                            onChange={(e) => form.setData('cardNumber', e.target.value)}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            maxLength={50}
                            required
                        />
                        {form.errors.cardNumber ? <p className="mt-1 text-xs text-red-600">{form.errors.cardNumber}</p> : null}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">PIN (Optional)</label>
                        <input
                            value={form.data.pin}
                            onChange={(e) => form.setData('pin', e.target.value)}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            maxLength={25}
                        />
                        {form.errors.pin ? <p className="mt-1 text-xs text-red-600">{form.errors.pin}</p> : null}
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-medium text-gray-700">SKU (Optional)</label>
                        <input
                            value={form.data.sku}
                            onChange={(e) => form.setData('sku', e.target.value)}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            maxLength={64}
                        />
                        {form.errors.sku ? <p className="mt-1 text-xs text-red-600">{form.errors.sku}</p> : null}
                    </div>

                    {form.errors.unexpected_fields ? <p className="text-xs text-red-600">{form.errors.unexpected_fields}</p> : null}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-60"
                    >
                        {form.processing ? 'Checking...' : 'Check Balance'}
                    </button>
                </form>

                {result ? (
                    <div className={`mt-6 rounded-xl border p-5 text-sm shadow-sm ${result.success ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'}`}>
                        {result.success ? (
                            <div className="space-y-1 text-gray-800">
                                <p><span className="font-medium">Card Number:</span> {result.data?.cardNumber ?? '—'}</p>
                                <p><span className="font-medium">Balance:</span> {result.data?.currency?.symbol ?? ''}{result.data?.balance ?? '—'}</p>
                                <p><span className="font-medium">Status:</span> {result.data?.status ?? '—'}</p>
                                <p><span className="font-medium">Expiry:</span> {result.data?.expiry ?? '—'}</p>
                            </div>
                        ) : (
                            <p className="text-red-700">{result.message ?? 'Unable to fetch balance.'}</p>
                        )}
                    </div>
                ) : null}
            </div>
        </StorefrontLayout>
    );
}

