import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

export default function RewardsCatalog() {
    return (
        <AdminLayout>
            <Head title="Promotions — Rewards catalog" />
            <div className="p-6">
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Rewards catalog</h1>
                <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Phase 5 placeholder. This screen will manage loyalty rewards catalog items.
                </p>
            </div>
        </AdminLayout>
    );
}

