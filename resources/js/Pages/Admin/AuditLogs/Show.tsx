import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs } from '@/Components/Admin';
import { formatAuditAction } from '@/lib/auditActionLabels';
import { ArrowLeft } from 'lucide-react';

type Log = {
    id: number;
    action: string;
    user_id: number | null;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    url: string | null;
    created_at: string | null;
    user?: { name: string; email: string };
};

type Props = {
    log: Log;
};

function JsonBlock({ data }: { data: unknown }) {
    if (data == null) {
        return <span className="text-gray-400">null</span>;
    }
    return (
        <pre className="text-xs bg-gray-100 dark:bg-gray-900 p-3 rounded-lg overflow-x-auto max-h-64 overflow-y-auto">
            {JSON.stringify(data, null, 2)}
        </pre>
    );
}

export default function Show({ log }: Props) {
    return (
        <AdminLayout>
            <Head title={`Audit #${log.id}`} />
            <div className="space-y-6 max-w-4xl">
                <Breadcrumbs items={[{ label: 'Audit log', href: '/panel/audit-logs' }, { label: `#${log.id}` }]} />
                <div className="flex items-center gap-4">
                    <Link href="/panel/audit-logs" className="text-gray-500 hover:text-indigo-600">
                        <ArrowLeft size={20} />
                    </Link>
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                            {formatAuditAction(log.action).label}
                        </h1>
                        <p className="mt-1 font-mono text-xs text-gray-500 dark:text-gray-400">{log.action}</p>
                    </div>
                </div>
                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6 space-y-4 text-sm">
                    <div className="grid md:grid-cols-2 gap-4">
                        <div>
                            <span className="text-gray-500">When</span>
                            <p>{log.created_at ? new Date(log.created_at).toLocaleString() : '—'}</p>
                        </div>
                        <div>
                            <span className="text-gray-500">User</span>
                            <p>{log.user?.name || log.user?.email || (log.user_id ? `#${log.user_id}` : '—')}</p>
                        </div>
                        <div>
                            <span className="text-gray-500">IP</span>
                            <p className="font-mono text-xs">{log.ip_address || '—'}</p>
                        </div>
                        <div>
                            <span className="text-gray-500">Auditable</span>
                            <p className="font-mono text-xs">
                                {log.auditable_type || '—'} {log.auditable_id != null ? `#${log.auditable_id}` : ''}
                            </p>
                        </div>
                    </div>
                    {log.url && (
                        <div>
                            <span className="text-gray-500">URL</span>
                            <p className="break-all text-xs">{log.url}</p>
                        </div>
                    )}
                    <div>
                        <span className="text-gray-500 block mb-2">Old values</span>
                        <JsonBlock data={log.old_values} />
                    </div>
                    <div>
                        <span className="text-gray-500 block mb-2">New values</span>
                        <JsonBlock data={log.new_values} />
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
