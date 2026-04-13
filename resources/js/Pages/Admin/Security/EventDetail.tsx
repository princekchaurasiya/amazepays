import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft } from 'lucide-react';

type UserLite = { id: number; name?: string; email?: string };
type EventT = {
    id: number;
    event_type: string;
    severity: string;
    ip_address: string | null;
    user_agent: string | null;
    request_url: string | null;
    country_code: string | null;
    metadata: Record<string, unknown> | null;
    resolved: boolean;
    resolution_note: string | null;
    resolved_at: string | null;
    created_at: string | null;
    user?: UserLite | null;
    resolved_by?: UserLite | null;
};

type Props = {
    event: EventT;
};

export default function EventDetail({ event }: Props) {
    const [note, setNote] = useState('');

    return (
        <AdminLayout>
            <Head title={`Security event #${event.id}`} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs items={[{ label: 'Security', href: '/panel/security' }, { label: 'Events', href: '/panel/security/events' }, { label: `#${event.id}` }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/security/events" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <h1 className="text-xl font-bold font-mono text-gray-900 dark:text-white">{event.event_type}</h1>
                    <span className="text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700">{event.severity}</span>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-3 text-sm">
                    <p><span className="text-gray-500">When:</span> {event.created_at ? new Date(event.created_at).toLocaleString() : '—'}</p>
                    <p><span className="text-gray-500">IP:</span> <code>{event.ip_address || '—'}</code></p>
                    <p><span className="text-gray-500">User:</span> {event.user?.email || event.user?.name || '—'}</p>
                    {event.request_url && (
                        <p className="break-all"><span className="text-gray-500">URL:</span> {event.request_url}</p>
                    )}
                    {event.metadata && (
                        <pre className="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto max-h-48">
                            {JSON.stringify(event.metadata, null, 2)}
                        </pre>
                    )}
                    {event.resolved ? (
                        <div className="pt-4 border-t dark:border-gray-700">
                            <p className="text-green-600 font-medium">Resolved</p>
                            <p className="text-xs text-gray-500">{event.resolved_at && new Date(event.resolved_at).toLocaleString()}</p>
                            <p>{event.resolution_note || '—'}</p>
                            {event.resolved_by && (
                                <p className="text-xs">By: {event.resolved_by.email || event.resolved_by.name}</p>
                            )}
                        </div>
                    ) : (
                        <div className="pt-4 border-t dark:border-gray-700 space-y-2">
                            <label className="block text-xs text-gray-500">Resolution note</label>
                            <textarea value={note} onChange={e => setNote(e.target.value)} rows={3} className="w-full px-3 py-2 text-sm border rounded-lg dark:bg-gray-700" />
                            <button
                                type="button"
                                onClick={() => router.post(`/panel/security/events/${event.id}/resolve`, { note: note || null })}
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm"
                            >
                                Mark resolved
                            </button>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
