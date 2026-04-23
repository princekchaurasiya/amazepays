import React from 'react';

const STYLES: Record<string, string> = {
    b2c: 'bg-sky-100 text-sky-800 dark:bg-sky-900/35 dark:text-sky-300',
    b2b: 'bg-amber-100 text-amber-900 dark:bg-amber-900/35 dark:text-amber-200',
    both: 'bg-violet-100 text-violet-800 dark:bg-violet-900/35 dark:text-violet-300',
};

const LABELS: Record<string, string> = {
    b2c: 'B2C',
    b2b: 'B2B',
    both: 'Both',
};

type Props = {
    audience: string | null | undefined;
    className?: string;
};

export default function CatalogAudienceBadge({ audience, className = '' }: Props) {
    const key = typeof audience === 'string' ? audience.toLowerCase() : '';
    const label = LABELS[key] ?? (key ? key.toUpperCase() : '—');
    const style =
        STYLES[key] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';

    return (
        <span
            className={`inline-flex items-center text-xs px-2.5 py-0.5 rounded-full font-medium whitespace-nowrap ${style} ${className}`.trim()}
        >
            {label}
        </span>
    );
}
