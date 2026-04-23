import React, { FormEvent, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import FileUploadDropzone from '@/Components/Admin/FileUploadDropzone';
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

const PAYMENT_MODES = [
    { value: 'neft', label: 'NEFT' },
    { value: 'imps', label: 'IMPS' },
    { value: 'rtgs', label: 'RTGS' },
    { value: 'cash', label: 'Cash' },
    { value: 'cheque', label: 'Cheque' },
];

export default function Show({ wallet, transactions }: Props) {
    const { auth } = usePage().props as { auth?: { user?: { permissions?: string[] } } };
    const canSubmitOnBehalf = !!auth?.user?.permissions?.includes('wallets.load_requests.submit_on_behalf');

    const [loadAmount, setLoadAmount] = useState('');
    const [loadMode, setLoadMode] = useState('neft');
    const [loadRef, setLoadRef] = useState('');
    const [loadProof, setLoadProof] = useState<File | null>(null);

    const [debitAmount, setDebitAmount] = useState('');
    const [debitDesc, setDebitDesc] = useState('');

    const submitLoadRequest = (e: FormEvent) => {
        e.preventDefault();
        if (!loadProof) {
            return;
        }
        const fd = new FormData();
        fd.append('amount', loadAmount);
        fd.append('payment_mode', loadMode);
        if (loadRef) {
            fd.append('reference_no', loadRef);
        }
        fd.append('proof', loadProof);
        router.post(`/panel/wallets/${wallet.id}/load-requests`, fd, {
            forceFormData: true,
            onSuccess: () => {
                setLoadAmount('');
                setLoadRef('');
                setLoadProof(null);
            },
        });
    };

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
                        <Link
                            href="/panel/wallets/load-requests"
                            className="px-3 py-1.5 border rounded-lg text-sm dark:border-gray-600 text-indigo-600"
                        >
                            Load requests queue
                        </Link>
                    </div>
                </div>
                <div className="grid md:grid-cols-2 gap-6">
                    {canSubmitOnBehalf ? (
                        <form
                            onSubmit={submitLoadRequest}
                            className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 space-y-3"
                        >
                            <h3 className="font-semibold">Submit load request (on behalf)</h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                Creates a pending request. Finance must approve before the wallet is credited.
                            </p>
                            <input
                                type="number"
                                required
                                value={loadAmount}
                                onChange={e => setLoadAmount(e.target.value)}
                                placeholder="Amount (min 100)"
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                            <select
                                value={loadMode}
                                onChange={e => setLoadMode(e.target.value)}
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            >
                                {PAYMENT_MODES.map(m => (
                                    <option key={m.value} value={m.value}>{m.label}</option>
                                ))}
                            </select>
                            <input
                                value={loadRef}
                                onChange={e => setLoadRef(e.target.value)}
                                placeholder="Reference / UTR / cheque no (optional)"
                                className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                            />
                            <FileUploadDropzone
                                label="Payment proof"
                                description="PDF, PNG or JPEG — max 2 MB (same rules as upload validation)."
                                value={loadProof}
                                onChange={setLoadProof}
                                accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf"
                                required
                                compact
                            />
                            <button
                                type="submit"
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm"
                            >
                                Submit load request
                            </button>
                        </form>
                    ) : (
                        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                            <h3 className="font-semibold text-gray-900 dark:text-white">Wallet top-ups</h3>
                            <p>Direct credits are disabled. Use the load requests queue to approve funding.</p>
                            <Link href="/panel/wallets/load-requests" className="text-indigo-600 font-medium">Open load requests</Link>
                        </div>
                    )}
                    <div
                        className={`bg-white dark:bg-gray-800 rounded-xl shadow-sm p-4 space-y-3 ${wallet.is_frozen ? 'opacity-60' : ''}`}
                    >
                        <h3 className="font-semibold">Debit</h3>
                        {wallet.is_frozen ? (
                            <p className="text-xs text-amber-700 dark:text-amber-400">Debit is disabled while this wallet is frozen.</p>
                        ) : null}
                        <input
                            type="number"
                            value={debitAmount}
                            onChange={e => setDebitAmount(e.target.value)}
                            placeholder="Amount"
                            disabled={!!wallet.is_frozen}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 disabled:cursor-not-allowed"
                        />
                        <input
                            value={debitDesc}
                            onChange={e => setDebitDesc(e.target.value)}
                            placeholder="Description"
                            disabled={!!wallet.is_frozen}
                            className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 disabled:cursor-not-allowed"
                        />
                        <button
                            type="button"
                            disabled={!!wallet.is_frozen}
                            onClick={() => router.post(`/panel/wallets/${wallet.id}/debit`, { amount: debitAmount, description: debitDesc })}
                            className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm disabled:cursor-not-allowed disabled:opacity-50"
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
                                        <td className="px-5 py-3">{'\u20B9'}{Number(tx.amount).toLocaleString('en-IN')}</td>
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
