import React from 'react';
import { Head } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

/** Placeholder: gift email HTML is rendered server-side for real sends. */
export default function GiftMailPreview() {
    return (
        <StorefrontLayout>
            <Head title="Gift email" />
            <div className="mx-auto max-w-lg px-4 py-16 text-center text-sm text-gray-600">
                <p>This path is reserved for the gift-card email HTML template.</p>
                <p className="mt-4">Real emails are sent with order and card data from the server.</p>
            </div>
        </StorefrontLayout>
    );
}
