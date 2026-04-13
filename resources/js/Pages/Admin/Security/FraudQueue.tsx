import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { AlertTriangle, Eye } from 'lucide-react';

type EventRow = {
    id: number;
    event_type: string;
    severity: string;
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
};

export default function FraudQueue({ events }: Props) {
    return (
        <AdminLayout>
            <Head title="Fraud queue" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Security', href: '/panel/security' }, { label: 'Fraud queue' }]} />
                <div className="flex items-center gap-2">
                    <AlertTriangle className="text-amber-500" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Fraud queue</h1>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="text-left text-gray-500 bg-gray-50 dark:bg-gray-700/50">
                                <th className="px-5 py-3">When</th>
                                <th className="px-5 py-3">Type</th>
                                <th className="px-5 py-3">Severity</th>
                                <th className="px-5 py-3">User</th>
                                <th className="px-5 py-3" />
                            </tr>
                        </thead>
                        <tbody>
                            {events.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-5 py-12 text-center text-gray-500">Queue is empty.</td>
                                </tr>
                            ) : (
                                events.data.map(row => (
                                    <tr key={row.id} className="border-t dark:border-gray-700">
                                        <td className="px-5 py-3 whitespace-nowrap text-gray-500">
                                            {row.created_at ? new Date(row.created_at).toLocaleString() : '—'}
                                        </td>
                                        <td className="px-5 py-3 font-mono text-xs">{row.event_type}</td>
                                        <td className="px-5 py-3">{row.severity}</td>
                                        <td className="px-5 py-3">{row.user?.email || '—'}</td>
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
                                <Link href={`/panel/security/fraud-queue?page=${events.current_page - 1}`} preserveState>
                                    Previous
                                </Link>
                            )}
                            <span />
                            {events.current_page < events.last_page && (
                                <Link href={`/panel/security/fraud-queue?page=${events.current_page + 1}`} preserveState>
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
