import React from 'react';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';

type Prefill = Record<string, string | undefined>;

export default function EvcRequestCreate({ prefill = {} }: { prefill?: Prefill }) {
    const page = usePage<{ i18n?: { storefront?: { product?: Record<string, string> } } }>();
    const text = page.props.i18n?.storefront?.product ?? {};

    return (
        <StorefrontLayout>
            <Head title={text.evcCheckoutTitle ?? 'EVC checkout'} />
            <div className="mx-auto max-w-xl px-4 py-10">
                <h1 className="text-2xl font-bold text-gray-900">{text.evcCheckoutTitle ?? 'EVC checkout'}</h1>
                <p className="mt-2 text-sm text-gray-600">
                    {text.evcCheckoutRetiredMessage ??
                        'This flow has been retired. Please use the standard checkout flow from the product page.'}
                </p>
            </div>
        </StorefrontLayout>
    );
}
