import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

type OrderRow = {
    order_id: number;
    product_name?: string;
    brand_name?: string;
    order_status?: string;
    amount_payable_after_discount?: string | number;
    view_card_url?: string | null;
    display_image?: string | null;
};

function statusUi(raw?: string): { label: string; tone: 'green' | 'red' | 'gray' } {
    const st = String(raw || '').toLowerCase();
    if (['fulfilled', 'complete', 'completed', 'paid', 'success'].includes(st)) return { label: 'Fulfilled', tone: 'green' };
    if (['failed', 'failure'].includes(st)) return { label: 'Failed', tone: 'red' };
    if (['cancelled', 'canceled'].includes(st)) return { label: 'Cancelled', tone: 'red' };
    if (st === 'processing') return { label: 'Processing', tone: 'gray' };
    return { label: raw ? String(raw) : '—', tone: 'gray' };
}

export default function Orders({ orders = [] }: { orders: OrderRow[] }) {
    return (
        <StorefrontLayout>
            <Head title="My orders" />
            <div className="mx-auto max-w-4xl px-4 py-10">
                <nav className="mb-6 flex gap-4 text-sm text-gray-600">
                    <Link href={paths.profile} className="hover:text-brand-600">
                        Profile
                    </Link>
                    <span className="font-medium text-gray-900">My orders</span>
                </nav>
                <h1 className="text-2xl font-bold text-gray-900">My orders</h1>
                <div className="mt-6 space-y-4">
                    {orders.length === 0 ? (
                        <p className="text-gray-600">No orders yet.</p>
                    ) : (
                        orders.map((o) => (
                            <div key={o.order_id} className="flex gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                {o.display_image ? (
                                    <img src={o.display_image} alt="" className="h-20 w-20 rounded-lg object-cover" />
                                ) : (
                                    <div className="flex h-20 w-20 items-center justify-center rounded-lg bg-gray-100 text-gray-400">—</div>
                                )}
                                <div className="min-w-0 flex-1">
                                    <p className="font-semibold text-gray-900">{o.product_name}</p>
                                    <p className="text-sm text-gray-600">{o.brand_name}</p>
                                    {(() => {
                                        const s = statusUi(o.order_status);
                                        const cls =
                                            s.tone === 'green'
                                                ? 'bg-emerald-100 text-emerald-800'
                                                : s.tone === 'red'
                                                  ? 'bg-rose-100 text-rose-800'
                                                  : 'bg-gray-100 text-gray-700';
                                        return (
                                            <span className={`mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${cls}`}>
                                                {s.label}
                                            </span>
                                        );
                                    })()}
                                    {o.view_card_url ? (
                                        <div className="mt-3">
                                            <Link
                                                href={o.view_card_url}
                                                className="inline-flex items-center justify-center rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800"
                                            >
                                                View card details
                                            </Link>
                                        </div>
                                    ) : null}
                                </div>
                                <div className="text-right text-sm font-medium text-gray-900">
                                    ₹{Number(o.amount_payable_after_discount ?? 0).toLocaleString('en-IN')}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </StorefrontLayout>
    );
}
