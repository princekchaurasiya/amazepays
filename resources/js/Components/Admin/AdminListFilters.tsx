import React, { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';

export type SelectOption = { value: string; label: string };

type Props = {
    /** Current filter values (from server). */
    filters: Record<string, string | number | null | undefined>;
    /** Build full URL for GET navigation; omit empty optional params in implementation. */
    buildUrl: (patch: Record<string, string | undefined>, page?: number) => string;
    sourceProviderOptions: string[];
    searchDebounceMs?: number;
    className?: string;
};

const PER_PAGE_OPTIONS = [10, 20, 50, 100] as const;

const STATUS_OPTIONS: SelectOption[] = [
    { value: '', label: 'Visibility: any' },
    { value: 'visible', label: 'Visible' },
    { value: 'hidden', label: 'Hidden' },
];

const AUDIENCE_OPTIONS: SelectOption[] = [
    { value: '', label: 'Audience: any' },
    { value: 'b2c', label: 'B2C' },
    { value: 'b2b', label: 'B2B' },
    { value: 'both', label: 'Both' },
];

export default function AdminListFilters({
    filters,
    buildUrl,
    sourceProviderOptions,
    searchDebounceMs = 320,
    className = '',
}: Props) {
    const buildUrlRef = useRef(buildUrl);
    buildUrlRef.current = buildUrl;

    const searchFromServer = (filters.search as string | undefined) ?? '';
    const [localSearch, setLocalSearch] = useState(searchFromServer);

    useEffect(() => {
        setLocalSearch(searchFromServer);
    }, [searchFromServer]);

    useEffect(() => {
        const t = window.setTimeout(() => {
            const next = localSearch.trim();
            const cur = searchFromServer.trim();
            if (next === cur) {
                return;
            }
            router.get(
                buildUrlRef.current({ search: next || undefined }, 1),
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, searchDebounceMs);
        return () => window.clearTimeout(t);
    }, [localSearch, searchDebounceMs, searchFromServer]);

    const navigate = (patch: Record<string, string | undefined>) => {
        router.get(buildUrlRef.current(patch, 1), {}, { preserveState: true, preserveScroll: true });
    };

    const hasFilters =
        (filters.search as string | undefined)?.trim() ||
        filters.source_provider ||
        filters.status ||
        filters.catalog_audience;

    const clear = () => {
        router.get(
            buildUrlRef.current(
                {
                    search: undefined,
                    source_provider: undefined,
                    status: undefined,
                    catalog_audience: undefined,
                },
                1,
            ),
            {},
            { preserveState: true, preserveScroll: true },
        );
        setLocalSearch('');
    };

    const providerValue = (filters.source_provider as string | undefined) ?? '';
    const statusValue = (filters.status as string | undefined) ?? '';
    const audienceValue = (filters.catalog_audience as string | undefined) ?? '';
    const perPage = Number(filters.per_page ?? 20) || 20;

    return (
        <div
            className={`flex flex-col gap-3 p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/50 ${className}`.trim()}
        >
            <div className="flex flex-col lg:flex-row lg:items-end gap-3">
                <div className="relative flex-1 min-w-[12rem]">
                    <Search
                        size={16}
                        className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                    />
                    <input
                        type="search"
                        value={localSearch}
                        onChange={e => setLocalSearch(e.target.value)}
                        placeholder="Search name, SKU…"
                        className="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>
                <div className="flex flex-wrap gap-2 items-center">
                    <select
                        value={providerValue}
                        onChange={e =>
                            navigate({
                                source_provider: e.target.value || undefined,
                            })
                        }
                        className="text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="">Provider: any</option>
                        {sourceProviderOptions.map(p => (
                            <option key={p} value={p}>
                                {p}
                            </option>
                        ))}
                    </select>
                    <select
                        value={statusValue}
                        onChange={e =>
                            navigate({
                                status: e.target.value || undefined,
                            })
                        }
                        className="text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                    >
                        {STATUS_OPTIONS.map(o => (
                            <option key={o.value || 'any'} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={audienceValue}
                        onChange={e =>
                            navigate({
                                catalog_audience: e.target.value || undefined,
                            })
                        }
                        className="text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                    >
                        {AUDIENCE_OPTIONS.map(o => (
                            <option key={o.value || 'any-aud'} value={o.value}>
                                {o.label}
                            </option>
                        ))}
                    </select>
                    <select
                        value={String(perPage)}
                        onChange={e =>
                            navigate({
                                per_page: e.target.value,
                            })
                        }
                        className="text-sm border border-gray-200 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white py-2 pl-3 pr-8 focus:ring-2 focus:ring-indigo-500"
                    >
                        {PER_PAGE_OPTIONS.map(n => (
                            <option key={n} value={String(n)}>
                                {n} / page
                            </option>
                        ))}
                    </select>
                    {hasFilters ? (
                        <button
                            type="button"
                            onClick={clear}
                            className="inline-flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <X size={14} />
                            Clear
                        </button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
