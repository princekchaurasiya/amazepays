import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function Campaigns() {
    return (
        <AdminLayout>
            <Head title="Promotions — Campaigns" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900">Campaigns</h1>
                <p className="mt-2 text-sm text-gray-600">Phase 5 placeholder. Wire this to promotion campaigns when the pricing funnel is finalized.</p>
            </div>
        </AdminLayout>
    );
}

