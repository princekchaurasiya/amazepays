import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function WoohooIndex({
    host,
    configured,
    processingUrl,
}: {
    host: string | null;
    configured: boolean;
    processingUrl: string;
}) {
    return (
        <AdminLayout>
            <Head title="Woohoo" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900">Woohoo</h1>
                <p className="mt-2 text-sm text-gray-600">
                    API host: <span className="font-mono text-gray-900 dark:text-gray-100">{host || '—'}</span>
                </p>
                <p className="mt-2 text-sm">
                    Configuration:{' '}
                    <span className={configured ? 'font-medium text-emerald-700' : 'font-medium text-amber-700'}>
                        {configured ? 'Host + secret + bearer present' : 'Incomplete — check config/woohoo.php & env'}
                    </span>
                </p>
                <div className="mt-6 space-y-2 text-sm">
                    <p>
                        Processing page:{' '}
                        <a href={processingUrl} className="text-indigo-600 hover:underline dark:text-indigo-400">
                            {processingUrl}
                        </a>
                    </p>
                </div>
                <p className="mt-8">
                    <Link href="/panel/providers" className="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-300">
                        &larr; Providers hub
                    </Link>
                </p>
            </div>
        </AdminLayout>
    );
}
