import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import CategoryGlyph from '@/Components/Storefront/CategoryGlyph';
import { getCategoryIconDef } from '@/lib/categoryIcons';
import { paths } from '@/lib/paths';

type Cat = { id: number; name: string; slug: string; thumbnail?: string | null };

export default function CategoryNav() {
    const page = usePage<{ storefrontCategories?: Cat[] }>();
    const categories = page.props.storefrontCategories ?? [];
    const hasDb = categories.length > 0;
    const scrollRef = useRef<HTMLDivElement>(null);
    const [canPrev, setCanPrev] = useState(false);
    const [canNext, setCanNext] = useState(false);

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

    const renderIcon = (name: string) => {
        const def = CATEGORY_ICON_MAP[name];
        if (!def) return null;
        return (
            <svg
                className={`h-6 w-6 ${def.className ?? 'text-gray-800'}`}
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden
            >
                {def.paths.map((d) => (
                    <path key={d.slice(0, 24)} strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={d} />
                ))}
            </svg>
        );
    };

    const thumbnailUrl = (path: string | null | undefined) => {
        if (!path || path === 'null') return null;
        if (path.startsWith('http')) return path;
        return `/storage/${path.replace(/^\/+/, '')}`;
    };

    return (
        <nav
            className="storefront-category-nav border-b border-gray-200/80 bg-gradient-to-b from-slate-100 via-gray-50 to-white"
            aria-label="Categories"
        >
            <div className="mx-auto max-w-7xl px-3 py-3">
                <div className="rounded-[1.5rem] border border-gray-200/90 bg-white px-2 py-2 shadow-md shadow-gray-900/[0.06] ring-1 ring-black/[0.04] sm:px-3">
                    <div className="relative flex items-center gap-1">
                        <button
                            type="button"
                            className="category-nav-scroll-btn hidden h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-30 sm:flex"
                            aria-label="Scroll categories left"
                            disabled={!canPrev}
                            onClick={() => scrollBy(-220)}
                        >
                            <ChevronLeft className="h-5 w-5" />
                        </button>
                        <div
                            ref={scrollRef}
                            data-category-nav-scroll
                            className="category-nav-scroll-track flex min-w-0 flex-1 flex-nowrap items-start gap-x-1 overflow-x-auto scroll-smooth py-1 md:gap-2"
                            style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                        >
                            <Link
                                href={paths.home}
                                className="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50"
                            >
                                <span className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm ring-1 ring-gray-200">
                                    <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"
                                        />
                                    </svg>
                                </span>
                                <span className="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">Browse</span>
                            </Link>

                            {!hasDb && (
                                <>
                                    <a
                                        href={`${paths.home}#storefront-section-hot`}
                                        className="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 border-l border-gray-200 pl-3 text-center text-gray-700 transition hover:bg-gray-50 md:pl-4"
                                    >
                                        <span className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-orange-600 shadow-sm ring-1 ring-gray-200">
                                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"
                                                />
                                            </svg>
                                        </span>
                                        <span className="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">Hot deals</span>
                                    </a>
                                    <a
                                        href={`${paths.home}#storefront-section-brands`}
                                        className="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50"
                                    >
                                        <span className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200">
                                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
                                                />
                                            </svg>
                                        </span>
                                        <span className="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">Brands</span>
                                    </a>
                                    <a
                                        href={`${paths.home}#storefront-section-deals`}
                                        className="flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 text-center text-gray-700 transition hover:bg-gray-50"
                                    >
                                        <span className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-white text-emerald-600 shadow-sm ring-1 ring-gray-200">
                                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </span>
                                        <span className="max-w-[5.5rem] truncate text-xs font-medium text-gray-800">All deals</span>
                                    </a>
                                </>
                            )}

                            {categories.map((cat, i) => {
                                const href = `/category/${encodeURIComponent(cat.slug)}`;
                                const thumb = thumbnailUrl(cat.thumbnail);
                                const icon = <CategoryGlyph name={cat.name} className="h-6 w-6 text-gray-800" />;
                                return (
                                    <Link
                                        key={cat.id}
                                        href={href}
                                        className={`flex min-w-[4.5rem] flex-shrink-0 flex-col items-center gap-1 rounded-xl px-2 py-1 text-center text-gray-700 transition hover:bg-gray-50 ${
                                            i === 0 && hasDb ? 'border-l border-gray-200 pl-3 md:pl-4' : ''
                                        }`}
                                    >
                                        <span className="flex h-12 w-12 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-1 ring-gray-200">
                                            {thumb ? (
                                                <img src={thumb} alt="" className="h-full w-full object-cover" />
                                            ) : getCategoryIconDef(cat.name) ? (
                                                icon
                                            ) : (
                                                <span className="text-sm font-bold text-brand-600">
                                                    {cat.name.slice(0, 1).toUpperCase()}
                                                </span>
                                            )}
                                        </span>
                                        <span className="max-w-[5.5rem] truncate text-xs font-medium text-gray-800" title={cat.name}>
                                            {cat.name}
                                        </span>
                                    </Link>
                                );
                            })}
                        </div>
                        <button
                            type="button"
                            className="category-nav-scroll-btn hidden h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50 disabled:pointer-events-none disabled:opacity-30 sm:flex"
                            aria-label="Scroll categories right"
                            disabled={!canNext}
                            onClick={() => scrollBy(220)}
                        >
                            <ChevronRight className="h-5 w-5" />
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
