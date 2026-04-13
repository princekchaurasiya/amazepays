import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

/** Admin VD/EVC tooling: show structured props as JSON (legacy Blade replacement). */
export default function VdDataPage({ title, data }: { title: string; data: unknown }) {
    return (
        <AdminLayout>
            <Head title={title} />
            <div className="space-y-4 p-6">
                <h1 className="text-xl font-bold text-gray-900">{title}</h1>
                <pre className="max-h-[70vh] overflow-auto rounded-lg border border-gray-200 bg-gray-900 p-4 text-xs text-green-100">
                    {JSON.stringify(data, null, 2)}
                </pre>
            </div>
        </AdminLayout>
    );
}
