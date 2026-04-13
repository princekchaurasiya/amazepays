import React from 'react';

type Props = {
    label: string;
    value: string | number;
    icon: React.ElementType;
    color?: 'blue' | 'green' | 'purple' | 'orange' | 'red' | 'indigo';
    trend?: { value: number; label: string };
};

const colorClasses: Record<string, string> = {
    blue: 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
    green: 'bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400',
    purple: 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400',
    orange: 'bg-orange-50 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400',
    red: 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400',
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400',
};

export default function StatCard({ label, value, icon: Icon, color = 'blue', trend }: Props) {
    return (
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-5 flex items-center gap-4">
            <div className={`w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0 ${colorClasses[color]}`}>
                <Icon size={22} />
            </div>
            <div className="min-w-0">
                <p className="text-sm text-gray-500 dark:text-gray-400 truncate">{label}</p>
                <p className="text-xl font-bold text-gray-900 dark:text-white">{value}</p>
                {trend && (
                    <p className={`text-xs mt-0.5 ${trend.value >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                        {trend.value >= 0 ? '+' : ''}{trend.value}% {trend.label}
                    </p>
                )}
            </div>
        </div>
    );
}
