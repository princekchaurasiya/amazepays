import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StorefrontLayout from '@/Layouts/StorefrontLayout';
import { usePage } from '@inertiajs/react';
import { paths } from '@/lib/paths';

export default function About() {
    const page = usePage<{ company?: { official_name?: string; address?: string; website?: string; about_link?: string } }>();
    const c = page.props.company;

    return (
        <StorefrontLayout>
            <Head title="About us" />
            <article className="mx-auto max-w-3xl px-4 py-10 prose prose-gray">
                <h1 className="text-3xl font-bold text-gray-900">About AmazePays</h1>
                <p className="mt-4 text-gray-700">
                    Welcome to{' '}
                    <a href={c?.website ?? '#'} className="text-brand-600 hover:underline" target="_blank" rel="noreferrer">
                        AmazePays
                    </a>
                    . The website is owned and operated by <strong>{c?.official_name}</strong> with its registered office at{' '}
                    {c?.address}.
                </p>
                <h2 className="mt-10 text-xl font-semibold text-gray-900">What we do</h2>
                <p className="text-gray-700">
                    We make gifting simple with digital gift cards and vouchers from leading brands. Our mission is to help you
                    celebrate every occasion with a seamless, secure purchase experience.
                </p>
                <p className="mt-4 text-gray-700">
                    Browse categories, discover offers, and send instant gifts with trusted payment partners.
                </p>
                <p className="mt-8 text-sm">
                    <Link href={paths.contact} className="text-brand-600 hover:underline">
                        Contact us
                    </Link>
                </p>
            </article>
        </StorefrontLayout>
    );
}
