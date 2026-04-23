import React, { useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { Menu, ShoppingCart } from 'lucide-react';
import { paths } from '@/lib/paths';
import SearchDropdown, { type SearchDropdownHandle } from '@/Components/Storefront/SearchDropdown';

type Props = {
    onOpenAuth?: () => void;
    searchQuery?: string;
};

export default function StorefrontHeader({ onOpenAuth, searchQuery = '' }: Props) {
    const page = usePage<{
        auth?: { user?: { name: string; email: string } | null };
        cart?: { count: number; quantity: number; total: number };
    }>();
    const user = page.props.auth?.user ?? null;
    const cartQty = Number(page.props.cart?.quantity ?? 0);
    const [q, setQ] = useState(() => (typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('query') ?? '' : searchQuery));
    const [searchFocused, setSearchFocused] = useState(false);
    const searchBoundaryRef = useRef<HTMLDivElement>(null);
    const searchDropdownRef = useRef<SearchDropdownHandle>(null);

    return (
        <header className="sticky top-0 z-50 border-b border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
            <div className="mx-auto flex max-w-7xl flex-wrap items-center gap-3 px-4 py-3 md:flex-nowrap md:gap-6">
                <Link href={paths.home} className="flex shrink-0 items-center gap-2">
                    <img src="/images/logo.png" alt="AmazePays" className="h-9 w-auto object-contain md:h-10" />
                </Link>

                <form
                    action={paths.search}
                    method="GET"
                    className="order-3 w-full md:order-none md:mx-auto md:max-w-xl md:flex-1"
                >
                    <label className="sr-only" htmlFor="storefront-search">
                        Brand or category name
                    </label>
                    <div className="relative" ref={searchBoundaryRef}>
                        <span className="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </span>
                        <input
                            id="storefront-search"
                            type="search"
                            name="query"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            onFocus={() => setSearchFocused(true)}
                            onBlur={() => {
                                window.setTimeout(() => setSearchFocused(false), 120);
                            }}
                            onKeyDown={(e) => {
                                searchDropdownRef.current?.consumeKeyDown(e);
                            }}
                            placeholder="Brand or category name"
                            className="w-full rounded-full border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 placeholder-gray-500 shadow-sm transition focus:border-brand-500 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            autoComplete="off"
                        />
                        <SearchDropdown
                            ref={searchDropdownRef}
                            query={q}
                            open={searchFocused && q.trim().length >= 2}
                            boundaryRef={searchBoundaryRef}
                            onRequestClose={() => setSearchFocused(false)}
                            onSelectSlug={(slug) => router.visit(paths.product(slug))}
                        />
                    </div>
                </form>

                <nav className="ml-auto flex shrink-0 items-center gap-2 md:gap-4" aria-label="Account">
                    <Link href={paths.business} className="hidden text-sm font-medium text-gray-700 hover:text-brand-600 sm:inline">
                        For businesses
                    </Link>
                    {user ? (
                        <div className="hidden items-center gap-2 sm:flex">
                            <Link
                                href={paths.cart}
                                className="relative inline-flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                <ShoppingCart className="h-4 w-4" />
                                Cart
                                {cartQty > 0 ? (
                                    <span className="absolute -right-1 -top-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-gray-900 px-1 text-xs font-semibold text-white">
                                        {cartQty > 99 ? '99+' : cartQty}
                                    </span>
                                ) : null}
                            </Link>
                            <Link href={paths.profile} className="text-sm font-medium text-gray-700 hover:text-brand-600">
                                Profile
                            </Link>
                            <button
                                type="button"
                                className="rounded-full border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                onClick={() => router.post('/logout')}
                            >
                                Logout
                            </button>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => (onOpenAuth ? onOpenAuth() : router.visit(paths.login))}
                            className="inline-flex items-center rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                        >
                            Log in / Sign up
                        </button>
                    )}

                    <details className="relative sm:hidden">
                        <summary className="list-none cursor-pointer rounded-full border border-gray-200 p-2 text-gray-700 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                            <span className="sr-only">Menu</span>
                            <Menu className="h-5 w-5" />
                        </summary>
                        <div className="absolute right-0 z-50 mt-2 w-48 rounded-xl border border-gray-100 bg-white py-2 shadow-lg">
                            <Link href={paths.business} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                For businesses
                            </Link>
                            <Link href={paths.about} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                About
                            </Link>
                            <Link href={paths.contact} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Contact
                            </Link>
                            {user ? (
                                <>
                                    <Link href={paths.cart} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        Cart{cartQty > 0 ? ` (${cartQty > 99 ? '99+' : cartQty})` : ''}
                                    </Link>
                                    <Link href={paths.profile} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        Profile
                                    </Link>
                                    <Link href={paths.myOrders} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        My orders
                                    </Link>
                                    <button
                                        type="button"
                                        className="w-full px-4 py-2 text-left text-sm font-medium text-red-600"
                                        onClick={() => router.post('/logout')}
                                    >
                                        Logout
                                    </button>
                                </>
                            ) : null}
                        </div>
                    </details>
                </nav>
            </div>
        </header>
    );
}
