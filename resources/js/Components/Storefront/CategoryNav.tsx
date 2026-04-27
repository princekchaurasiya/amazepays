import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import clsx from 'clsx';
import CategoryGlyph from '@/Components/Storefront/CategoryGlyph';
import { getCategoryIconDef } from '@/lib/categoryIcons';
import { categoryAccentColor } from '@/lib/categoryAccent';
import { paths } from '@/lib/paths';

export type StorefrontCategory = {
    id: number;
    name: string;
    slug: string;
    thumbnail?: string | null;
    accent_color?: string | null;
};

export type CategoryNavProps = {
    /** Storefront (default) or compact B2B shop bar */
    variant?: 'storefront' | 'panel';
    /** Required for `variant="panel"` — categories shown as icon tiles */
    categories?: StorefrontCategory[];
    selectedCategoryId?: number | null;
    onSelectCategory?: (categoryId: number | null) => void;
};

const BrowseGridIcon = ({ className = 'h-6 w-6' }: { className?: string }) => (
    <svg className={className} fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden>
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
        />
    </svg>
);

function thumbnailUrl(path: string | null | undefined) {
    if (!path || path === 'null') return null;
    if (path.startsWith('http')) return path;
    return `/storage/${path.replace(/^\/+/, '')}`;
}

export default function CategoryNav({
    variant = 'storefront',
    categories: categoriesProp,
    selectedCategoryId = null,
    onSelectCategory,
}: CategoryNavProps) {
    const page = usePage<{ storefrontCategories?: StorefrontCategory[] }>();
    const isPanel = variant === 'panel';
    const categories = isPanel ? (categoriesProp ?? []) : (page.props.storefrontCategories ?? []);
    const hasDb = categories.length > 0;
    const scrollRef = useRef<HTMLDivElement>(null);
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(false);

    const compact = isPanel;
    const tileMin = compact ? 'min-w-[3.5rem]' : 'min-w-[4.5rem]';
    const labelMax = compact ? 'max-w-[4.25rem]' : 'max-w-[5.5rem]';
    const iconWrap = compact ? 'h-9 w-9' : 'h-12 w-12';
    const glyphCls = compact ? 'h-5 w-5 text-gray-800' : 'h-6 w-6 text-gray-800';
    const scrollStep = compact ? 168 : 220;

    const updateArrows = useCallback(() => {
        const el = scrollRef.current;
        if (!el) return;
        const max = el.scrollWidth - el.clientWidth - 2;
        setCanPrev(el.scrollLeft > 2);
        setCanNext(el.scrollLeft < max);
        if (max <= 0) {
            setCanPrev(false);
            setCanNext(false);
        }
    }, []);

    useEffect(() => {
        updateArrows();
        const el = scrollRef.current;
        if (!el) return;
        const ro = new ResizeObserver(updateArrows);
        ro.observe(el);
        el.addEventListener('scroll', updateArrows);
        return () => {
            ro.disconnect();
            el.removeEventListener('scroll', updateArrows);
        };
    }, [updateArrows, categories.length]);

    const scrollBy = (delta: number) => {
        scrollRef.current?.scrollBy({ left: delta, behavior: 'smooth' });
    };

    const selectedRing = (selected: boolean) =>
        selected
            ? 'bg-indigo-50 ring-2 ring-indigo-500 dark:bg-indigo-900/35 dark:ring-indigo-400'
            : 'bg-white ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-600';

    const renderCategoryVisual = (cat: StorefrontCategory) => {
        const thumb = thumbnailUrl(cat.thumbnail);
        const accent =
            typeof cat.accent_color === 'string' && /^#[0-9A-Fa-f]{6}$/.test(cat.accent_color.trim())
                ? cat.accent_color.trim()
                : categoryAccentColor(cat.name);
        if (thumb) {
            return <img src={thumb} alt="" className="h-full w-full object-cover" />;
        }
        if (getCategoryIconDef(cat.name)) {
            return <CategoryGlyph name={cat.name} className={glyphCls} accentColor={accent} />;
        }
        return <span className="text-xs font-bold text-brand-600">{cat.name.slice(0, 1).toUpperCase()}</span>;
    };

    const navClass = isPanel
        ? 'w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800'
        : 'storefront-category-nav border-b border-gray-200/80 bg-gradient-to-b from-slate-100 via-gray-50 to-white';

    const innerShell = isPanel
        ? 'px-1.5 py-1.5 sm:px-2'
        : 'rounded-[1.5rem] border border-gray-200/90 bg-white px-2 py-2 shadow-md shadow-gray-900/[0.06] ring-1 ring-black/[0.04] sm:px-3';

    const outerPad = isPanel ? 'py-0' : 'mx-auto max-w-7xl px-3 py-3';

    const scrollBtnCls = clsx(
        'category-nav-scroll-btn flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-30 sm:h-9 sm:w-9',
        compact && 'sm:flex'
    );

    return (
        <nav className={navClass} aria-label="Categories">
            <div className={outerPad}>
                <div className={innerShell}>
                    <div className="relative flex items-center gap-0.5 sm:gap-1">
                        <button
                            type="button"
                            className={clsx(scrollBtnCls, 'hidden sm:flex')}
                            aria-label="Scroll categories left"
                            disabled={!canPrev}
                            onClick={() => scrollBy(-scrollStep)}
                        >
                            <ChevronLeft className="h-4 w-4 sm:h-5 sm:w-5" />
                        </button>
                        <div
                            ref={scrollRef}
                            data-category-nav-scroll
                            className="category-nav-scroll-track flex min-w-0 flex-1 flex-nowrap items-start gap-x-0.5 overflow-x-auto scroll-smooth py-0.5 sm:gap-1 md:gap-2"
                            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                        >
                            {isPanel && onSelectCategory ? (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => onSelectCategory(null)}
                                        className={clsx(
                                            'flex flex-shrink-0 flex-col items-center gap-0.5 rounded-lg px-1.5 py-0.5 text-center transition',
                                            tileMin,
                                            selectedCategoryId === null
                                                ? 'text-indigo-800 dark:text-indigo-200'
                                                : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                        )}
                                    >
                                        <span
                                            className={clsx(
                                                'flex flex-shrink-0 items-center justify-center overflow-hidden rounded-full shadow-sm',
                                                iconWrap,
                                                selectedRing(selectedCategoryId === null)
                                            )}
                                        >
                                            <BrowseGridIcon className={compact ? 'h-5 w-5 text-indigo-600' : 'h-6 w-6 text-indigo-600'} />
                                        </span>
                                        <span
                                            className={clsx(
                                                'truncate text-[10px] font-medium sm:text-xs',
                                                labelMax,
                                                'text-gray-800 dark:text-gray-200'
                                            )}
                                        >
                                            All
                                        </span>
                                    </button>
                                    {categories.map((cat, i) => (
                                        <button
                                            key={cat.id}
                                            type="button"
                                            onClick={() => onSelectCategory(cat.id)}
                                            className={clsx(
                                                'flex flex-shrink-0 flex-col items-center gap-0.5 rounded-lg px-1.5 py-0.5 text-center transition',
                                                i > 0 && 'border-l border-gray-100 pl-2 dark:border-gray-700',
                                                tileMin,
                                                selectedCategoryId === cat.id
                                                    ? 'text-indigo-800 dark:text-indigo-200'
                                                    : 'text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50'
                                            )}
                                        >
                                            <span
                                                className={clsx(
                                                    'flex flex-shrink-0 items-center justify-center overflow-hidden rounded-full shadow-sm',
                                                    iconWrap,
                                                    selectedRing(selectedCategoryId === cat.id)
                                                )}
                                            >
                                                {renderCategoryVisual(cat)}
                                            </span>
                                            <span
                                                className={clsx(
                                                    'truncate text-[10px] font-medium sm:text-xs',
                                                    labelMax,
                                                    'text-gray-800 dark:text-gray-200'
                                                )}
                                                title={cat.name}
                                            >
                                                {cat.name}
                                            </span>
                                        </button>
                                    ))}
                                </>
                            ) : (
                                <>
                                    <Link
                                        href={paths.home}
                                        className={clsx(
                                            'flex flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50',
                                            tileMin
                                        )}
                                    >
                                        <span
                                            className={clsx(
                                                'flex flex-shrink-0 items-center justify-center rounded-full text-brand-600 shadow-sm ring-1 ring-gray-200',
                                                iconWrap
                                            )}
                                        >
                                            <BrowseGridIcon className={compact ? 'h-5 w-5' : 'h-6 w-6'} />
                                        </span>
                                        <span className={clsx('truncate text-xs font-medium text-gray-800', labelMax)}>Browse</span>
                                    </Link>

                                    {!hasDb && (
                                        <>
                                            <a
                                                href={`${paths.home}#storefront-section-hot`}
                                                className={clsx(
                                                    'flex flex-shrink-0 flex-col items-center gap-1 border-l border-gray-200 pl-3 text-center text-gray-700 transition hover:bg-gray-50 md:pl-4',
                                                    tileMin
                                                )}
                                            >
                                                <span
                                                    className={clsx(
                                                        'flex flex-shrink-0 items-center justify-center rounded-full bg-white text-orange-600 shadow-sm ring-1 ring-gray-200',
                                                        iconWrap
                                                    )}
                                                >
                                                    <svg className={compact ? 'h-5 w-5' : 'h-6 w-6'} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"
                                                        />
                                                    </svg>
                                                </span>
                                                <span className={clsx('truncate text-xs font-medium text-gray-800', labelMax)}>Hot deals</span>
                                            </a>
                                            <a
                                                href={`${paths.home}#storefront-section-brands`}
                                                className={clsx(
                                                    'flex flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50',
                                                    tileMin
                                                )}
                                            >
                                                <span
                                                    className={clsx(
                                                        'flex flex-shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200',
                                                        iconWrap
                                                    )}
                                                >
                                                    <svg className={compact ? 'h-5 w-5' : 'h-6 w-6'} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                                        />
                                                    </svg>
                                                </span>
                                                <span className={clsx('truncate text-xs font-medium text-gray-800', labelMax)}>Brands</span>
                                            </a>
                                            <a
                                                href={`${paths.home}#storefront-section-deals`}
                                                className={clsx(
                                                    'flex flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50',
                                                    tileMin
                                                )}
                                            >
                                                <span
                                                    className={clsx(
                                                        'flex flex-shrink-0 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-gray-200',
                                                        iconWrap
                                                    )}
                                                >
                                                    <svg className={compact ? 'h-5 w-5' : 'h-6 w-6'} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path
                                                            strokeLinecap="round"
                                                            strokeLinejoin="round"
                                                            strokeWidth={2}
                                                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        />
                                                    </svg>
                                                </span>
                                                <span className={clsx('truncate text-xs font-medium text-gray-800', labelMax)}>All deals</span>
                                            </a>
                                        </>
                                    )}

                                    {categories.map((cat, i) => {
                                        const href = `/category/${encodeURIComponent(cat.slug)}`;
                                        const accent = cat.accent_color ?? null;
                                        return (
                                            <Link
                                                key={cat.id}
                                                href={href}
                                                className={clsx(
                                                    'flex flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50',
                                                    tileMin,
                                                    i === 0 && hasDb ? 'border-l border-gray-200 pl-3 md:pl-4' : ''
                                                )}
                                            >
                                                <span
                                                    className={clsx(
                                                        'flex flex-shrink-0 items-center justify-center overflow-hidden rounded-full shadow-sm ring-1 ring-gray-200',
                                                        iconWrap
                                                    )}
                                                >
                                                    {thumbnailUrl(cat.thumbnail) ? (
                                                        <img src={thumbnailUrl(cat.thumbnail)!} alt="" className="h-full w-full object-cover" />
                                                    ) : getCategoryIconDef(cat.name) ? (
                                                        <CategoryGlyph name={cat.name} className={glyphCls} accentColor={accent} />
                                                    ) : (
                                                        <span className="text-sm font-bold text-brand-600">{cat.name.slice(0, 1).toUpperCase()}</span>
                                                    )}
                                                </span>
                                                <span className={clsx('truncate text-xs font-medium text-gray-800', labelMax)} title={cat.name}>
                                                    {cat.name}
                                                </span>
                                            </Link>
                                        );
                                    })}
                                </>
                            )}
                        </div>
                        <button
                            type="button"
                            className={clsx(scrollBtnCls, 'hidden sm:flex')}
                            aria-label="Scroll categories right"
                            disabled={!canNext}
                            onClick={() => scrollBy(scrollStep)}
                        >
                            <ChevronRight className="h-4 w-4 sm:h-5 sm:w-5" />
                        </button>
                    </div>
                </div>
            </div>
            <style>{`
                .category-nav-scroll-track::-webkit-scrollbar { display: none; }
            `}</style>
        </nav>
    );
}
