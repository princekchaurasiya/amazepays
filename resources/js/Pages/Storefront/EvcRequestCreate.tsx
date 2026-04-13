import React from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

type Prefill = Record<string, string | undefined>;

export default function EvcRequestCreate({ prefill = {} }: { prefill?: Prefill }) {
    const token = typeof document !== 'undefined' ? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '' : '';

    const denom = Number(prefill.denomination ?? '');
    const qty = Number(prefill.quantity ?? '');

    return (
        <StorefrontLayout>
            <Head title="EVC checkout" />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">EVC checkout</h1>
                <p className="mt-2 text-sm text-gray-600">
                    Submit payment details for Value Design fulfilment. For support, contact us from the contact page.
                </p>

                <form action="/vd-payment" method="post" className="mt-8 space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <input type="hidden" name="_token" value={token} />
                    <input type="hidden" name="vd_discount" value={prefill.vd_discount ?? ''} />
                    <input type="hidden" name="vd_brand_code" value={prefill.vd_brand_code ?? ''} />

                    <div>
                        <label className="text-sm font-medium text-gray-700">Payable amount (INR)</label>
                        <input
                            name="payable_amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                            defaultValue={prefill.payable_amount ?? (Number.isFinite(denom * qty) && denom * qty > 0 ? String(denom * qty) : '')}
                            className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                    </div>
                    <div>
                        <label className="text-sm font-medium text-gray-700">Existing order ID (optional)</label>
                        <input
                            name="order_id"
                            type="number"
                            className="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                            placeholder="Leave blank for guest payment"
                        />
                    </div>
                    <p className="text-xs text-gray-500">
                        Denomination / quantity from a prior step can be stored via query string; payable amount must match what you
                        intend to charge.
                    </p>
                    <button type="submit" className="w-full rounded-lg bg-brand-600 py-2.5 text-sm font-semibold text-white hover:bg-brand-700">
                        Continue to payment
                    </button>
                </form>
            </div>
        </StorefrontLayout>
    );
}
