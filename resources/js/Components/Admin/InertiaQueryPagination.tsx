import React, { useMemo } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number | null;
    to?: number | null;
};

function pageItems(current: number, last: number): (number | 'ellipsis')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    const set = new Set<number>();
    set.add(1);
    set.add(last);
    for (let i = current - 2; i <= current + 2; i++) {
        if (i >= 1 && i <= last) {
            set.add(i);
        }
    }
    const sorted = [...set].sort((a, b) => a - b);
    const out: (number | 'ellipsis')[] = [];
    let prev = 0;
    for (const p of sorted) {
        if (prev && p - prev > 1) {
            out.push('ellipsis');
        }
        out.push(p);
        prev = p;
    }
    return out;
}

type Props = {
    meta: PaginationMeta;
    buildUrl: (page: number) => string;
    className?: string;
};

export default function InertiaQueryPagination({ meta, buildUrl, className = '' }: Props) {
    const { current_page: current, last_page: last, total, from, to } = meta;
    const items = useMemo(() => pageItems(current, last), [current, last]);

    if (total <= 0) {
        return null;
    }

    const fromN = from ?? (last ? (current - 1) * meta.per_page + 1 : 0);
    const toN = to ?? Math.min(current * meta.per_page, total);

    return (
        <div
            className={`flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-5 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400 ${className}`.trim()}
        >
            <span>
                Showing {fromN}–{toN} of {total}
            </span>
            {last > 1 && (
                <div className="flex flex-wrap items-center gap-1 justify-end">
                    {current > 1 ? (
                        <Link
                            href={buildUrl(current - 1)}
                            preserveState
                            className="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Previous page"
                        >
                            <ChevronLeft size={18} />
                        </Link>
                    ) : (
                        <span className="p-1.5 rounded opacity-40 pointer-events-none">
                            <ChevronLeft size={18} />
                        </span>
                    )}
                    {items.map((item, idx) =>
                        item === 'ellipsis' ? (
                            <span key={`e-${idx}`} className="px-1 text-gray-400">
                                …
                            </span>
                        ) : (
                            <Link
                                key={item}
                                href={buildUrl(item)}
                                preserveState
                                className={`min-w-[2rem] px-2 py-1 rounded text-center ${
                                    item === current
                                        ? 'bg-indigo-600 text-white font-medium'
                                        : 'hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300'
                                }`}
                            >
                                {item}
                            </Link>
                        ),
                    )}
                    {current < last ? (
                        <Link
                            href={buildUrl(current + 1)}
                            preserveState
                            className="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            aria-label="Next page"
                        >
                            <ChevronRight size={18} />
                        </Link>
                    ) : (
                        <span className="p-1.5 rounded opacity-40 pointer-events-none">
                            <ChevronRight size={18} />
                        </span>
                    )}
                </div>
            )}
        </div>
    );
}
