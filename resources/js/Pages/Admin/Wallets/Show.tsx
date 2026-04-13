import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft, Wallet } from 'lucide-react';

type WalletT = {
    id: number;
    balance: string | number;
    is_frozen?: boolean;
    frozen_reason?: string | null;
    user?: { id: number; name: string; email: string };
};

type Tx = {
    id: number;
    type: string;
    amount: string | number;
    description: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type Props = {
    wallet: WalletT;
    transactions: Paginated<Tx>;
};

export default function Show({ wallet, transactions }: Props) {
    const [creditAmount, setCreditAmount] = useState('');
    const [creditDesc, setCreditDesc] = useState('');
    const [debitAmount, setDebitAmount] = useState('');
    const [debitDesc, setDebitDesc] = useState('');

    return (
        <AdminLayout>
            <Head title={`Wallet #${wallet.id}`} />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs items={[{ label: 'Wallets', href: '/panel/wallets' }, { label: `#${wallet.id}` }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/wallets" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <Wallet className="text-indigo-600" size={28} />
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Wallet #{wallet.id}</h1>
                        {wallet.user && (
                            <p className="text-sm text-gray-500">
                                {wallet.user.name} — {wallet.user.email}
                                <Link href={`/panel/users/${wallet.user.id}`} className="text-indigo-600 ml-2">User</Link>
                            </p>
                        )}
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    <p className="text-3xl font-bold text-gray-900 dark:text-white">
                        ₹{Number(wallet.balance).toLocaleString('en-IN')}
                    </p>
                    {wallet.is_frozen && (
                        <p className="text-red-600 text-sm mt-2">Frozen: {wallet.frozen_reason || '—'}</p>
                    )}
                    <div className="mt-4 flex flex-wrap gap-2">
                        <button
                            type="button"
                            onClick={() => {
                                if (wallet.is_frozen) {
                                    router.post(`/panel/wallets/${wallet.id}/unfreeze`);
                                } else {
                                    router.post(`/panel/wallets/${wallet.id}/freeze`);
                                }
                            }}
                            className="px-3 py-1.5 border rounded-lg text-sm dark:border-gray-600"
                        >
                            {wallet.is_frozen ? 'Unfreeze' : 'Freeze'}
                        </button>
                    </div>
                </div>
                <div className="grid md:grid-cols-2 gap-6">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 space-y-3">
                        <h3 className="font-semibold">Credit</h3>
                        <input
                            type="number"
                            value={creditAmount}
                            onChange={e => setCreditAmount(e.target.value)}
                            placeholder="Amount"
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                        <input
                            value={creditDesc}
                            onChange={e => setCreditDesc(e.target.value)}
                            placeholder="Description"
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                        <button
                            type="button"
                            onClick={() => router.post(`/panel/wallets/${wallet.id}/credit`, { amount: creditAmount, description: creditDesc })}
                            className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm"
                        >
                            Credit
                        </button>
                    </div>
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 space-y-3">
                        <h3 className="font-semibold">Debit</h3>
                        <input
                            type="number"
                            value={debitAmount}
                            onChange={e => setDebitAmount(e.target.value)}
                            placeholder="Amount"
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                        <input
                            value={debitDesc}
                            onChange={e => setDebitDesc(e.target.value)}
                            placeholder="Description"
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                        <button
                            type="button"
                            onClick={() => router.post(`/panel/wallets/${wallet.id}/debit`, { amount: debitAmount, description: debitDesc })}
                            className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm"
                        >
                            Debit
                        </button>
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <h2 className="px-5 py-3 font-semibold border-b dark:border-gray-700">Transactions</h2>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Date</th>
                                <th className="px-5 py-3">Type</th>
                                <th className="px-5 py-3">Amount</th>
                                <th className="px-5 py-3">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-5 py-8 text-center text-gray-500">No transactions.</td>
                                </tr>
                            ) : (
                                transactions.data.map(tx => (
                                    <tr key={tx.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 text-gray-500 whitespace-nowrap">
                                            {tx.created_at ? new Date(tx.created_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3">{tx.type}</td>
                                        <td className="px-5 py-3">₹{Number(tx.amount).toLocaleString('en-IN')}</td>
                                        <td className="px-5 py-3">{tx.description || '—'}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {transactions.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm">
                            {transactions.current_page > 1 && (
                                <Link href={`/panel/wallets/${wallet.id}?page=${transactions.current_page - 1}`} preserveState className="text-indigo-600">
                                    Previous
                                </Link>
                            )}
                            <span />
                            {transactions.current_page < transactions.last_page && (
                                <Link href={`/panel/wallets/${wallet.id}?page=${transactions.current_page + 1}`} preserveState className="text-indigo-600">
                                    Next
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
