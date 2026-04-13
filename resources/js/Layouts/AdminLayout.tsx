import React, { PropsWithChildren, useMemo, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    LayoutDashboard,
    Package,
    FolderTree,
    ShoppingCart,
    Users,
    Building2,
    Wallet,
    Tag,
    Settings,
    FileText,
    Shield,
    Gift,
    Menu,
    X,
    Bell,
    ChevronDown,
    LogOut,
    User,
    HelpCircle,
    MessageSquare,
    Search,
    Store,
    ClipboardList,
    UsersRound,
    LayoutGrid,
} from 'lucide-react';
import FlashToast from '@/Components/Admin/FlashToast';

type NavItem = {
    label: string;
    href: string;
    icon: React.ElementType;
    permission?: string;
};

const adminNavItems: NavItem[] = [
    { label: 'Dashboard', href: '/panel', icon: LayoutDashboard, permission: 'dashboard.view' },
    { label: 'Products', href: '/panel/products', icon: Package, permission: 'products.view' },
    { label: 'Categories', href: '/panel/categories', icon: FolderTree, permission: 'categories.manage' },
    { label: 'Orders', href: '/panel/orders', icon: ShoppingCart, permission: 'orders.view' },
    { label: 'Users', href: '/panel/users', icon: Users, permission: 'users.view' },
    { label: 'Tenants / B2B', href: '/panel/tenants', icon: Building2, permission: 'tenants.view' },
    { label: 'Wallets', href: '/panel/wallets', icon: Wallet, permission: 'wallets.view' },
    { label: 'Offers', href: '/panel/offers', icon: Tag, permission: 'offers.view' },
    { label: 'Tickets', href: '/panel/tickets', icon: MessageSquare, permission: 'tickets.view' },
    { label: 'Providers', href: '/panel/providers', icon: LayoutGrid, permission: 'providers.view' },
    { label: 'Vouchagram', href: '/panel/vouchagram', icon: Gift, permission: 'providers.view' },
    { label: 'Audit Log', href: '/panel/audit-logs', icon: FileText, permission: 'audit_logs.view' },
    { label: 'Security', href: '/panel/security', icon: Shield, permission: 'security.view' },
    { label: 'Settings', href: '/panel/settings', icon: Settings, permission: 'settings.view' },
];

const b2bNavItems: NavItem[] = [
    { label: 'Place order', href: '/panel/b2b/place-order', icon: Store, permission: 'b2b.place_order' },
    { label: 'My orders', href: '/panel/b2b/orders', icon: ClipboardList, permission: 'b2b.view_orders' },
    { label: 'Team', href: '/panel/b2b/team', icon: UsersRound, permission: 'b2b.manage_team' },
    { label: 'Wallet', href: '/panel/b2b/wallet', icon: Wallet, permission: 'b2b.wallet.view' },
];

