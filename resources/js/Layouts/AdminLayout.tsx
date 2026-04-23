import React, { PropsWithChildren } from 'react';
import { Link, usePage } from '@inertiajs/react';

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
    { label: 'Vouchagram / Gyftr', href: '/panel/vouchagram' },
    { label: 'Value Design', href: '/panel/value-design' },
    { label: 'Settings', href: '/panel/settings' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const page = usePage();
    const currentPath = page.url?.split('?')[0] ?? '';

    return (
        <div className="flex min-h-screen bg-slate-100 dark:bg-gray-900">
            <aside className="w-64 border-r border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div className="border-b border-gray-200 px-4 py-4 dark:border-gray-700">
                    <h1 className="text-xl font-bold text-indigo-600 dark:text-indigo-400">AmazePays</h1>
                </div>
                <nav className="p-3">
                    {adminMenu.map((item) => {
                        const active = currentPath.startsWith(item.href);
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`mb-1 block rounded px-3 py-2 text-sm ${
                                    active
                                        ? 'bg-indigo-50 font-medium text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700/50'
                                }`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            <main className="min-w-0 flex-1 p-6">{children}</main>
        </div>
    );
}
