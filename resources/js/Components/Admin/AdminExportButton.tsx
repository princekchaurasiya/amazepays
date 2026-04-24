import React from 'react';
import { usePage } from '@inertiajs/react';
import { Download } from 'lucide-react';

type Props = {
    href: string;
    label?: string;
    requiredPermission?: string;
    disabled?: boolean;
    className?: string;
};

export default function AdminExportButton({
    href,
    label = 'Export',
    requiredPermission,
    disabled = false,
    className = '',
}: Props) {
    const page = usePage<{ auth?: { user?: { permissions?: string[] } } }>();
    const permissions = page.props.auth?.user?.permissions ?? [];

    if (requiredPermission && !permissions.includes(requiredPermission)) {
        return null;
    }

    const isDisabled = disabled || !href;

    return (
        <a
            href={isDisabled ? undefined : href}
            aria-disabled={isDisabled}
            className={[
                'inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                isDisabled ? 'pointer-events-none opacity-50' : '',
                className,
            ].join(' ')}
        >
            <Download className="h-4 w-4" />
            {label}
        </a>
    );
}

