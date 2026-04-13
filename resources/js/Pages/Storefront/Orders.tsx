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
                                    <p className="text-xs text-gray-500">Status: {o.order_status}</p>
                                    {o.view_card_url ? (
                                        <Link href={o.view_card_url} className="mt-2 inline-block text-sm font-medium text-brand-600 hover:underline">
                                            View card
                                        </Link>
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
