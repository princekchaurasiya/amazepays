import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import PriceBreakdown, { type PricingEnvelope } from '@/Components/Pricing/PriceBreakdown';

type Payment = Record<string, unknown>;

export default function Show({ payment }: { payment: Payment }) {
    const pricing: PricingEnvelope = {
        subtotal: Number(payment.amount_minor ?? 0) / 100,
        discount_amount: 0,
        gst_amount: 0,
        grand_total: Number(payment.amount_minor ?? 0) / 100,
    };

    return (
        <AdminLayout>
            <Head title="Payment session" />
            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Payment session</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Inspect gateway status and the linked order.</p>
                </div>

                <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <pre className="overflow-auto text-xs text-gray-700 dark:text-gray-200">{JSON.stringify(payment, null, 2)}</pre>
                </div>

                <PriceBreakdown pricing={pricing} />
            </div>
        </AdminLayout>
    );
}

