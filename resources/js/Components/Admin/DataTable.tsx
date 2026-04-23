import React from 'react';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Search, FileDown } from 'lucide-react';

export type Column<T> = {
    key: string;
    label: string;
    render?: (row: T) => React.ReactNode;
    sortable?: boolean;
    className?: string;
};

type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from?: number;
    to?: number;
};

type Props<T> = {
    columns: Column<T>[];
    data: T[];
    meta?: PaginationMeta;
    searchValue?: string;
    searchPlaceholder?: string;
    onSearch?: (query: string) => void;
    onExport?: () => void;
    emptyMessage?: string;
    emptyDescription?: string;
    baseUrl?: string;
    rowKey?: (row: T) => string | number;
};

/** Generic table; for Inertia admin lists use {@link InertiaQueryPagination} so page links keep filters. */
export default function DataTable<T extends Record<string, any>>({
    columns,
    data,
    meta,
    searchValue = '',
    searchPlaceholder = 'Search...',
    onSearch,
    onExport,
    emptyMessage = 'No results found',
    emptyDescription = 'Try adjusting your search or filters.',
    baseUrl,
    rowKey = (row) => row.id,
}: Props<T>) {
    return (
        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
            {(onSearch || onExport) && (
                <div className="flex items-center justify-between gap-4 p-4 border-b border-gray-100 dark:border-gray-700">
                    {onSearch && (
                        <div className="relative flex-1 max-w-sm">
                            <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                            <input
                                type="text"
                                defaultValue={searchValue}
                                placeholder={searchPlaceholder}
                                onChange={(e) => onSearch(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            />
                        </div>
                    )}
                    {onExport && (
                        <button
                            onClick={onExport}
                            className="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                        >
                            <FileDown size={16} />
                            Export
                        </button>
                    )}
                </div>
            )}

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                            {columns.map(col => (
                                <th key={col.key} className={`px-5 py-3 font-medium ${col.className ?? ''}`}>
                                    {col.label}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.length === 0 ? (
                            <tr>
                                <td colSpan={columns.length} className="px-5 py-12 text-center">
                                    <p className="text-gray-500 dark:text-gray-400 font-medium">{emptyMessage}</p>
                                    <p className="text-gray-400 dark:text-gray-500 text-xs mt-1">{emptyDescription}</p>
                                </td>
                            </tr>
                        ) : (
                            data.map(row => (
                                <tr
                                    key={rowKey(row)}
                                    className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                >
                                    {columns.map(col => (
                                        <td key={col.key} className={`px-5 py-3 ${col.className ?? ''}`}>
                                            {col.render ? col.render(row) : row[col.key]}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {meta && meta.last_page > 1 && (
                <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                    <span>
                        Showing {meta.from ?? ((meta.current_page - 1) * meta.per_page + 1)} to{' '}
                        {meta.to ?? Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} results
                    </span>
                    <div className="flex items-center gap-1">
                        {meta.current_page > 1 && (
                            <Link
                                href={`${baseUrl ?? ''}?page=${meta.current_page - 1}`}
                                preserveState
                                className="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <ChevronLeft size={16} />
                            </Link>
                        )}
                        <span className="px-2 font-medium text-gray-700 dark:text-gray-300">
                            {meta.current_page} / {meta.last_page}
                        </span>
                        {meta.current_page < meta.last_page && (
                            <Link
                                href={`${baseUrl ?? ''}?page=${meta.current_page + 1}`}
                                preserveState
                                className="p-1.5 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <ChevronRight size={16} />
                            </Link>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
