import React, { FormEvent, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { Shield, Eye } from 'lucide-react';

type EventRow = {
    id: number;
    event_type: string;
    severity: string;
    ip_address: string | null;
    resolved: boolean;
    created_at: string | null;
    user?: { email?: string };
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
    events: Paginated<EventRow>;
    filters: {
        event_type?: string;
        severity?: string;
        ip_filter?: string;
        from?: string;
        to?: string;
        unresolved_only?: boolean;
    };
};

export default function Events({ events, filters }: Props) {
    const [eventType, setEventType] = useState(filters.event_type ?? '');
    const [severity, setSeverity] = useState(filters.severity ?? '');
    const [ipFilter, setIpFilter] = useState(filters.ip_filter ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');
    const [unresolvedOnly, setUnresolvedOnly] = useState(Boolean(filters.unresolved_only));

    const apply = (e?: FormEvent) => {
        e?.preventDefault();
        router.get(
            '/panel/security/events',
            {
                event_type: eventType || undefined,
                severity: severity || undefined,
                ip_filter: ipFilter || undefined,
                from: from || undefined,
                to: to || undefined,
                unresolved_only: unresolvedOnly || undefined,
            },
            { preserveState: true },
        );
    };

    const q = () =>
        `event_type=${encodeURIComponent(eventType)}&severity=${encodeURIComponent(severity)}&ip_filter=${encodeURIComponent(ipFilter)}&from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}&unresolved_only=${unresolvedOnly ? '1' : ''}`;

    return (
        <AdminLayout>
            <Head title="Security events" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Security', href: '/panel/security' }, { label: 'Events' }]} />
                <div className="flex items-center gap-2">
                    <Shield className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Security events</h1>
                </div>
                <form onSubmit={apply} className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 bg-white dark:bg-gray-800 p-4 rounded-xl shadow-sm items-end">
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Event type</label>
                        <input value={eventType} onChange={e => setEventType(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">Severity</label>
                        <select value={severity} onChange={e => setSeverity(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700">
                            <option value="">All</option>
                            <option value="info">info</option>
                            <option value="low">low</option>
                            <option value="medium">medium</option>
                            <option value="high">high</option>
                            <option value="critical">critical</option>
                        </select>
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">IP</label>
                        <input value={ipFilter} onChange={e => setIpFilter(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700 font-mono" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">From</label>
                        <input type="date" value={from} onChange={e => setFrom(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700" />
                    </div>
                    <div>
                        <label className="block text-xs text-gray-500 mb-1">To</label>
                        <input type="date" value={to} onChange={e => setTo(e.target.value)} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700" />
                    </div>
                    <label className="flex items-center gap-2 text-sm pb-2">
                        <input type="checkbox" checked={unresolvedOnly} onChange={e => setUnresolvedOnly(e.target.checked)} />
                        Unresolved only
                    </label>
                    <div className="md:col-span-3 lg:col-span-6">
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
                            Apply
                        </button>
                    </div>
                </form>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">When</th>
                                <th className="px-5 py-3">Type</th>
                                <th className="px-5 py-3">Severity</th>
                                <th className="px-5 py-3">IP</th>
                                <th className="px-5 py-3">Resolved</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {events.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-5 py-12 text-center text-gray-500">No events.</td>
                                </tr>
                            ) : (
                                events.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 whitespace-nowrap text-gray-500">
                                            {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.event_type}</td>
                                        <td className="px-5 py-3">{row.severity}</td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.ip_address || '—'}</td>
                                        <td className="px-5 py-3">{row.resolved ? 'Yes' : 'No'}</td>
                                        <td className="px-5 py-3">
                                            <Link href={`/panel/security/events/${row.id}`} className="inline-flex items-center gap-1 text-indigo-600 text-sm">
                                                <Eye size={14} /> View
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                    {events.last_page > 1 && (
                        <div className="flex justify-between px-5 py-3 border-t text-sm">
                            {events.current_page > 1 && (
                                <Link href={`/panel/security/events?page=${events.current_page - 1}&${q()}`} preserveState className="text-indigo-600">
                                    Previous
                                </Link>
                            )}
                            <span />
                            {events.current_page < events.last_page && (
                                <Link href={`/panel/security/events?page=${events.current_page + 1}&${q()}`} preserveState className="text-indigo-600">
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