export default function AdminLayout({ children }: PropsWithChildren) {
    const page = usePage<any>();
    const { auth, security } = page.props;
    const pathname = (page.url as string)?.split('?')[0] ?? '';

    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [search, setSearch] = useState('');

    const permissions: string[] = Array.isArray(auth?.user?.permissions) ? auth.user.permissions : [];
    const roles: string[] = Array.isArray(auth?.user?.roles) ? auth.user.roles : [];

    const hasPermission = (perm?: string) => !perm || permissions.includes(perm);

    const isPureB2b =
        roles.some((r) => r === 'b2b-client' || r === 'b2b-operator') &&
        !roles.some((r) => r === 'super-admin' || r === 'admin' || r === 'finance');

    const filteredAdmin = useMemo(() => {
        const q = search.trim().toLowerCase();
        return adminNavItems
            .filter((item) => hasPermission(item.permission))
            .filter((item) => !q || item.label.toLowerCase().includes(q));
    }, [permissions, search]);

    const filteredB2b = useMemo(() => {
        const q = search.trim().toLowerCase();
        return b2bNavItems
            .filter((item) => hasPermission(item.permission))
            .filter((item) => !q || item.label.toLowerCase().includes(q));
    }, [permissions, search]);

    const activeThreats = typeof security?.active_threats === 'number' ? security.active_threats : 0;

    const navLinkClass = (href: string, activeOverride?: boolean) => {
        const isActive =
            activeOverride ??
            (href === '/panel' ? pathname === '/panel' || pathname === '/panel/' : pathname.startsWith(href));
        return `flex items-center gap-3 px-4 py-2.5 text-sm transition-colors relative ${
            isActive
                ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 dark:text-indigo-400 font-medium'
                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200'
        }`;
    };

    return (
        <div className="flex h-screen overflow-hidden bg-slate-100 dark:bg-gray-900">
            <aside
                className={`${
                    sidebarOpen ? 'w-64' : 'w-16'
                } flex flex-shrink-0 flex-col border-r border-gray-200 bg-white shadow-sm transition-all duration-300 dark:border-gray-700 dark:bg-gray-800`}
            >
                <div className="flex h-16 items-center justify-between border-b border-gray-200 px-4 dark:border-gray-700">
                    {sidebarOpen && (
                        <span className="text-lg font-bold tracking-tight text-indigo-600 dark:text-indigo-400">
                            AmazePays
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        aria-label={sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'}
                    >
                        {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
                    </button>
                </div>

                <nav className="flex-1 overflow-y-auto py-3">
                    {!isPureB2b && filteredAdmin.length > 0 && (
                        <>
                            {sidebarOpen && (
                                <p className="mb-1 px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    Administration
                                </p>
                            )}
                            {filteredAdmin.map((item) => {
                                const Icon = item.icon;
                                return (
                                    <Link key={item.href} href={item.href} className={navLinkClass(item.href)}>
                                        <Icon size={18} className="flex-shrink-0" />
                                        {sidebarOpen && <span className="truncate">{item.label}</span>}
                                        {item.label === 'Security' && activeThreats > 0 && sidebarOpen && (
                                            <span className="ml-auto min-w-[1.25rem] rounded-full bg-red-500 px-1.5 py-0.5 text-center text-xs text-white">
                                                {activeThreats}
                                            </span>
                                        )}
                                    </Link>
                                );
                            })}
                        </>
                    )}

                    {filteredB2b.length > 0 && (
                        <>
                            {sidebarOpen && (
                                <p className="mb-1 mt-4 px-4 text-xs font-semibold uppercase tracking-wider text-gray-400">
                                    B2B portal
                                </p>
                            )}
                            {filteredB2b.map((item) => {
                                const Icon = item.icon;
                                return (
                                    <Link key={item.href} href={item.href} className={navLinkClass(item.href)}>
                                        <Icon size={18} className="flex-shrink-0" />
                                        {sidebarOpen && <span className="truncate">{item.label}</span>}
                                    </Link>
                                );
                            })}
                        </>
                    )}

                    {filteredAdmin.length === 0 && filteredB2b.length === 0 && sidebarOpen && (
                        <p className="px-4 text-sm text-gray-500">No menu items match your access.</p>
                    )}
                </nav>

                {sidebarOpen && (
                    <div className="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                        <a
                            href="/docs"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 text-xs text-gray-400 transition-colors hover:text-indigo-500"
                        >
                            <HelpCircle size={14} />
                            Need help?
                        </a>
                    </div>
                )}
            </aside>

            <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
                <FlashToast />

                <header className="flex h-16 flex-shrink-0 items-center gap-4 border-b border-gray-200 bg-white px-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:px-6">
                    <div className="flex min-w-0 flex-1 items-center gap-3">
                        <div className="hidden min-w-0 flex-1 flex-col sm:flex">
                            <span className="truncate text-xs font-medium uppercase tracking-wide text-gray-400">
                                Control center
                            </span>
                        </div>
                        <div className="relative w-full max-w-xl flex-1">
                            <Search
                                className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                aria-hidden
                            />
                            <input
                                type="search"
                                placeholder="Search menu…"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full rounded-lg border border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500"
                            />
                        </div>
                    </div>

                    <div className="flex flex-shrink-0 items-center gap-2">
                        <Link
                            href="/"
                            className="hidden rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700/50 md:inline"
                        >
                            Storefront
                        </Link>
                        <button
                            type="button"
                            className="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                            aria-label="Notifications"
                        >
                            <Bell size={20} />
                            {activeThreats > 0 && (
                                <span className="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-red-500" />
                            )}
                        </button>

                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                                className="flex items-center gap-2 rounded-lg py-1.5 pl-1 pr-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700/50"
                            >
                                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/50">
                                    <User size={18} className="text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <span className="hidden max-w-[140px] truncate sm:inline">
                                    {auth?.user?.name ?? 'Account'}
                                </span>
                                <ChevronDown size={16} className="text-gray-400" />
                            </button>

                            {userMenuOpen && (
                                <>
                                    <button
                                        type="button"
                                        className="fixed inset-0 z-40 cursor-default"
                                        aria-label="Close menu"
                                        onClick={() => setUserMenuOpen(false)}
                                    />
                                    <div className="absolute right-0 top-full z-50 mt-2 w-52 rounded-lg border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                        <Link
                                            href="/panel/2fa/setup"
                                            className="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700"
                                            onClick={() => setUserMenuOpen(false)}
                                        >
                                            <Shield size={16} />
                                            Two-factor auth
                                        </Link>
                                        <hr className="my-1 border-gray-200 dark:border-gray-700" />
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            className="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
                                            onClick={() => setUserMenuOpen(false)}
                                        >
                                            <LogOut size={16} />
                                            Logout
                                        </Link>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                <main className="flex-1 overflow-y-auto p-4 md:p-6">{children}</main>
            </div>
        </div>
    );
}
