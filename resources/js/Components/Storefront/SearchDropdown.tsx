import React, { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { paths } from '@/lib/paths';

export type SearchSuggestion = {
    id: number;
    name: string;
    slug: string;
    image: string | null;
    discount: string | number | null;
};

export type SearchDropdownHandle = {
    /** Return true if the key event was handled (e.g. navigated, closed). */
    consumeKeyDown: (e: React.KeyboardEvent) => boolean;
};

type Props = {
    query: string;
    /** When true, the panel may render (still requires2+ debounced chars and results/loading). */
    open: boolean;
    boundaryRef: React.RefObject<HTMLElement | null>;
    onRequestClose: () => void;
    onSelectSlug: (slug: string) => void;
};

const DEBOUNCE_MS = 300;

function initials(name: string): string {
    const t = name.trim();
    if (!t) return '?';
    return t.slice(0, 1).toUpperCase();
}

const SearchDropdown = forwardRef<SearchDropdownHandle, Props>(function SearchDropdown(
    { query, open, boundaryRef, onRequestClose, onSelectSlug },
    ref,
) {
    const [debounced, setDebounced] = useState('');
    const [items, setItems] = useState<SearchSuggestion[]>([]);
    const [loading, setLoading] = useState(false);
    const [highlight, setHighlight] = useState(-1);
    const listRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const t = window.setTimeout(() => setDebounced(query.trim()), DEBOUNCE_MS);
        return () => window.clearTimeout(t);
    }, [query]);

    useEffect(() => {
        setHighlight(-1);
    }, [debounced]);

    useEffect(() => {
        setHighlight((h) => {
            if (h < 0) {
                return -1;
            }
            if (items.length === 0) {
                return -1;
            }
            return Math.min(h, items.length - 1);
        });
    }, [items]);

    useEffect(() => {
        if (debounced.length < 2) {
            setItems([]);
            setLoading(false);
            return;
        }

        const ac = new AbortController();
        setLoading(true);

        fetch(`${paths.searchSuggest}?q=${encodeURIComponent(debounced)}`, {
            signal: ac.signal,
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then((r) => r.json())
            .then((data: unknown) => {
                if (!Array.isArray(data)) {
                    setItems([]);
                    return;
                }
                const cleaned = (data as SearchSuggestion[]).filter((row) => row && typeof row.slug === 'string' && row.slug.length > 0);
                setItems(cleaned);
            })
            .catch((err: Error) => {
                if (err.name !== 'AbortError') {
                    setItems([]);
                }
            })
            .finally(() => {
                if (!ac.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => ac.abort();
    }, [debounced]);

    useEffect(() => {
        if (highlight < 0) return;
        const row = listRef.current?.querySelector<HTMLElement>(`[data-suggest-index="${highlight}"]`);
        row?.scrollIntoView({ block: 'nearest' });
    }, [highlight]);

    useEffect(() => {
        if (!open) return;

        const onDocMouseDown = (e: MouseEvent) => {
            const root = boundaryRef.current;
            if (root && !root.contains(e.target as Node)) {
                onRequestClose();
            }
        };

        document.addEventListener('mousedown', onDocMouseDown);
        return () => document.removeEventListener('mousedown', onDocMouseDown);
    }, [open, boundaryRef, onRequestClose]);

    useImperativeHandle(ref, () => ({
        consumeKeyDown: (e: React.KeyboardEvent) => {
            if (!open || debounced.length < 2) {
                return false;
            }

            if (e.key === 'Escape') {
                e.preventDefault();
                onRequestClose();
                return true;
            }

            if (e.key === 'ArrowDown') {
                if (!items.length) return false;
                e.preventDefault();
                setHighlight((h) => (h < items.length - 1 ? h + 1 : h));
                return true;
            }

            if (e.key === 'ArrowUp') {
                if (!items.length) return false;
                e.preventDefault();
                setHighlight((h) => (h > 0 ? h - 1 : -1));
                return true;
            }

            if (e.key === 'Enter') {
                const row = highlight >= 0 ? items[highlight] : null;
                if (row?.slug) {
                    e.preventDefault();
                    onSelectSlug(row.slug);
                    onRequestClose();
                    return true;
                }
            }

            return false;
        },
    }));

    const showPanel = open && debounced.length >= 2 && (loading || items.length > 0);
    if (!showPanel) {
        return null;
    }

    const viewAllHref = `${paths.search}?query=${encodeURIComponent(debounced)}`;

    return (
        <div
            ref={listRef}
            className="absolute left-0 right-0 top-full z-[60] mt-2 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl"
            role="listbox"
            aria-label="Search suggestions"
            onMouseDown={(e) => e.preventDefault()}
        >
            <div className="max-h-80 overflow-y-auto py-1">
                {loading && items.length === 0 ? (
                    <div className="px-4 py-6 text-center text-sm text-gray-500">Searching…</div>
                ) : null}

                {items.map((item, index) => {
                    const active = index === highlight;
                    return (
                        <Link
                            key={item.id}
                            href={paths.product(item.slug)}
                            data-suggest-index={index}
                            role="option"
                            aria-selected={active}
                            className={`flex items-center gap-3 px-3 py-2 text-left transition ${
                                active ? 'bg-brand-50' : 'hover:bg-gray-50'
                            }`}
                            onMouseEnter={() => setHighlight(index)}
                            onClick={() => onRequestClose()}
                        >
                            <div className="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                {item.image ? (
                                    <img src={item.image} alt="" className="h-full w-full object-cover" loading="lazy" />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center text-sm font-semibold text-gray-500">
                                        {initials(item.name)}
                                    </div>
                                )}
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="truncate text-sm font-medium text-gray-900">{item.name}</div>
                                <div className="text-xs text-gray-500">Product</div>
                            </div>
                        </Link>
                    );
                })}
            </div>

            <div className="border-t border-gray-100 bg-gray-50/80">
                <Link
                    href={viewAllHref}
                    className="block px-4 py-3 text-center text-sm font-semibold text-brand-600 hover:text-brand-700"
                    onClick={() => onRequestClose()}
                >
                    View all results
                </Link>
            </div>
        </div>
    );
});

export default SearchDropdown;
