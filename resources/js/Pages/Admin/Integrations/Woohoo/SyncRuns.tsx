import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function WoohooSyncRuns() {
    return (
        <AdminLayout>
            <Head title="Integrations — Woohoo sync runs" />
            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Woohoo sync runs</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Phase 5 placeholder for provider sync history.</p>
                </div>
            </div>
        </AdminLayout>
    );
}

