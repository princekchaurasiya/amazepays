import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { formatAuditAction } from '@/lib/auditActionLabels';
import { FileText, Search } from 'lucide-react';

type LogRow = {
    id: number;
    action: string;
    user_id: number | null;
    ip_address?: string | null;
    created_at: string | null;
    user?: { name: string; email: string };
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
    logs: Paginated<LogRow>;
    filters: { search?: string; action?: string; user_id?: string; from?: string; to?: string };
};

export default function Index({ logs, filters }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [action, setAction] = useState(filters.action ?? '');
    const [userId, setUserId] = useState(filters.user_id ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    const applyFilters = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/panel/audit-logs',
            {
                search: search || undefined,
                action: action || undefined,
                user_id: userId || undefined,
                from: from || undefined,
                to: to || undefined,
            },
            { preserveState: true },
        );
    };

    const q = () =>
        `search=${encodeURIComponent(search)}&action=${encodeURIComponent(action)}&user_id=${encodeURIComponent(userId)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`;

    return (
        <AdminLayout>
            <Head title="Audit log" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Audit log' }]} />
                <div className="flex items-center gap-2">
                    <FileText className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Audit log</h1>
                </div>
                <form onSubmit={applyFilters} className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm items-end">
                    <div className="md:col-span-2">
                        <label className="block text-xs text-gray-500 mb-1">Search</label>
                        <div className="relative">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                value={search}
                                onChange={e => setSearch(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Action</label>
                        <input value={action} onChange={e => setAction(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">User ID</label>
                        <input value={userId} onChange={e => setUserId(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">From</label>
                        <input type="date" value={from} onChange={e => setFrom(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">To</label>
                        <input type="date" value={to} onChange={e => setTo(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg bg-gray-50 dark:bg-gray-700" />
                    </div>
                    <div className="md:col-span-3 lg:col-span-6">
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium">
                            Apply filters
                        </button>
                    </div>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">When</th>
                                <th className="px-5 py-3">Action</th>
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3">IP</th>
                                <th className="px-5 py-3 w-28 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-gray-500">No audit entries.</td>
                                </tr>
                            ) : (
                                logs.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td className="px-5 py-3 text-gray-500 whitespace-nowrap">
                                            {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3">
                                            {(() => {
                                                const { label, color } = formatAuditAction(row.action);
                                                return (
                                                    <span
                                                        className={`inline-block max-w-[220px] truncate rounded-full px-2.5 py-1 text-xs font-medium ${color}`}
                                                        title={row.action}
                                                    >
                                                        {label}
                                                    </span>
                                                );
                                            })()}
                                        </td>
                                        <td className="px-5 py-3">{row.user?.name || row.user?.email || (row.user_id ? `#${row.user_id}` : '—')}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.ip_address || '—'}</td>
                                        <td className="px-5 py-3 text-right">
                                            <ActionButtons viewHref={`/panel/audit-logs/${row.id}`} />
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {logs.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm text-gray-500">
                            <span>{logs.from}–{logs.to} of {logs.total}</span>
                            <div className="flex gap-2">
                                {logs.current_page > 1 && (
                                    <Link href={`/panel/audit-logs?page=${logs.current_page - 1}&${q()}`} preserveState className="px-3 py-1 rounded border dark:border-gray-600">
                                        Previous
                                    </Link>
                                )}
                                {logs.current_page < logs.last_page && (
                                    <Link href={`/panel/audit-logs?page=${logs.current_page + 1}&${q()}`} preserveState className="px-3 py-1 rounded border dark:border-gray-600">
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
