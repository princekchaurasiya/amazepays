import React from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { usePage } from '@inertiajs/react';

export default function Invoice() {
    const page = usePage<{ company?: { official_name?: string; address?: string; email?: string } }>();
    const c = page.props.company;

    return (
        <StorefrontLayout>
            <Head title="Invoice" />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">Invoice</h1>
                <p className="mt-4 text-sm text-gray-600">
                    {c?.official_name}
                    <br />
                    {c?.address}
                    <br />
                    {c?.email}
                </p>
                <p className="mt-6 text-sm text-gray-500">Use the admin or order confirmation email for downloadable invoices.</p>
            </div>
        </StorefrontLayout>
    );
}
