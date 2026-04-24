import React from 'react';

export type AdminUploadDropzoneSurfaceProps = {
    disabled: boolean;
    isDragging: boolean;
    error?: boolean | string;
    /** Slightly shorter vertical padding */
    compact?: boolean;
    onClick: () => void;
    onKeyDown: (e: React.KeyboardEvent) => void;
    onDragOver: (e: React.DragEvent) => void;
    onDragLeave: (e: React.DragEvent) => void;
    onDrop: (e: React.DragEvent) => void;
    ariaLabel: string;
    className?: string;
    children: React.ReactNode;
};

/**
 * Shared dashed dropzone shell for admin uploads (images + documents).
 */
export function AdminUploadDropzoneSurface({
    disabled,
    isDragging,
    error,
    compact = false,
    onClick,
    onKeyDown,
    onDragOver,
    onDragLeave,
    onDrop,
    ariaLabel,
    className = '',
    children,
}: AdminUploadDropzoneSurfaceProps) {
    return (
        <div
            role="button"
            tabIndex={disabled ? -1 : 0}
            aria-label={ariaLabel}
            aria-disabled={disabled}
            onClick={() => {
                if (!disabled) {
                    onClick();
                }
            }}
            onKeyDown={onKeyDown}
            onDragOver={onDragOver}
            onDragLeave={onDragLeave}
            onDrop={onDrop}
            className={`
                group relative w-full rounded-xl border-2 border-dashed text-left transition-all outline-none
                focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900
                flex items-center justify-center gap-2
                ${compact ? 'py-6 px-4' : 'py-8 px-4'}
                ${disabled
                    ? 'cursor-not-allowed border-gray-200 bg-gray-50 opacity-60 dark:border-gray-700 dark:bg-gray-800/50'
                    : isDragging
                        ? 'border-indigo-500 bg-indigo-50/80 dark:border-indigo-400 dark:bg-indigo-950/40'
                        : error
                            ? 'border-red-300 bg-red-50/30 dark:border-red-800 dark:bg-red-950/20'
                            : 'cursor-pointer border-gray-300 bg-white hover:border-indigo-400 dark:border-gray-600 dark:bg-gray-900/40 dark:hover:border-indigo-500/50'
                }
                ${className}
            `}
        >
            {children}
        </div>
    );
}
