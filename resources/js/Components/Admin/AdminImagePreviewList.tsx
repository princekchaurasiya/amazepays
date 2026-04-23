import React from 'react';
import SquareImageThumbnail from '@/Components/SquareImageThumbnail';

export type AdminImagePreviewItem =
    | {
          kind: 'local';
          key: string;
          url: string;
          name: string;
      }
    | {
          kind: 'persisted';
          key: string;
          url: string;
          name?: string;
          path: string;
      };

type Props = {
    items: AdminImagePreviewItem[];
    onRemove?: (itemKey: string) => void;
    emptyText?: string;
    readOnly?: boolean;
    className?: string;
};

export default function AdminImagePreviewList({
    items,
    onRemove,
    emptyText = 'No images selected.',
    readOnly = false,
    className = '',
}: Props) {
    if (items.length === 0) {
        return <p className="text-xs text-gray-500 dark:text-gray-400">{emptyText}</p>;
    }

    return (
        <div className={`flex flex-wrap gap-2.5 ${className}`}>
            {items.map(item => (
                <div key={item.key}>
                    <div className="w-20 overflow-hidden rounded-lg border border-gray-200 bg-white sm:w-24 dark:border-gray-700 dark:bg-gray-900/40">
                        <SquareImageThumbnail
                            src={item.url}
                            alt={item.name ?? 'Theme image'}
                            size="md"
                            removable={!readOnly && !!onRemove}
                            onRemove={!readOnly && onRemove ? () => onRemove(item.key) : undefined}
                            className="!rounded-none !border-0 !ring-0"
                        />
                        <div className="border-t border-gray-100 px-1.5 py-1 text-[10px] text-gray-600 dark:border-gray-700 dark:text-gray-300">
                            <p className="truncate" title={item.name ?? (item.kind === 'persisted' ? item.path : '')}>
                                {item.name ?? (item.kind === 'persisted' ? item.path.split('/').pop() : 'Image')}
                            </p>
                        </div>
                    </div>
                </div>
            ))}
        </div>
    );
}
