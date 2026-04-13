import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs, ConfirmDialog } from '@/Components/Admin';
import { Smartphone } from 'lucide-react';

type BlockRow = {
    id: number;
    mobile: string;
    reason: string | null;
    permanent: boolean;
    expires_at: string | null;
    blocked_at: string | null;
    blocked_by_user?: { name?: string; email?: string };
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
    blockedMobiles: Paginated<BlockRow>;
    filters: { search?: string; active_only?: boolean };
};

export default function BlockedMobiles({ blockedMobiles, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [activeOnly, setActiveOnly] = useState(Boolean(filters.active_only));
    const [mobile, setMobile] = useState('');
    const [reason, setReason] = useState('');
    const [duration, setDuration] = useState('24');
    const [permanent, setPermanent] = useState(false);
    const [unblockTarget, setUnblockTarget] = useState<BlockRow | null>(null);
    const [unblocking, setUnblocking] = useState(false);

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/panel/security/blocked-mobiles',
            { search: search || undefined, active_only: activeOnly || undefined },
            { preserveState: true },
        );
    };

    const submitBlock = (e: FormEvent) => {
        e.preventDefault();
        router.post('/panel/security/blocked-mobiles', {
            mobile,
            reason,
            duration: Number(duration),
            permanent,
        });
    };

    const confirmUnblock = () => {
        if (!unblockTarget) return;
        router.delete(`/panel/security/blocked-mobiles/${unblockTarget.mobile}`, {
            preserveScroll: true,
            onStart: () => setUnblocking(true),
            onFinish: () => setUnblocking(false),
            onSuccess: () => setUnblockTarget(null),
        });
    };

    return (
        <AdminLayout>
            <Head title="Blocked mobiles" />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs
                    items={[
                        { label: 'Security', href: '/panel/security' },
                        { label: 'Blocked mobiles' },
                    ]}
                />
                <div className="flex items-center gap-2">
                    <Smartphone className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Blocked mobiles</h1>
                </div>
                <form onSubmit={submitBlock} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3 max-w-xl">
                    <h2 className="font-semibold text-sm">Block a mobile number</h2>
                    <p className="text-xs text-gray-500">
                        Blocks OTP login and signup for this number (in addition to per-user account lock).
                    </p>
                    <input
                        value={mobile}
                        onChange={e => setMobile(e.target.value)}
                        placeholder="10-digit mobile"
                        inputMode="numeric"
                        className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 font-mono"
                        required
                    />
                    <textarea
                        value={reason}
                        onChange={e => setReason(e.target.value)}
                        placeholder="Reason"
                        rows={2}
                        className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        required
                    />
                    <div className="flex gap-3 items-center">
                        <label className="text-xs text-gray-500">Duration (hours)</label>
                        <input
                            type="number"
                            min={1}
                            value={duration}
                            onChange={e => setDuration(e.target.value)}
                            className="w-24 px-2 py-1 text-sm border rounded dark:bg-gray-700"
                        />
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={permanent} onChange={e => setPermanent(e.target.checked)} />
                            Permanent
                        </label>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">
                        Block mobile
                    </button>
                </form>
                <form onSubmit={apply} className="flex gap-3 items-end">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Search mobile</label>
                        <input
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700"
                        />
                    </div>
                    <label className="flex items-center gap-2 text-sm pb-2">
                        <input type="checkbox" checked={activeOnly} onChange={e => setActiveOnly(e.target.checked)} />
                        Active only
                    </label>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
                        Filter
                    </button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">Mobile</th>
                                <th className="px-5 py-3">Reason</th>
                                <th className="px-5 py-3">Expires</th>
                                <th className="px-5 py-3">Permanent</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {blockedMobiles.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-gray-500">
                                        No blocked mobiles.
                                    </td>
                                </tr>
                            ) : (
                                blockedMobiles.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 font-mono text-xs">{row.mobile}</td>
                                        <td className="px-5 py-3">{row.reason || '—'}</td>
                                        <td className="px-5 py-3 text-xs">
                                            {row.expires_at ? new Date(row.expires_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3">{row.permanent ? 'Yes' : 'No'}</td>
                                        <td className="px-5 py-3">
                                            <button
                                                type="button"
                                                onClick={() => setUnblockTarget(row)}
                                                className="text-indigo-600 text-sm"
                                            >
                                                Unblock
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {blockedMobiles.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm">
                            {blockedMobiles.current_page > 1 && (
                                <Link
                                    href={`/panel/security/blocked-mobiles?page=${blockedMobiles.current_page - 1}&search=${encodeURIComponent(search)}&active_only=${activeOnly ? '1' : ''}`}
                                    preserveState
                                >
                                    Previous
                                </Link>
                            )}
                            <span />
                            {blockedMobiles.current_page < blockedMobiles.last_page && (
                                <Link
                                    href={`/panel/security/blocked-mobiles?page=${blockedMobiles.current_page + 1}&search=${encodeURIComponent(search)}&active_only=${activeOnly ? '1' : ''}`}
                                    preserveState
                                >
                                    Next
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
            <ConfirmDialog
                open={unblockTarget !== null}
                onClose={() => {
                    if (!unblocking) setUnblockTarget(null);
                }}
                onConfirm={confirmUnblock}
                title="Unblock mobile"
                message={
                    unblockTarget
                        ? `Are you sure you want to unblock ${unblockTarget.mobile}?`
                        : ''
                }
                confirmLabel="Unblock"
                variant="warning"
                loading={unblocking}
            />
        </AdminLayout>
    );
}
