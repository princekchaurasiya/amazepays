import React from 'react';

export type PricingEnvelope = {
    unit_price?: number;
    quantity?: number;
    subtotal?: number;
    discount_percentage?: number;
    discount_amount?: number;
    gst_percentage?: number;
    gst_amount?: number;
    grand_total?: number;
    offer_applied?: string | null;
};

function fmtMoney(n: number, currencySymbol: string) {
    return `${currencySymbol}${Math.round(n).toLocaleString('en-IN')}`;
}

export default function PriceBreakdown({
    pricing,
    currencySymbol = '₹',
}: {
    pricing: PricingEnvelope;
    currencySymbol?: string;
}) {
    const subtotal = Number(pricing.subtotal ?? 0);
    const discount = Number(pricing.discount_amount ?? 0);
    const gst = Number(pricing.gst_amount ?? 0);
    const total = Number(pricing.grand_total ?? subtotal - discount + gst);

    return (
        <div className="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">Price breakdown</p>
                    {pricing.offer_applied ? (
                        <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Offer applied: {pricing.offer_applied}</p>
                    ) : null}
                </div>
            </div>

            <dl className="mt-3 space-y-2 text-sm">
                <div className="flex items-center justify-between">
                    <dt className="text-gray-600 dark:text-gray-300">Subtotal</dt>
                    <dd className="font-semibold text-gray-900 dark:text-gray-100">{fmtMoney(subtotal, currencySymbol)}</dd>
                </div>
                <div className="flex items-center justify-between">
                    <dt className="text-gray-600 dark:text-gray-300">Discount</dt>
                    <dd className="font-semibold text-emerald-700 dark:text-emerald-300">- {fmtMoney(discount, currencySymbol)}</dd>
                </div>
                <div className="flex items-center justify-between">
                    <dt className="text-gray-600 dark:text-gray-300">GST</dt>
                    <dd className="font-semibold text-gray-900 dark:text-gray-100">{fmtMoney(gst, currencySymbol)}</dd>
                </div>
                <div className="mt-2 border-t border-gray-200 pt-2 dark:border-gray-700">
                    <div className="flex items-center justify-between">
                        <dt className="text-gray-800 dark:text-gray-200">Total</dt>
                        <dd className="text-base font-bold text-gray-900 dark:text-white">{fmtMoney(total, currencySymbol)}</dd>
                    </div>
                </div>
            </dl>
        </div>
    );
}

