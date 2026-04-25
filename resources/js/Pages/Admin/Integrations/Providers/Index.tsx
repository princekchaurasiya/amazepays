import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function ProvidersIntegrationsIndex() {
    return (
        <AdminLayout>
            <Head title="Integrations — Providers" />
            <div className="p-6 space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Integrations — Providers</h1>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Central place for provider configuration and health checks.
                    </p>
                </div>

                <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        This is a Phase 5 placeholder. Use the existing Providers dashboard until this screen is wired.
                    </p>
                    <Link href="/panel/providers" className="mt-3 inline-block text-sm font-semibold text-brand-600 hover:underline">
                        Open Providers dashboard
                    </Link>
                </div>
            </div>
        </AdminLayout>
    );
}

