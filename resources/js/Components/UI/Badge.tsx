import React from 'react';

type Tone = 'success' | 'warning' | 'danger' | 'neutral' | 'brand';

const tones: Record<Tone, string> = {
    success: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
    warning: 'bg-amber-100 text-amber-900 dark:bg-amber-900/30 dark:text-amber-100',
    danger: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
    neutral: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    brand: 'bg-blue-50 text-product-primary dark:bg-blue-900/30 dark:text-blue-100',
};

export default function Badge({
    tone = 'neutral',
    className,
    ...props
}: React.HTMLAttributes<HTMLSpanElement> & { tone?: Tone }) {
    return (
        <span
            className={[
                'inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold',
                tones[tone],
                className,
            ]
                .filter(Boolean)
                .join(' ')}
            {...props}
        />
    );
}

