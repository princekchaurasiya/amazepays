import React, { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PriceBreakdown, { type PricingEnvelope } from '@/Components/Pricing/PriceBreakdown';

export default function Preview() {
    const [sku, setSku] = useState('');
    const [quantity, setQuantity] = useState(1);
    const [denomination, setDenomination] = useState<number>(0);

    const preview: PricingEnvelope = useMemo(() => {
        const subtotal = Math.max(0, denomination) * Math.max(1, quantity);
        const discount = 0;
        const gst = Math.round((subtotal - discount) * 0) / 100;
        return {
            unit_price: denomination,
            quantity,
            subtotal,
            discount_amount: discount,
            gst_amount: gst,
            grand_total: subtotal - discount + gst,
            offer_applied: null,
        };
    }, [denomination, quantity]);

    return (
        <AdminLayout>
            <Head title="Pricing — Preview" />
            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Pricing preview</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Phase 5 tool to sanity-check the pricing envelope.</p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                        <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300">SKU</label>
                        <input
                            value={sku}
                            onChange={(e) => setSku(e.target.value)}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            placeholder="e.g. AMAZON100"
                        />
                    </div>
                    <div className="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                        <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300">Quantity</label>
                        <input
                            value={quantity}
                            onChange={(e) => setQuantity(Math.max(1, Number(e.target.value || 1)))}
                            type="number"
                            min={1}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>
                    <div className="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                        <label className="block text-xs font-semibold text-gray-600 dark:text-gray-300">Denomination</label>
                        <input
                            value={denomination}
                            onChange={(e) => setDenomination(Math.max(0, Number(e.target.value || 0)))}
                            type="number"
                            min={0}
                            className="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>
                </div>

                <PriceBreakdown pricing={preview} />

                <div className="text-xs text-gray-500 dark:text-gray-400">
                    For API-backed output use <code className="rounded bg-gray-100 px-1 py-0.5 dark:bg-gray-900">GET /api/v1/products/&lt;sku&gt;/pricing</code>.
                </div>
            </div>
        </AdminLayout>
    );
}

