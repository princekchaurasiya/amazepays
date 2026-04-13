import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs, ConfirmDialog } from '@/Components/Admin';
import { Shield, AlertTriangle, Ban, Eye, Activity, Smartphone } from 'lucide-react';
import { format } from 'date-fns';

type Props = {
    overview: {
        active_threats: number;
        vpn_attempts_today: number;
        blocked_ips: number;
        blocked_mobiles: number;
        failed_logins_today: number;
        wallet_fraud_flags: number;
        top_threat_ips: { ip_address: string; event_count: number }[];
    };
    timeline: any[];
};

const severityColors: Record<string, string> = {
    critical: 'bg-red-100 text-red-700 border-red-200',
    high:     'bg-orange-100 text-orange-700 border-orange-200',
    medium:   'bg-yellow-100 text-yellow-700 border-yellow-200',
    low:      'bg-blue-100 text-blue-700 border-blue-200',
    info:     'bg-gray-100 text-gray-600 border-gray-200',
};

export default function SecurityDashboard({ overview, timeline }: Props) {
    const hasThreats = overview.active_threats > 0 || overview.wallet_fraud_flags > 0;
    const [blockIp, setBlockIp] = useState<string | null>(null);
    const [blocking, setBlocking] = useState(false);

    const handleBlockIp = () => {
        if (!blockIp) return;
        setBlocking(true);
        router.post('/panel/security/blocked-ips', { ip_address: blockIp }, {
            onFinish: () => { setBlocking(false); setBlockIp(null); },
        });
    };

    return (
        <AdminLayout>
            <Head title="Security Dashboard" />

            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Security' }]} />

                <ConfirmDialog
                    open={blockIp !== null}
                    onClose={() => setBlockIp(null)}
                    onConfirm={handleBlockIp}
                    title={`Block IP ${blockIp}?`}
                    message={`Blocking this IP will prevent all requests from ${blockIp}. Legitimate users behind this IP (e.g. shared networks) will also be affected.`}
                    confirmLabel="Block IP"
                    variant="danger"
                    loading={blocking}
                />

                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Shield size={24} className="text-indigo-600" />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Security Dashboard</h1>
                    </div>
                    <div className="flex gap-2">
                        <Link href="/panel/security/fraud-queue" className="btn-secondary text-sm">
                            Fraud Queue {overview.wallet_fraud_flags > 0 && `(${overview.wallet_fraud_flags})`}
                        </Link>
                        <Link href="/panel/security/blocked-ips" className="btn-secondary text-sm">
                            Blocked IPs ({overview.blocked_ips})
                        </Link>
                        <Link href="/panel/security/blocked-mobiles" className="btn-secondary text-sm">
                            Blocked mobiles ({overview.blocked_mobiles})
                        </Link>
                        <Link href="/panel/security/events" className="btn-primary text-sm">
                            All Events
                        </Link>
                    </div>
                </div>

                {/* Alert banner */}
                {hasThreats && (
                    <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 flex items-center gap-3">
                        <AlertTriangle size={20} className="text-red-600 flex-shrink-0" />
                        <div>
                            <p className="font-semibold text-red-800 dark:text-red-400">
                                Active Security Threats Detected
                            </p>
                            <p className="text-sm text-red-600 dark:text-red-400 mt-1">
                                {overview.active_threats} unresolved high-severity events. {overview.wallet_fraud_flags} fraud flags in queue.
                                Please review immediately.
                            </p>
                        </div>
                    </div>
                )}

                {/* Metric cards — each links to the relevant security screen */}
                <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    {[
                        {
                            label: 'Active Threats',
                            value: overview.active_threats,
                            icon: AlertTriangle,
                            alert: overview.active_threats > 0,
                            href: '/panel/security/events?severity=high&unresolved_only=1',
                        },
                        {
                            label: 'VPN Attempts',
                            value: overview.vpn_attempts_today,
                            icon: Eye,
                            alert: overview.vpn_attempts_today > 10,
                            href: '/panel/security/events?event_type=vpn_detected',
                        },
                        {
                            label: 'Blocked IPs',
                            value: overview.blocked_ips,
                            icon: Ban,
                            alert: false,
                            href: '/panel/security/blocked-ips',
                        },
                        {
                            label: 'Blocked mobiles',
                            value: overview.blocked_mobiles,
                            icon: Smartphone,
                            alert: false,
                            href: '/panel/security/blocked-mobiles',
                        },
                        {
                            label: 'Failed Logins',
                            value: overview.failed_logins_today,
                            icon: Activity,
                            alert: overview.failed_logins_today > 20,
                            href: '/panel/security/events?event_type=login_failed',
                        },
                        {
                            label: 'Fraud Flags',
                            value: overview.wallet_fraud_flags,
                            icon: AlertTriangle,
                            alert: overview.wallet_fraud_flags > 0,
                            href: '/panel/security/fraud-queue',
                        },
                    ].map(card => {
                        const Icon = card.icon;
                        return (
                            <Link
                                key={card.label}
                                href={card.href}
                                className={`block rounded-xl shadow-sm p-5 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 ${
                                    card.alert ? 'bg-white dark:bg-gray-800 ring-2 ring-red-500/20 hover:bg-red-50/50 dark:hover:bg-gray-800/90' : 'bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/30'
                                }`}
                            >
                                <div className={`w-10 h-10 rounded-lg flex items-center justify-center mb-3 ${card.alert ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-600 dark:bg-gray-700 dark:text-gray-300'}`}>
                                    <Icon size={18} />
                                </div>
                                <p className={`text-2xl font-bold ${card.alert ? 'text-red-600' : 'text-gray-900 dark:text-white'}`}>
                                    {card.value}
                                </p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{card.label}</p>
                            </Link>
                        );
                    })}
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Event Timeline */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <div className="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 className="font-semibold text-gray-900 dark:text-white">Recent High-Severity Events</h2>
                            <Link href="/panel/security/events?severity=high" className="text-xs text-indigo-600 hover:underline">
                                View all →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100 dark:divide-gray-700 max-h-96 overflow-y-auto">
                            {timeline.length === 0 ? (
                                <p className="p-5 text-sm text-gray-400 text-center">No high-severity events today</p>
                            ) : (
                                timeline.map(event => (
                                    <div key={event.id} className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <span className={`text-xs px-2 py-0.5 rounded border font-medium ${severityColors[event.severity]}`}>
                                                        {event.severity}
                                                    </span>
                                                    <span className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                        {event.event_type.replace(/_/g, ' ')}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    IP: {event.ip_address ?? '—'} · {event.country_code ?? '?'}
                                                    {event.user_id && ` · User #${event.user_id}`}
                                                </p>
                                            </div>
                                            <div className="text-right flex-shrink-0">
                                                <p className="text-xs text-gray-400">
                                                    {format(new Date(event.created_at), 'HH:mm:ss')}
                                                </p>
                                                {!event.resolved && (
                                                    <Link
                                                        href={`/panel/security/events/${event.id}`}
                                                        className="text-xs text-indigo-600 hover:underline mt-1 block"
                                                    >
                                                        Review
                                                    </Link>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>

                    {/* Top Threat IPs */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm">
                        <div className="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h2 className="font-semibold text-gray-900 dark:text-white">Top Threat IPs (24h)</h2>
                            <Link href="/panel/security/blocked-ips" className="text-xs text-indigo-600 hover:underline">
                                Manage IPs →
                            </Link>
                        </div>
                        <div className="divide-y divide-gray-100 dark:divide-gray-700">
                            {overview.top_threat_ips.length === 0 ? (
                                <p className="p-5 text-sm text-gray-400 text-center">No threat IPs in the last 24h</p>
                            ) : (
                                overview.top_threat_ips.map((ipRow, idx) => (
                                    <div key={ipRow.ip_address} className="flex items-center justify-between px-5 py-3">
                                        <div className="flex items-center gap-3">
                                            <span className="text-xs text-gray-400 font-mono w-5">{idx + 1}</span>
                                            <span className="font-mono text-sm text-gray-900 dark:text-white">
                                                {ipRow.ip_address}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <span className="text-sm text-red-600 font-medium">
                                                {ipRow.event_count} events
                                            </span>
                                            <button
                                                className="text-xs text-red-600 hover:underline"
                                                onClick={() => setBlockIp(ipRow.ip_address)}
                                            >
                                                Block
                                            </button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}
