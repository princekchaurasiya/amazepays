import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { Wallet, Search } from 'lucide-react';

type WalletRow = {
    id: number;
    balance: string | number;
    is_frozen?: boolean;
    user?: { id: number; name: string; email: string };
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
    wallets: Paginated<WalletRow>;
};

export default function Index({ wallets }: Props) {
    const [search, setSearch] = useState(
        typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('search') ?? '' : '',
    );

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/wallets', { search: search || undefined }, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Wallets" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Wallets' }]} />
                <div className="flex items-center gap-2">
                    <Wallet className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Wallets</h1>
                </div>
                <form onSubmit={applyFilters} className="flex flex-wrap gap-3 items-end bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm">
                    <div className="flex-1 min-w-[200px]">
                        <label className="block text-xs text-gray-500 mb-1">User name</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                placeholder="Search by user name"
                                className="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                            />
                        </div>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">
                        Search
                    </button>
                    <Link href="/panel/wallets/load-requests" className="px-4 py-2 text-sm text-indigo-600 border border-indigo-200 rounded-lg hover:bg-indigo-50">
                        Load requests
                    </Link>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3">Balance</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3 w-24" />
                            </tr>
                        </thead>
                        <tbody>
                            {wallets.data.length === 0 ? (
                                <tr>
                                    <td colSpan={4} className="px-5 py-12 text-center text-gray-500">No wallets found.</td>
                                </tr>
                            ) : (
                                wallets.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3">
                                            {row.user ? (
                                                <span>
                                                    <span className="font-medium">{row.user.name}</span>
                                                    <span className="text-gray-500 text-xs block">{row.user.email}</span>
                                                </span>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-5 py-3">₹{Number(row.balance).toLocaleString('en-IN')}</td>
                                        <td className="px-5 py-3">
                                            {row.is_frozen ? (
                                                <span className="text-red-600 text-xs font-medium">Frozen</span>
                                            ) : (
                                                <span className="text-green-600 text-xs">Active</span>
                                            )}
                                        </td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons viewHref={`/panel/wallets/${row.id}`} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {wallets.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>{wallets.from}–{wallets.to} of {wallets.total}</span>
                            <div className="flex gap-2">
                                {wallets.current_page > 1 && (
                                    <Link
                                        href={`/panel/wallets?page=${wallets.current_page - 1}&search=${encodeURIComponent(search)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {wallets.current_page < wallets.last_page && (
                                    <Link
                                        href={`/panel/wallets?page=${wallets.current_page + 1}&search=${encodeURIComponent(search)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
