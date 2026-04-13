import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { Shield } from 'lucide-react';

type BlockRow = {
    id: number;
    ip_address: string;
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
    blockedIps: Paginated<BlockRow>;
    filters: { search?: string; active_only?: boolean };
};

export default function BlockedIps({ blockedIps, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [activeOnly, setActiveOnly] = useState(Boolean(filters.active_only));
    const [ipAddress, setIpAddress] = useState('');
    const [reason, setReason] = useState('');
    const [duration, setDuration] = useState('24');
    const [permanent, setPermanent] = useState(false);

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get('/panel/security/blocked-ips', { search: search || undefined, active_only: activeOnly || undefined }, { preserveState: true });
    };

    const submitBlock = (e: FormEvent) => {
        e.preventDefault();
        router.post('/panel/security/blocked-ips', {
            ip_address: ipAddress,
            reason,
            duration: Number(duration),
            permanent,
        });
    };

    return (
        <AdminLayout>
            <Head title="Blocked IPs" />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs items={[{ label: 'Security', href: '/panel/security' }, { label: 'Blocked IPs' }]} />
                <div className="flex items-center gap-2">
                    <Shield className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Blocked IPs</h1>
                </div>
                <form onSubmit={submitBlock} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3 max-w-xl">
                    <h2 className="font-semibold text-sm">Block an IP</h2>
                    <input
                        value={ipAddress}
                        onChange={e => setIpAddress(e.target.value)}
                        placeholder="IP address"
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
                        <input type="number" min={1} value={duration} onChange={e => setDuration(e.target.value)} className="w-24 px-2 py-1 text-sm border rounded dark:bg-gray-700" />
                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" checked={permanent} onChange={e => setPermanent(e.target.checked)} />
                            Permanent
                        </label>
                    </div>
                    <button type="submit" className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm">Block IP</button>
                </form>
                <form onSubmit={apply} className="flex gap-3 items-end">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Search IP</label>
                        <input value={search} onChange={e => setSearch(e.target.value)} className="px-3 py-2 text-sm border rounded-lg dark:bg-gray-700" />
                    </div>
                    <label className="flex items-center gap-2 text-sm pb-2">
                        <input type="checkbox" checked={activeOnly} onChange={e => setActiveOnly(e.target.checked)} />
                        Active only
                    </label>
                    <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">Filter</button>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">IP</th>
                                <th className="px-5 py-3">Reason</th>
                                <th className="px-5 py-3">Expires</th>
                                <th className="px-5 py-3">Permanent</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {blockedIps.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-gray-500">No blocked IPs.</td>
                                </tr>
                            ) : (
                                blockedIps.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 font-mono text-xs">{row.ip_address}</td>
                                        <td className="px-5 py-3">{row.reason || '—'}</td>
                                        <td className="px-5 py-3 text-xs">{row.expires_at ? new Date(row.expires_at).toLocaleString() : '—'}</td>
                                        <td className="px-5 py-3">{row.permanent ? 'Yes' : 'No'}</td>
                                        <td className="px-5 py-3">
                                            <button
                                                type="button"
                                                onClick={() => {
                                                    if (confirm(`Unblock ${row.ip_address}?`)) {
                                                        router.delete(`/panel/security/blocked-ips/${encodeURIComponent(row.ip_address)}`);
                                                    }
                                                }}
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
                    {blockedIps.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm">
                            {blockedIps.current_page > 1 && (
                                <Link
                                    href={`/panel/security/blocked-ips?page=${blockedIps.current_page - 1}&search=${encodeURIComponent(search)}&active_only=${activeOnly ? '1' : ''}`}
                                    preserveState
                                >
                                    Previous
                                </Link>
                            )}
                            <span />
                            {blockedIps.current_page < blockedIps.last_page && (
                                <Link
                                    href={`/panel/security/blocked-ips?page=${blockedIps.current_page + 1}&search=${encodeURIComponent(search)}&active_only=${activeOnly ? '1' : ''}`}
                                    preserveState
                                >
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
