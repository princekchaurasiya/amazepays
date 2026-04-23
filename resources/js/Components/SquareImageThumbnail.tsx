import React from 'react';
import { X } from 'lucide-react';

type SquareImageThumbnailProps = {
    src: string;
    alt: string;
    selected?: boolean;
    onClick?: () => void;
    onRemove?: () => void;
    removable?: boolean;
    size?: 'sm' | 'md';
    className?: string;
};

const sizeMap: Record<NonNullable<SquareImageThumbnailProps['size']>, string> = {
    sm: 'h-16 w-16 sm:h-20 sm:w-20',
    md: 'h-20 w-20 sm:h-24 sm:w-24',
};

export default function SquareImageThumbnail({
    src,
    alt,
    selected = false,
    onClick,
    onRemove,
    removable = false,
    size = 'md',
    className = '',
}: SquareImageThumbnailProps) {
    const sizeClass = sizeMap[size];
    const borderClass = selected
        ? 'border-gray-900 ring-2 ring-gray-900/70'
        : 'border-gray-300 hover:border-gray-500';

    const imageBlock = (
        <div className={`${sizeClass} overflow-hidden rounded-lg bg-gray-100`}>
            <img src={src} alt={alt} className="h-full w-full object-cover" />
        </div>
    );

    return (
        <div className={`relative shrink-0 rounded-lg border bg-white transition dark:bg-gray-900/40 dark:border-gray-700 ${borderClass} ${className}`}>
            {onClick ? (
                <button
                    type="button"
                    onClick={onClick}
                    className="block rounded-lg focus:outline-none"
                    aria-label={alt}
                >
                    {imageBlock}
                </button>
            ) : (
                imageBlock
            )}

            {removable && onRemove ? (
                <button
                    type="button"
                    onClick={onRemove}
                    className="absolute right-1 top-1 z-10 rounded bg-white/90 p-1 text-gray-600 shadow hover:bg-red-50 hover:text-red-600 dark:bg-gray-800/90 dark:text-gray-200 dark:hover:bg-red-900/40 dark:hover:text-red-300"
                    aria-label="Remove image"
                    title="Remove image"
                >
                    <X size={13} />
                </button>
            ) : null}
        </div>
    );
}

