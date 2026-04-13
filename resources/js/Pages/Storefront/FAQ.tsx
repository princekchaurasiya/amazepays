import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';

const items: { q: string; a: string }[] = [
    {
        q: 'How do I receive my gift card?',
        a: 'After successful payment, voucher details are delivered by email/SMS where supported by the brand. You can also view eligible orders in your account.',
    },
    {
        q: 'What payments do you support?',
        a: 'We integrate with trusted gateways including UPI and cards. Available methods are shown at checkout.',
    },
    {
        q: 'Can I cancel an order?',
        a: 'Once a digital code is issued, cancellation may not be possible. For payment issues, contact support with your reference number.',
    },
    {
        q: 'Is my data secure?',
        a: 'We apply industry-standard practices for transport security and access control. See our privacy policy for details.',
    },
];

export default function FAQ() {
    return (
        <StorefrontLayout>
            <Head title="FAQ" />
            <div className="mx-auto max-w-3xl px-4 py-10">
                <h1 className="text-3xl font-bold text-gray-900">Frequently asked questions</h1>
                <dl className="mt-8 space-y-6">
                    {items.map((item) => (
                        <div key={item.q}>
                            <dt className="font-semibold text-gray-900">{item.q}</dt>
                            <dd className="mt-2 text-sm leading-relaxed text-gray-700">{item.a}</dd>
                        </div>
                    ))}
                </dl>
                <p className="mt-10 text-sm">
                    Still need help?{' '}
                    <Link href={paths.contact} className="text-brand-600 hover:underline">
                        Contact us
                    </Link>
                    .
                </p>
            </div>
        </StorefrontLayout>
    );
}
