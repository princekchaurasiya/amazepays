import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

type Provider = {
    key: string;
    label: string;
    healthy: boolean;
    href: string | null;
    note?: string | null;
};

export default function ProvidersIndex({
    providers,
    order_stats,
}: {
    providers: Provider[];
    order_stats: { storefront_orders: number; kgen_provider_orders: number };
}) {
    return (
        <AdminLayout>
            <Head title="Providers" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900">Voucher providers</h1>
                <p className="mt-2 text-sm text-gray-600">Health flags are based on required env/config values being non-empty.</p>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {providers.map((p) => (
                        <div key={p.key} className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center justify-between gap-2">
                                <h2 className="font-semibold text-gray-900 dark:text-gray-100">{p.label}</h2>
                                <span
                                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                        p.healthy ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'
                                    }`}
                                >
                                    {p.healthy ? 'Configured' : 'Check env'}
                                </span>
                            </div>
                            {p.note ? (
                                <p className="mt-2 text-xs leading-relaxed text-gray-600 dark:text-gray-400">{p.note}</p>
                            ) : null}
                            {p.href ? (
                                <Link href={p.href} className="mt-3 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                                    Open dashboard
                                </Link>
                            ) : (
                                <p className="mt-3 text-xs text-gray-500">No dedicated panel yet — configure via env.</p>
                            )}
                        </div>
                    ))}
                </div>

                <div className="mt-10 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 className="font-semibold text-gray-900 dark:text-gray-100">Order volume</h3>
                    <ul className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        <li>Storefront orders: {order_stats.storefront_orders}</li>
                        <li>KGen provider orders: {order_stats.kgen_provider_orders}</li>
                    </ul>
                </div>
            </div>
        </AdminLayout>
    );
}
