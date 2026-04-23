import React, { useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { StatCard, StatusBadge } from '@/Components/Admin';
import {
    ShoppingCart,
    IndianRupee,
    Users,
    Wallet,
    Shield,
    AlertTriangle,
    TrendingUp,
    Building2,
} from 'lucide-react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { format, isValid, parseISO } from 'date-fns';

type DashboardType = 'admin' | 'finance' | 'b2b';

function safeFormatDate(value: unknown, fmt: string): string {
    if (value == null || value === '') {
        return '—';
    }
    const d = value instanceof Date ? value : typeof value === 'string' ? parseISO(value) : new Date(value as string);
    if (!isValid(d)) {
        return '—';
    }
    try {
        return format(d, fmt);
    } catch {
        return '—';
    }
}

type Stats = {
    orders_today: number;
    revenue_today: number;
    orders_this_week?: number;
    total_users?: number;
    new_users_today?: number;
    pending_loads?: number;
    total_wallet_balance: number;
};

type SecurityBlock = {
    active_threats: number;
    vpn_attempts_today: number;
    blocked_ips: number;
    blocked_mobiles: number;
    failed_logins_today: number;
    wallet_fraud_flags: number;
};

type StatLinkKey =
    | 'orders_today'
    | 'revenue_today'
    | 'total_users'
    | 'wallet_balance'
    | 'pending_loads'
    | 'orders_this_week'
    | 'b2b_wallet';

type StatLinks = Partial<Record<StatLinkKey, string>>;

type StatCardDef = {
    label: string;
    value: string | number;
    icon: React.ElementType;
    color: 'blue' | 'green' | 'purple' | 'orange';
    linkKey?: StatLinkKey;
};

type Props = {
    dashboardType?: DashboardType;
    chartDays?: number;
    statLinks?: StatLinks;
    stats?: Partial<Stats>;
    recentOrders?: any[];
    revenueChart?: { date: string; orders: number; revenue: number }[];
    security?: Partial<SecurityBlock>;
    b2b?: {
        tenant: { id: number; name: string; slug: string } | null;
    } | null;
};

const defaultStats: Stats = {
    orders_today: 0,
    revenue_today: 0,
    orders_this_week: 0,
    total_users: 0,
    new_users_today: 0,
    pending_loads: 0,
    total_wallet_balance: 0,
};

const defaultSecurity: SecurityBlock = {
    active_threats: 0,
    vpn_attempts_today: 0,
    blocked_ips: 0,
    blocked_mobiles: 0,
    failed_logins_today: 0,
    wallet_fraud_flags: 0,
};

const CHART_DAY_OPTIONS = [7, 14, 30, 60, 90] as const;

export default function Dashboard({
    dashboardType = 'admin',
    chartDays: chartDaysProp = 30,
    statLinks: statLinksIn = {},
    stats: statsIn,
    recentOrders,
    revenueChart,
    security: securityIn,
    b2b,
}: Props) {
    const stats = useMemo(() => ({ ...defaultStats, ...statsIn }), [statsIn]);
    const statLinks = useMemo(() => statLinksIn ?? {}, [statLinksIn]);
    const chartDays = CHART_DAY_OPTIONS.includes(chartDaysProp as (typeof CHART_DAY_OPTIONS)[number])
        ? chartDaysProp
        : 30;
    const security = useMemo(() => ({ ...defaultSecurity, ...securityIn }), [securityIn]);
    const orders = Array.isArray(recentOrders) ? recentOrders : [];
    const chart = Array.isArray(revenueChart) ? revenueChart : [];

    const title =
        dashboardType === 'finance'
            ? 'Finance overview'
            : dashboardType === 'b2b'
              ? 'B2B dashboard'
              : 'Dashboard';

    const subtitle =
        dashboardType === 'finance'
            ? 'Orders, refunds, wallets, and load requests at a glance.'
            : dashboardType === 'b2b'
              ? 'Tenant activity and your wallet balance.'
              : 'Operations, security, and revenue across the platform.';

    const statCards: StatCardDef[] =
        dashboardType === 'b2b'
            ? [
                  {
                      label: 'Orders today',
                      value: stats.orders_today,
                      icon: ShoppingCart,
                      color: 'blue',
                      linkKey: 'orders_today',
                  },
                  {
                      label: 'Revenue today',
                      value: `₹${Number(stats.revenue_today).toLocaleString('en-IN')}`,
                      icon: IndianRupee,
                      color: 'green',
                      linkKey: 'revenue_today',
                  },
                  {
                      label: 'Orders this week',
                      value: stats.orders_this_week ?? 0,
                      icon: TrendingUp,
                      color: 'purple',
                      linkKey: 'orders_this_week',
                  },
                  {
                      label: 'Your wallet',
                      value: `₹${Number(stats.total_wallet_balance).toLocaleString('en-IN')}`,
                      icon: Wallet,
                      color: 'orange',
                      linkKey: 'b2b_wallet',
                  },
              ]
            : dashboardType === 'finance'
              ? [
                    {
                        label: 'Orders today',
                        value: stats.orders_today,
                        icon: ShoppingCart,
                        color: 'blue',
                        linkKey: 'orders_today',
                    },
                    {
                        label: 'Revenue today',
                        value: `₹${Number(stats.revenue_today).toLocaleString('en-IN')}`,
                        icon: IndianRupee,
                        color: 'green',
                        linkKey: 'revenue_today',
                    },
                    {
                        label: 'Pending load requests',
                        value: stats.pending_loads ?? 0,
                        icon: Wallet,
                        color: 'orange',
                        linkKey: 'pending_loads',
                    },
                    {
                        label: 'Total wallet balance',
                        value: `₹${Number(stats.total_wallet_balance).toLocaleString('en-IN')}`,
                        icon: IndianRupee,
                        color: 'purple',
                        linkKey: 'wallet_balance',
                    },
                ]
              : [
                    {
                        label: 'Orders today',
                        value: stats.orders_today,
                        icon: ShoppingCart,
                        color: 'blue',
                        linkKey: 'orders_today',
                    },
                    {
                        label: 'Revenue today',
                        value: `₹${Number(stats.revenue_today).toLocaleString('en-IN')}`,
                        icon: IndianRupee,
                        color: 'green',
                        linkKey: 'revenue_today',
                    },
                    {
                        label: 'Total users',
                        value: stats.total_users ?? 0,
                        icon: Users,
                        color: 'purple',
                        linkKey: 'total_users',
                    },
                    {
                        label: 'Wallet balance',
                        value: `₹${Number(stats.total_wallet_balance).toLocaleString('en-IN')}`,
                        icon: Wallet,
                        color: 'orange',
                        linkKey: 'wallet_balance',
                    },
                ];

    const colorClasses: Record<string, string> = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-green-50 text-green-600',
        purple: 'bg-purple-50 text-purple-600',
        orange: 'bg-orange-50 text-orange-600',
        red: 'text-red-600',
        yellow: 'text-yellow-600',
        gray: 'text-gray-600',
    };

    const securityCards = [
        { label: 'Active threats', value: security.active_threats, color: security.active_threats > 0 ? 'red' : 'green' },
        { label: 'VPN attempts', value: security.vpn_attempts_today, color: security.vpn_attempts_today > 10 ? 'red' : 'yellow' },
        { label: 'Blocked IPs', value: security.blocked_ips, color: 'gray' },
        { label: 'Blocked mobiles', value: security.blocked_mobiles, color: 'gray' },
        { label: 'Failed logins', value: security.failed_logins_today, color: security.failed_logins_today > 20 ? 'red' : 'yellow' },
        { label: 'Fraud flags', value: security.wallet_fraud_flags, color: security.wallet_fraud_flags > 0 ? 'red' : 'green' },
    ];

    const chartData = chart.map((row) => ({
        ...row,
        revenue: Number(row.revenue ?? 0),
    }));

    return (
        <AdminLayout>
            <Head title={title} />

            <div className="space-y-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{title}</h1>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{subtitle}</p>
                    </div>
                    {dashboardType === 'b2b' && b2b?.tenant && (
                        <div className="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <Building2 className="h-4 w-4 text-indigo-600" />
                            <span className="font-medium text-gray-900 dark:text-white">{b2b.tenant.name}</span>
                        </div>
                    )}
                </div>

                {dashboardType === 'b2b' && (
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href="/panel/b2b/shop"
                            className="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700"
                        >
                            Shop
                        </Link>
                        <Link
                            href="/panel/b2b/orders"
                            className="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        >
                            View orders
                        </Link>
                        <Link
                            href="/panel/b2b/wallet"
                            className="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        >
                            Wallet
                        </Link>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {statCards.map((card) => (
                        <StatCard
                            key={card.label}
                            label={card.label}
                            value={card.value}
                            icon={card.icon}
                            color={card.color}
                            href={card.linkKey ? statLinks[card.linkKey] : undefined}
                        />
                    ))}
                </div>

                {dashboardType === 'admin' && (
                    <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                        <div className="mb-4 flex items-center gap-2">
                            <Shield size={20} className="text-indigo-600" />
                            <h2 className="font-semibold text-gray-900 dark:text-white">Security overview</h2>
                            {security.active_threats > 0 && (
                                <span className="ml-2 flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700">
                                    <AlertTriangle size={12} />
                                    {security.active_threats} active threat{security.active_threats !== 1 ? 's' : ''}
                                </span>
                            )}
                        </div>
                        <div className="grid grid-cols-2 gap-4 sm:grid-cols-5">
                            {securityCards.map((card) => (
                                <div key={card.label} className="text-center">
                                    <p className={`text-2xl font-bold ${colorClasses[card.color]}`}>{card.value}</p>
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{card.label}</p>
                                </div>
                            ))}
                        </div>
                        {(security.active_threats > 0 || security.wallet_fraud_flags > 0) && (
                            <div className="mt-3 border-t border-gray-100 pt-3 dark:border-gray-700">
                                <a href="/panel/security" className="text-sm text-indigo-600 hover:underline">
                                    View security dashboard →
                                </a>
                            </div>
                        )}
                    </div>
                )}

                <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                    <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <TrendingUp size={20} className="text-green-600" />
                            <h2 className="font-semibold text-gray-900 dark:text-white">
                                {dashboardType === 'b2b'
                                    ? `Revenue — your tenant (${chartDays} days)`
                                    : `Revenue — last ${chartDays} days`}
                            </h2>
                        </div>
                        <div className="flex items-center gap-2">
                            <label htmlFor="dashboard-chart-days" className="text-xs text-gray-500 dark:text-gray-400">
                                Period
                            </label>
                            <select
                                id="dashboard-chart-days"
                                value={chartDays}
                                onChange={(e) => {
                                    router.get(
                                        '/panel',
                                        { chart_days: Number(e.target.value) },
                                        { preserveState: true, replace: true, preserveScroll: true },
                                    );
                                }}
                                className="rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                {CHART_DAY_OPTIONS.map((d) => (
                                    <option key={d} value={d}>
                                        Last {d} days
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    {chartData.length === 0 ? (
                        <p className="py-8 text-center text-sm text-gray-500">No revenue data in this period.</p>
                    ) : (
                        <ResponsiveContainer width="100%" height={220}>
                            <AreaChart data={chartData}>
                                <defs>
                                    <linearGradient id="revenue" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="5%" stopColor="#6366f1" stopOpacity={0.2} />
                                        <stop offset="95%" stopColor="#6366f1" stopOpacity={0} />
                                    </linearGradient>
                                </defs>
                                <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                                <XAxis
                                    dataKey="date"
                                    tickFormatter={(d) => safeFormatDate(d, 'MMM d')}
                                    tick={{ fontSize: 11 }}
                                />
                                <YAxis tick={{ fontSize: 11 }} tickFormatter={(v) => `₹${(Number(v) / 1000).toFixed(0)}k`} />
                                <Tooltip
                                    formatter={(value: number) => [`₹${Number(value).toLocaleString('en-IN')}`, 'Revenue']}
                                    labelFormatter={(d) => safeFormatDate(d, 'MMM d, yyyy')}
                                />
                                <Area
                                    type="monotone"
                                    dataKey="revenue"
                                    stroke="#6366f1"
                                    fill="url(#revenue)"
                                    strokeWidth={2}
                                />
                            </AreaChart>
                        </ResponsiveContainer>
                    )}
                </div>

                <div className="rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div className="border-b border-gray-100 p-5 dark:border-gray-700">
                        <h2 className="font-semibold text-gray-900 dark:text-white">Recent orders</h2>
                    </div>
                    {orders.length === 0 ? (
                        <p className="p-8 text-center text-sm text-gray-500">No orders to show yet.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="bg-gray-50 text-left text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                                        <th className="px-5 py-3 font-medium">Order ID</th>
                                        <th className="px-5 py-3 font-medium">Customer</th>
                                        <th className="px-5 py-3 font-medium">Status</th>
                                        <th className="px-5 py-3 font-medium">Amount</th>
                                        <th className="px-5 py-3 font-medium">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {orders.map((order: any) => (
                                        <tr
                                            key={order.id}
                                            className="border-t border-gray-100 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/30"
                                        >
                                            <td className="px-5 py-3">
                                                {dashboardType === 'b2b' ? (
                                                    <span className="font-mono text-gray-800 dark:text-gray-200">
                                                        #{order.order_number ?? order.id}
                                                    </span>
                                                ) : (
                                                    <a
                                                        href={`/panel/orders/${order.id}`}
                                                        className="font-mono text-indigo-600 hover:underline"
                                                    >
                                                        #{order.id}
                                                    </a>
                                                )}
                                            </td>
                                            <td className="px-5 py-3">{order.user?.name ?? '—'}</td>
                                            <td className="px-5 py-3">
                                                <StatusBadge status={order.order_status} showTooltip />
                                            </td>
                                            <td className="px-5 py-3">
                                                ₹{Number(order.grand_payable_amount).toLocaleString('en-IN')}
                                            </td>
                                            <td className="px-5 py-3 text-gray-500">
                                                {safeFormatDate(order.created_at, 'dd MMM, HH:mm')}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
