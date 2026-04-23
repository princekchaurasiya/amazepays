import React, { PropsWithChildren } from 'react';
import { X } from 'lucide-react';

type Props = PropsWithChildren<{
    selectedCount: number;
    onClear: () => void;
    className?: string;
}>;

export default function BulkActionBar({ selectedCount, onClear, className = '', children }: Props) {
    if (selectedCount <= 0) {
        return null;
    }

    return (
        <div
            className={`flex flex-wrap items-center gap-3 px-4 py-3 bg-indigo-50 dark:bg-indigo-950/40 border-b border-indigo-100 dark:border-indigo-900/50 text-sm ${className}`.trim()}
        >
            <span className="font-medium text-indigo-900 dark:text-indigo-200">
                {selectedCount} selected
            </span>
            <div className="flex flex-wrap items-center gap-2">{children}</div>
            <button
                type="button"
                onClick={onClear}
                className="inline-flex items-center gap-1 ml-auto text-indigo-700 dark:text-indigo-300 hover:underline"
            >
                <X size={14} />
                Clear
            </button>
        </div>
    );
}
