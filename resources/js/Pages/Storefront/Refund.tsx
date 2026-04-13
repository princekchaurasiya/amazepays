import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { usePage } from '@inertiajs/react';
import { paths } from '@/lib/paths';

export default function Refund() {
    const page = usePage<{ company?: { email?: string; official_name?: string } }>();
    const c = page.props.company;

    return (
        <StorefrontLayout>
            <Head title="Refund policy" />
            <article className="mx-auto max-w-3xl px-4 py-10 text-gray-800">
                <h1 className="text-3xl font-bold text-gray-900">Refund &amp; cancellation</h1>
                <p className="mt-4 text-sm leading-relaxed">
                    Digital gift cards and vouchers are generally non-refundable once codes are issued or revealed, except where
                    required by law or where a partner programme explicitly allows cancellation before activation.
                </p>
                <h2 className="mt-8 text-xl font-semibold text-gray-900">Failed or duplicate charges</h2>
                <p className="mt-2 text-sm leading-relaxed">
                    If a payment succeeds but an order cannot be fulfilled, we will work with the payment provider and brand
                    partner to reverse or re-issue value. Please contact support with your order reference.
                </p>
                <h2 className="mt-8 text-xl font-semibold text-gray-900">Chargebacks</h2>
                <p className="mt-2 text-sm leading-relaxed">
                    Raising a chargeback without contacting us may delay resolution. We recommend emailing{' '}
                    <a href={`mailto:${c?.email}`} className="text-brand-600 hover:underline">
                        {c?.email}
                    </a>{' '}
                    first with transaction details.
                </p>
                <p className="mt-8 text-sm">
                    <Link href={paths.contact} className="text-brand-600 hover:underline">
                        Contact support
                    </Link>
                </p>
            </article>
        </StorefrontLayout>
    );
}
