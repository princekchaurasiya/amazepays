import React from 'react';

type Props = React.SelectHTMLAttributes<HTMLSelectElement> & {
    label?: string;
    hint?: string;
    error?: string;
};

export default function Select({ label, hint, error, className, children, ...props }: Props) {
    return (
        <label className="block">
            {label ? <span className="text-xs font-semibold uppercase tracking-wide text-gray-500">{label}</span> : null}
            <select
                className={[
                    'mt-2 h-12 w-full rounded-xl border bg-white px-4 text-sm text-gray-900 shadow-sm outline-none transition',
                    'border-gray-300 focus:border-product-primary focus:ring-2 focus:ring-product-primary/20',
                    error ? 'border-red-400 focus:border-red-500 focus:ring-red-200' : '',
                    className,
                ]
                    .filter(Boolean)
                    .join(' ')}
                {...props}
            >
                {children}
            </select>
            {error ? <p className="mt-2 text-xs text-red-600">{error}</p> : hint ? <p className="mt-2 text-xs text-gray-500">{hint}</p> : null}
        </label>
    );
}

