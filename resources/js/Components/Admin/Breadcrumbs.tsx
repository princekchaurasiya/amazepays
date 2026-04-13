import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight, Home } from 'lucide-react';

export type BreadcrumbItem = {
    label: string;
    href?: string;
};

type Props = {
    items: BreadcrumbItem[];
};

export default function Breadcrumbs({ items }: Props) {
    return (
        <nav className="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4" aria-label="Breadcrumb">
            <Link
                href="/panel"
                className="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
            >
                <Home size={14} />
            </Link>

            {items.map((item, index) => (
                <React.Fragment key={index}>
                    <ChevronRight size={14} className="mx-2 text-gray-300 dark:text-gray-600 flex-shrink-0" />
                    {item.href && index < items.length - 1 ? (
                        <Link
                            href={item.href}
                            className="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors truncate"
                        >
                            {item.label}
                        </Link>
                    ) : (
                        <span className="text-gray-900 dark:text-white font-medium truncate">
                            {item.label}
                        </span>
                    )}
                </React.Fragment>
            ))}
        </nav>
    );
}
