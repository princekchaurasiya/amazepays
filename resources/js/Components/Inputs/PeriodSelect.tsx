import React from 'react';

export type PeriodOption = {
    value: number;
    label?: string;
};

export default function PeriodSelect({
    id,
    label = 'Period',
    value,
    options,
    onChange,
    className = '',
}: {
    id: string;
    label?: string;
    value: number;
    options: Array<number | PeriodOption>;
    onChange: (value: number) => void;
    className?: string;
}) {
    const normalized: PeriodOption[] = options.map((o) =>
        typeof o === 'number' ? { value: o, label: `Last ${o} days` } : { value: o.value, label: o.label ?? `Last ${o.value} days` },
    );

    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <label htmlFor={id} className="text-xs text-gray-500 dark:text-gray-400">
                {label}
            </label>
            <div className="relative">
                <select
                    id={id}
                    value={value}
                    onChange={(e) => onChange(Number(e.target.value))}
                    className="h-10 appearance-none rounded-lg border border-gray-200 bg-white px-3 pr-10 text-sm text-gray-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    {normalized.map((o) => (
                        <option key={o.value} value={o.value}>
                            {o.label}
                        </option>
                    ))}
                </select>
                <svg
                    aria-hidden
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    className="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                >
                    <path
                        fillRule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                        clipRule="evenodd"
                    />
                </svg>
            </div>
        </div>
    );
}

