import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import FilePreviewModal from '@/Components/Admin/FilePreviewModal';
import { ArrowLeft } from 'lucide-react';

type ReqRow = {
    id: number;
    amount: string | number;
    status: string;
    payment_mode: string;
    reference_no: string | null;
    proof_file: string | null;
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
    counts: { pending: number };
};

export default function LoadRequests({ requests, filters, counts }: Props) {
    const [status, setStatus] = useState(filters.status ?? '');
    const [modal, setModal] = useState<null | { row: ReqRow; kind: 'approve' | 'reject' }>(null);
    const [proofPreview, setProofPreview] = useState<ReqRow | null>(null);
    const [note, setNote] = useState('');
    const [reason, setReason] = useState('');

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/wallets/load-requests', { status: status || undefined }, { preserveState: true });
    };

    const closeModal = () => {
        setModal(null);
        setNote('');
        setReason('');
    };

    const submitApprove = (e: FormEvent) => {
        e.preventDefault();
        if (!modal || modal.kind !== 'approve') {
            return;
        }
        router.post(`/panel/wallets/load-requests/${modal.row.id}/approve`, { note: note || undefined }, {
            onSuccess: closeModal,
        });
    };

    const submitReject = (e: FormEvent) => {
        e.preventDefault();
        if (!modal || modal.kind !== 'reject' || !reason.trim()) {
            return;
        }
        router.post(`/panel/wallets/load-requests/${modal.row.id}/reject`, { reason: reason.trim() }, {
            onSuccess: closeModal,
        });
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
                    Pending: {counts.pending}
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
                                <th className="px-5 py-3">Mode</th>
                                <th className="px-5 py-3">Reference</th>
                                <th className="px-5 py-3">Proof</th>
                                <th className="px-5 py-3">Status</th>
                                <th className="px-5 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {requests.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="px-5 py-12 text-center text-gray-500">No requests.</td>
                                </tr>
                            ) : (
                                requests.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 font-mono text-xs">{row.id}</td>
                                        <td className="px-5 py-3">{row.user?.email || row.user?.name || '—'}</td>
                                        <td className="px-5 py-3">{'\u20B9'}{Number(row.amount).toLocaleString('en-IN')}</td>
                                        <td className="px-5 py-3">{row.payment_mode}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.reference_no || '—'}</td>
                                        <td className="px-5 py-3">
                                            {row.proof_file ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setProofPreview(row)}
                                                    className="text-indigo-600 font-medium hover:underline"
                                                >
                                                    View
                                                </button>
                                            ) : (
                                                '—'
                                            )}
                                        </td>
                                        <td className="px-5 py-3">{row.status}</td>
                                        <td className="px-5 py-3 space-x-2">
                                            {row.status === 'pending' && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() => setModal({ row, kind: 'approve' })}
                                                        className="text-green-600 text-sm font-medium"
                                                    >
                                                        Approve
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => setModal({ row, kind: 'reject' })}
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

                <FilePreviewModal
                    open={!!proofPreview}
                    onClose={() => setProofPreview(null)}
                    fileUrl={proofPreview ? `/panel/wallets/load-requests/${proofPreview.id}/proof` : ''}
                    fileHint={proofPreview?.proof_file}
                    title="Payment proof"
                    subtitle={
                        proofPreview
                            ? `Request #${proofPreview.id} · ${proofPreview.user?.email || proofPreview.user?.name || 'User'} · ${'\u20B9'}${Number(proofPreview.amount).toLocaleString('en-IN')} (${proofPreview.payment_mode})`
                            : undefined
                    }
                />

                {modal ? (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-lg max-w-md w-full p-6 space-y-4">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                {modal.kind === 'approve' ? 'Approve load request' : 'Reject load request'}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                #{modal.row.id} — ₹{Number(modal.row.amount).toLocaleString('en-IN')} ({modal.row.payment_mode})
                            </p>
                            {modal.kind === 'approve' ? (
                                <form onSubmit={submitApprove} className="space-y-3">
                                    <label className="block text-xs text-gray-500">Note (optional)</label>
                                    <textarea
                                        value={note}
                                        onChange={e => setNote(e.target.value)}
                                        className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                                        rows={3}
                                    />
                                    <div className="flex justify-end gap-2">
                                        <button type="button" onClick={closeModal} className="px-3 py-1.5 text-sm border rounded-lg dark:border-gray-600">
                                            Cancel
                                        </button>
                                        <button type="submit" className="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg">
                                            Approve & credit
                                        </button>
                                    </div>
                                </form>
                            ) : (
                                <form onSubmit={submitReject} className="space-y-3">
                                    <label className="block text-xs text-gray-500">Reason (required)</label>
                                    <textarea
                                        value={reason}
                                        onChange={e => setReason(e.target.value)}
                                        required
                                        className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                                        rows={3}
                                    />
                                    <div className="flex justify-end gap-2">
                                        <button type="button" onClick={closeModal} className="px-3 py-1.5 text-sm border rounded-lg dark:border-gray-600">
                                            Cancel
                                        </button>
                                        <button type="submit" className="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg">
                                            Reject
                                        </button>
                                    </div>
                                </form>
                            )}
                        </div>
                    </div>
                ) : null}
            </div>
        </AdminLayout>
    );
}
