import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function ValueDesignIndex({
    legacyBrandsUrl,
    legacyDashboardUrl,
    testConnectionUrl,
}: {
    legacyBrandsUrl: string;
    legacyDashboardUrl: string;
    testConnectionUrl: string;
}) {
    return (
        <AdminLayout>
            <Head title="Value Design" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900">Value Design</h1>
                <p className="mt-2 max-w-2xl text-sm text-gray-600">
                    Operational tools for VD brands and EVC flows still live on legacy admin URLs. This page links the existing
                    Blade tools until they are fully migrated.
                </p>
                <ul className="mt-6 space-y-3 text-sm">
                    <li>
                        <a href={legacyBrandsUrl} className="text-indigo-600 hover:underline dark:text-indigo-400">
                            VD brands (legacy)
                        </a>
                    </li>
                    <li>
                        <a href={legacyDashboardUrl} className="text-indigo-600 hover:underline dark:text-indigo-400">
                            VD dashboard (legacy)
                        </a>
                    </li>
                    <li>
                        <a href={testConnectionUrl} className="text-indigo-600 hover:underline dark:text-indigo-400">
                            Test VD connection
                        </a>
                    </li>
                </ul>
                <p className="mt-8">
                    <Link href="/panel/providers" className="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-300">
                        &larr; Providers hub
                    </Link>
                </p>
            </div>
        </AdminLayout>
    );
}
