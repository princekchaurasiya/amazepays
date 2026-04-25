import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function WoohooShow() {
    return (
        <AdminLayout>
            <Head title="Integrations — Woohoo" />
            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Woohoo integration</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Phase 5 placeholder screen.</p>
                </div>

                <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        Use the existing Woohoo admin page until this integration workspace is wired.
                    </p>
                    <Link href="/panel/woohoo-admin" className="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">
                        Open existing Woohoo admin
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}

