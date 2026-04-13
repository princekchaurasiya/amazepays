import React from 'react';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';
import PrivacyLegalBody from '@/Pages/Storefront/PrivacyLegalBody';

export default function Privacy() {
    const page = usePage<{ company?: { official_name?: string; address?: string; email?: string; website?: string } }>();
    const c = page.props.company;
    const legalNameFull = c?.official_name ?? 'Frenetic India Services Private Limited';
    const legalNameShort = 'Frenetic India Private Limited';
    const registeredAddress =
        (c?.address ?? '').trim() || '[registered office address — set COMPANY_ADDRESS in .env]';
    const supportEmail = c?.email ?? 'support@amazepays.in';
    const base = (c?.website ?? 'https://amazepays.in').replace(/\/+$/, '');
    const privacyAbsoluteUrl = `${base}${paths.privacy}`;

    return (
        <StorefrontLayout>
            <Head title="Privacy policy" />
            <article className="mx-auto max-w-3xl px-4 py-10 text-gray-800">
                <h1 className="text-center text-3xl font-bold text-gray-900">Privacy Policy</h1>
                <p className="mt-4 text-sm leading-relaxed">
                    <strong>{legalNameFull}</strong> (&quot;we&quot;) respects your privacy. This policy describes how we handle
                    personal data when you use AmazePays, in line with applicable Indian law including the Information Technology Act,
                    2000 and related rules.
                </p>
                <PrivacyLegalBody
                    legalNameShort={legalNameShort}
                    registeredAddress={registeredAddress}
                    supportEmail={supportEmail}
                    privacyAbsoluteUrl={privacyAbsoluteUrl}
                />
            </article>
        </StorefrontLayout>
    );
}
