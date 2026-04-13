import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Ban, FileQuestion, Home, PanelLeft } from 'lucide-react';

type Props = {
    status: number;
    message?: string;
};

export default function Error({ status, message }: Props) {
    const is403 = status === 403;
    const is404 = status === 404;
    const title = is403 ? 'Access denied' : is404 ? 'Page not found' : 'Something went wrong';
    const defaultMessage = is403
        ? 'You do not have permission to view this page.'
        : is404
          ? 'The page you requested could not be found.'
          : 'An unexpected error occurred. Please try again later.';
    const Icon = is403 ? Ban : is404 ? FileQuestion : AlertTriangle;
    const iconColor = is403 ? 'text-amber-500' : is404 ? 'text-gray-400' : 'text-red-500';

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 px-4">
            <Head title={String(status)} />
            <div className="max-w-md w-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-8 text-center">
                <div className={`inline-flex p-4 rounded-full bg-gray-100 dark:bg-gray-700 mb-4 ${iconColor}`}>
                    <Icon size={40} />
                </div>
                <p className="text-sm font-semibold text-indigo-600 dark:text-indigo-400 tracking-wide uppercase">
                    Error {status}
                </p>
                <h1 className="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{title}</h1>
                <p className="mt-3 text-gray-600 dark:text-gray-400">{message || defaultMessage}</p>
                <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        href="/"
                        className="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium"
                    >
                        <Home size={18} />
                        Home
                    </Link>
                    <Link
                        href="/panel"
                        className="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 text-sm font-medium"
                    >
                        <PanelLeft size={18} />
                        Admin panel
                    </Link>
                </div>
            </div>
        </div>
    );
}
