import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { paths } from '@/lib/paths';
import TermsLegalBody from '@/Pages/Storefront/TermsLegalBody';

export default function Terms() {
    const page = usePage<{ company?: { official_name?: string; address?: string; email?: string; website?: string } }>();
    const c = page.props.company;
    const legalName = c?.official_name ?? 'Frenetic India Services Private Limited';
    const rawAddr = (c?.address ?? '').trim();
    const addressForTerms = rawAddr.replace(/\.+\s*$/, '').trim() || '[registered office address — set COMPANY_ADDRESS in .env]';
    const supportEmail = c?.email ?? 'support@amazepays.in';
    const base = (c?.website ?? 'https://amazepays.in').replace(/\/+$/, '');
    const privacyAbsoluteUrl = `${base}${paths.privacy}`;
    const publicSiteUrl = base;

    return (
        <StorefrontLayout>
            <Head title="Terms of use" />
            <article className="mx-auto max-w-3xl px-4 py-10 text-gray-800">
                <h1 className="text-center text-3xl font-bold text-gray-900">Website Terms of Use</h1>
                <TermsLegalBody
                    legalName={legalName}
                    addressForTerms={addressForTerms}
                    supportEmail={supportEmail}
                    privacyAbsoluteUrl={privacyAbsoluteUrl}
                    publicSiteUrl={publicSiteUrl}
                />
                <p className="mt-10 text-sm text-gray-600">
                    Questions? Email{' '}
                    <a href={`mailto:${supportEmail}`} className="text-brand-600 hover:underline">
                        {supportEmail}
                    </a>
                    . See also our{' '}
                    <Link href={paths.privacy} className="text-brand-600 hover:underline">
                        privacy policy
                    </Link>
                    .
                </p>
            </article>
        </StorefrontLayout>
    );
}
