import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Wallet as WalletIcon } from 'lucide-react';

type Props = {
    tenant: {
        id: number;
        name: string;
        credit_limit: number | string | null;
        current_balance: number | string | null;
    } | null;
    wallet: { balance: number };
};

export default function Wallet({ tenant, wallet }: Props) {
    const balance = Number(wallet?.balance ?? 0);

    return (
        <AdminLayout>
            <Head title="Wallet" />
            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Wallet</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Your prepaid balance and tenant credit information.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex items-center gap-3">
                            <div className="rounded-lg bg-indigo-50 p-3 dark:bg-indigo-900/30">
                                <WalletIcon className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <div>
                                <p className="text-sm text-gray-500 dark:text-gray-400">Your wallet balance</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    ₹{balance.toLocaleString('en-IN')}
                                </p>
                            </div>
                        </div>
                    </div>
                    {tenant && (
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p className="text-sm text-gray-500 dark:text-gray-400">Tenant</p>
                            <p className="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{tenant.name}</p>
                            {tenant.credit_limit != null && (
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                    Credit limit: ₹{Number(tenant.credit_limit).toLocaleString('en-IN')}
                                </p>
                            )}
                            {tenant.current_balance != null && (
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    Tenant balance: ₹{Number(tenant.current_balance).toLocaleString('en-IN')}
                                </p>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
