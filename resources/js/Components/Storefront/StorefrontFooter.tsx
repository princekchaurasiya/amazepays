import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { paths } from '@/lib/paths';

type Company = {
    official_name?: string;
    address?: string;
    email?: string;
    cin?: string;
    about_link?: string;
};

type Social = { facebook?: string; linkedin?: string; instagram?: string };

export default function StorefrontFooter() {
    const page = usePage<{ company?: Company; social?: Social }>();
    const company = page.props.company ?? {};
    const social = page.props.social ?? {};

    return (
        <footer className="mt-auto border-t border-gray-200 bg-white">
            <div className="mx-auto max-w-7xl px-4 py-10">
                <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <Link href={paths.home} className="inline-block">
                            <img src="/images/logo.png" alt="" className="h-10 w-auto" />
                        </Link>
                        <p className="mt-4 text-sm text-gray-700">
                            <strong>Company:</strong> {company.official_name ?? 'Frenetic India Services Private Limited'}
                            <br />
                            <strong>CIN:</strong> {company.cin ?? '—'}
                            <br />
                            {company.address ?? ''}
                        </p>
                    </div>
                    <div>
                        <h5 className="mb-3 text-sm font-semibold uppercase tracking-wide text-orange-600">Quick read</h5>
                        <ul className="space-y-2 text-sm text-gray-800">
                            <li>
                                <Link href={paths.terms} className="hover:text-brand-600">
                                    Terms of use
                                </Link>
                            </li>
                            <li>
                                <Link href={paths.privacy} className="hover:text-brand-600">
                                    Privacy policy
                                </Link>
                            </li>
                            <li>
                                <Link href={paths.refund} className="hover:text-brand-600">
                                    Refund policy
                                </Link>
                            </li>
                            <li>
                                <a href={company.about_link ?? '#'} className="hover:text-brand-600" target="_blank" rel="noreferrer">
                                    Who we are?
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h5 className="mb-3 text-sm font-semibold uppercase tracking-wide text-orange-600">Easy guide</h5>
                        <ul className="space-y-2 text-sm text-gray-800">
                            <li>
                                <Link href={paths.home} className="hover:text-brand-600">
                                    Home
                                </Link>
                            </li>
                            <li>
                                <Link href={paths.about} className="hover:text-brand-600">
                                    About
                                </Link>
                            </li>
                            <li>
                                <Link href={paths.contact} className="hover:text-brand-600">
                                    Contact us
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h5 className="mb-3 text-sm font-semibold uppercase tracking-wide text-orange-600">Contact</h5>
                        <ul className="flex gap-3 text-gray-600">
                            <li>
                                <a href={social.facebook} target="_blank" rel="noreferrer" className="hover:text-brand-600">
                                    Facebook
                                </a>
                            </li>
                            <li>
                                <a href={social.linkedin} target="_blank" rel="noreferrer" className="hover:text-brand-600">
                                    LinkedIn
                                </a>
                            </li>
                            <li>
                                <a href={social.instagram} target="_blank" rel="noreferrer" className="hover:text-brand-600">
                                    Instagram
                                </a>
                            </li>
                        </ul>
                        <a href={`mailto:${company.email ?? 'support@amazepays.in'}`} className="mt-3 block text-sm text-gray-800 hover:text-brand-600">
                            {company.email ?? 'support@amazepays.in'}
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    );
}
