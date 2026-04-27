import React from 'react';
import { Link } from '@inertiajs/react';

type Props = {
    label: string;
    value: string | number;
    icon: React.ElementType;
    color?: 'brand' | 'success' | 'warning' | 'danger' | 'neutral' | 'accent';
    trend?: { value: number; label: string };
    href?: string;
};

const colorClasses: Record<string, string> = {
    brand: 'bg-blue-50 text-product-primary dark:bg-blue-900/30 dark:text-blue-100',
    accent: 'bg-orange-50 text-product-accent dark:bg-orange-900/30 dark:text-orange-100',
    success: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
    warning: 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-100',
    danger: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-200',
    neutral: 'bg-gray-50 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
};

const shellClass =
    'bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm p-6 flex items-center gap-4 transition hover:shadow-md';
const interactiveClass =
    'hover:ring-2 hover:ring-product-primary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-product-primary/30';

export default function StatCard({ label, value, icon: Icon, color = 'brand', trend, href }: Props) {
    const body = (
        <>
            <div className={`w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 ${colorClasses[color]}`}>
                <Icon size={22} />
            </div>
            <div className="min-w-0">
                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 truncate">{label}</p>
                <p className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{value}</p>
                {trend && (
                    <p className={`text-xs mt-0.5 ${trend.value >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {trend.value >= 0 ? '+' : ''}{trend.value}% {trend.label}
                    </p>
                )}
            </div>
        </>
    );

    if (href) {
        return (
            <Link href={href} className={`${shellClass} ${interactiveClass} block`}>
                {body}
            </Link>
        );
    }

    return <div className={shellClass}>{body}</div>;
}
