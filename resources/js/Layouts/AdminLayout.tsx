import React, { PropsWithChildren, useMemo } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Bell, Search, UserCircle2 } from 'lucide-react';

type MenuItem = {
    label: string;
    href: string;
};

const adminMenu: MenuItem[] = [
    { label: 'Dashboard', href: '/panel' },
    { label: 'Products', href: '/panel/products' },
    { label: 'Gift Themes', href: '/panel/gift-themes' },
    { label: 'Orders', href: '/panel/orders' },
    { label: 'Users', href: '/panel/users' },
    { label: 'Providers', href: '/panel/providers' },
    { label: 'Woohoo', href: '/panel/woohoo' },
    { label: 'Vouchagram / Gyftr', href: '/panel/vouchagram' },
    { label: 'Value Design', href: '/panel/value-design' },
    { label: 'Homepage Builder', href: '/panel/homepage-builder' },
    { label: 'Settings', href: '/panel/settings' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const currentPath = page.url?.split('?')[0] ?? '';

    const isActive = (href: string) => {
        // Dashboard (/panel) should not match every /panel/* page.
        if (href === '/panel') return currentPath === '/panel';
        return currentPath === href || currentPath.startsWith(href + '/');
    };

    const title = useMemo(() => {
        const found = adminMenu.find((m) => isActive(m.href));
        return found?.label ?? 'Admin';
    }, [currentPath]);

    return (
        <div className="flex min-h-screen bg-gray-50 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
            <aside className="hidden w-72 shrink-0 border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 md:block">
                <div className="flex items-center gap-3 border-b border-gray-200 px-6 py-5 dark:border-gray-800">
                    <div className="flex h-10 w-10 items-center justify-center rounded-2xl bg-product-primary text-white shadow-sm">
                        <span className="text-sm font-bold">A</span>
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">AMAZEPAYS</p>
                        <p className="text-xs text-gray-500">Admin console</p>
                    </div>
                </div>
                <nav className="p-4">
                    <p className="px-3 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Menu</p>
                    {adminMenu.map((item) => {
                        const active = isActive(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={[
                                    'mb-1 flex items-center rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                    active
                                        ? 'bg-blue-50 text-product-primary dark:bg-blue-900/25 dark:text-blue-100'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800/60',
                                ].join(' ')}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            <div className="min-w-0 flex-1">
                <header className="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/80">
                    <div className="mx-auto flex max-w-[1400px] items-center gap-4 px-6 py-4 md:px-8">
                        <div className="min-w-0">
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Admin</p>
                            <p className="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{title}</p>
                        </div>

                        <div className="ml-auto hidden w-full max-w-md items-center md:flex">
                            <div className="relative w-full">
                                <Search className="pointer-events-none absolute left-3 top-3 h-5 w-5 text-gray-400" aria-hidden="true" />
                                <input
                                    placeholder="Search…"
                                    className="h-11 w-full rounded-xl border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-900 shadow-sm outline-none transition focus:border-product-primary focus:ring-2 focus:ring-product-primary/20 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100"
                                />
                            </div>
                        </div>

                        <button
                            type="button"
                            className="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                            aria-label="Notifications"
                        >
                            <Bell className="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            className="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-900"
                            aria-label="Profile"
                        >
                            <UserCircle2 className="h-5 w-5 text-gray-400" aria-hidden="true" />
                            <span className="hidden sm:inline">Profile</span>
                        </button>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-[1400px] p-6 md:p-8">{children}</main>
            </div>
        </div>
    );
}
