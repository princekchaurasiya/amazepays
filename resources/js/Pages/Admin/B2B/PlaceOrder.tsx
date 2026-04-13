import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ShoppingBag } from 'lucide-react';

type Props = {
    tenant: { id: number; name: string; slug: string } | null;
};

export default function PlaceOrder({ tenant }: Props) {
    return (
        <AdminLayout>
            <Head title="Place order" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Place order</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Create a new B2B voucher order for your tenant. Use the API or catalog for bulk flows; this page is a
                        quick entry point.
                    </p>
                </div>
                {!tenant ? (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-6 text-amber-900 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                        <p className="font-medium">No tenant linked</p>
                        <p className="mt-1 text-sm">Contact support to attach your account to a B2B company.</p>
                    </div>
                ) : (
                    <div className="rounded-xl border border-gray-200 bg-white p-8 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-start gap-4">
                            <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                <ShoppingBag className="h-8 w-8 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Ordering for</p>
                                <p className="text-lg font-semibold text-gray-900 dark:text-white">{tenant.name}</p>
                                <p className="text-xs text-gray-400">Slug: {tenant.slug}</p>
                            </div>
                        </div>
                        <p className="mt-6 text-sm text-gray-600 dark:text-gray-300">
                            Order placement UI can be wired to your catalog and approval rules. For now, use the storefront API or
                            your integration workflow to submit orders.
                        </p>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
