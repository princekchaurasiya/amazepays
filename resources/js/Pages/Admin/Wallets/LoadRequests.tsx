import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type ReqRow = {
    id: number;
    amount: string | number;
    status: string;
    utr_number: string | null;
    user?: { name: string; email: string };
    tenant?: { name: string };
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
    requests: Paginated<ReqRow>;
    filters: { status?: string };
    counts: { pending: number; under_review: number };
};

export default function LoadRequests({ requests, filters, counts }: Props) {
    const [status, setStatus] = useState(filters.status ?? '');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/wallets/load-requests', { status: status || undefined }, { preserveState: true });
    };

    return (
        <AdminLayout>
            <Head title="Wallet load requests" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Wallets', href: '/panel/wallets' }, { label: 'Load requests' }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/wallets" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Load requests</h1>
                </div>
                <p className="text-sm text-gray-500">
                    Pending: {counts.pending} · Under review: {counts.under_review}
                </p>
                <form onSubmit={apply} className="flex gap-3 items-end">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Status</label>
                        <select
                            value={status}
                            onChange={e => setStatus(e.target.value)}
                            className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        >
                            <option value="">All</option>
                            <option value="pending">pending</option>
                            <option value="under_review">under_review</option>
                            <option value="approved">approved</option>
                            <option value="rejected">rejected</option>
                        </select>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">ID</th>
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3">Amount</th>
                                <th className="px-5 py-3">UTR</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {requests.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-5 py-12 text-center text-gray-500">No requests.</td>
                                </tr>
                            ) : (
                                requests.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 font-mono text-xs">{row.id}</td>
                                        <td className="px-5 py-3">{row.user?.email || row.user?.name || '—'}</td>
                                        <td className="px-5 py-3">₹{Number(row.amount).toLocaleString('en-IN')}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.utr_number || '—'}</td>
                                        <td className="px-5 py-3">{row.status}</td>
                                        <td className="px-5 py-3 space-x-2">
                                            {['pending', 'under_review'].includes(row.status) && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            const note = window.prompt('Approval note (optional)');
                                                            if (note !== null) {
                                                                router.post(`/panel/wallets/load-requests/${row.id}/approve`, { note: note || undefined });
                                                            }
                                                        }}
                                                        className="text-green-600 text-sm font-medium"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            const reason = window.prompt('Rejection reason');
                                                            if (reason) {
                                                                router.post(`/panel/wallets/load-requests/${row.id}/reject`, { reason });
                                                            }
                                                        }}
                                                        className="text-red-600 text-sm font-medium"
                                                    >
                                                        Reject
                                                    </button>
                                                </>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {requests.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>{requests.from}–{requests.to} of {requests.total}</span>
                            <div className="flex gap-2">
                                {requests.current_page > 1 && (
                                    <Link
                                        href={`/panel/wallets/load-requests?page=${requests.current_page - 1}&status=${encodeURIComponent(status)}`}
                                        preserveState
                                        className="px-3 py-1 rounded border dark:border-gray-600"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {requests.current_page < requests.last_page && (
                                    <Link
                                        href={`/panel/wallets/load-requests?page=${requests.current_page + 1}&status=${encodeURIComponent(status)}`}
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
