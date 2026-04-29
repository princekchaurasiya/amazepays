import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Home, RefreshCw } from 'lucide-react';

type Props = {
    status: number;
    message?: string;
    detail?: string | null;
};

function Illustration() {
    // Inline SVG so we don't depend on static assets.
    return (
        <svg viewBox="0 0 340 220" className="mx-auto h-44 w-full max-w-[320px]" aria-hidden="true">
            <defs>
                <linearGradient id="cloudFill" x1="0" x2="1" y1="0" y2="1">
                    <stop offset="0" stopColor="#E8EFFB" />
                    <stop offset="1" stopColor="#EEF2FF" />
                </linearGradient>
                <linearGradient id="accent" x1="0" x2="1" y1="0" y2="0">
                    <stop offset="0" stopColor="#94A3B8" stopOpacity="0.35" />
                    <stop offset="1" stopColor="#94A3B8" stopOpacity="0.15" />
                </linearGradient>
            </defs>

            <g opacity="0.85">
                <path
                    d="M122 161c-28 0-51-21-51-47 0-22 17-41 40-46 8-26 33-44 61-44 35 0 64 26 66 59 18 6 31 22 31 41 0 25-22 46-49 46H122z"
                    fill="url(#cloudFill)"
                />
                <path
                    d="M123 161h157c27 0 49-21 49-46 0-19-13-35-31-41-2-33-31-59-66-59-28 0-53 18-61 44-23 5-40 24-40 46 0 26 23 47 51 47z"
                    fill="none"
                    stroke="url(#accent)"
                    strokeWidth="2"
                />
            </g>

            <g transform="translate(132 70)">
                <rect x="0" y="20" width="76" height="74" rx="14" fill="#FFFFFF" stroke="#E2E8F0" />
                <rect x="12" y="34" width="52" height="10" rx="5" fill="#E2E8F0" />
                <rect x="12" y="52" width="52" height="10" rx="5" fill="#E2E8F0" />
                <rect x="12" y="70" width="36" height="10" rx="5" fill="#E2E8F0" />
                <circle cx="76" cy="18" r="16" fill="#F97316" opacity="0.9" />
                <path d="M76 10v9" stroke="#fff" strokeWidth="3.5" strokeLinecap="round" />
                <circle cx="76" cy="33" r="2.8" fill="#fff" />
            </g>
        </svg>
    );
}

export default function Error({ status, message, detail }: Props) {
    const title = status === 404 ? 'Page not found' : 'Something went wrong';
    const subtitle =
        status === 404
            ? "We couldn't find that page — please check the link and try again."
            : "We couldn't connect right now — please try again.";

    return (
        <div className="min-h-screen bg-gray-50 px-4 py-10">
            <Head title={String(status)} />

            <div className="mx-auto w-full max-w-2xl">
                <div className="rounded-2xl border border-gray-200 bg-white px-6 py-8 shadow-sm sm:px-10">
                    <Illustration />

                    <div className="mt-2 text-center">
                        <p className="text-xs font-semibold tracking-wider text-gray-500">
                            <span className="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5">
                                {status} × {status}
                            </span>
                        </p>

                        <h1 className="mt-4 text-2xl font-extrabold text-product-primary sm:text-3xl">{title} ⚠️</h1>
                        <p className="mt-2 text-sm text-gray-600">{subtitle}</p>

                        <div className="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="inline-flex items-center justify-center gap-2 rounded-lg bg-product-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-product-primary/90"
                            >
                                <RefreshCw className="h-4 w-4" aria-hidden="true" />
                                Retry
                            </button>

                            <Link
                                href="/"
                                className="inline-flex items-center justify-center gap-2 rounded-lg border border-product-primary/30 bg-white px-5 py-2.5 text-sm font-semibold text-product-primary transition hover:bg-product-primary/5"
                            >
                                <Home className="h-4 w-4" aria-hidden="true" />
                                Go to Home
                            </Link>
                        </div>

                        <div className="mt-4">
                            {message ? <p className="text-xs text-gray-500">{message}</p> : null}
                            {detail ? (
                                <p className="mt-2 text-[11px] text-gray-500">
                                    <span className="font-medium text-gray-600">Error:</span> {detail}
                                </p>
                            ) : null}
                        </div>

                        <Link href="/contact-us" className="mt-6 inline-flex text-xs font-semibold text-product-primary hover:underline">
                            Contact Support →
                        </Link>
                    </div>
                </div>

                <p className="mt-6 text-center text-[11px] text-gray-500">
                    If this keeps happening, please check your server and database connection.
                </p>
            </div>
        </div>
    );
}
