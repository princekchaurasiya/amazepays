import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import { Card, CardBody, CardHeader } from '@/Components/UI/Card';
import { Boxes, CircleCheck, ExternalLink, ListTodo, ShieldAlert, Wallet } from 'lucide-react';

type Provider = {
    key: string;
    label: string;
    healthy: boolean;
    href: string | null;
    note?: string | null;
    connection_present?: boolean;
    last_sync_at?: string | null;
    pending_queue?: number;
    failed_runs?: number;
};

export default function ProvidersIndex({
    providers,
    kpis,
    order_stats,
}: {
    providers: Provider[];
    kpis: { totalProviders: number; pendingQueue: number; failedRuns: number; lastSuccessfulSync: string | null };
    order_stats: {
        storefront_orders: number;
        kgen_provider_orders: number;
        woohoo_products: number;
        vouchagram_products: number;
        vd_products: number;
    };
}) {
    return (
        <AdminLayout>
            <Head title="Sync Center" />
            <div className="space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Integrations</p>
                        <h1 className="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Sync Center</h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Monitor provider health, sync activity, and catalog imports across Woohoo, Vouchagram, and Value Design.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {kpis.lastSuccessfulSync ? (
                            <Badge tone="neutral">Last successful sync: {new Date(kpis.lastSuccessfulSync).toLocaleString()}</Badge>
                        ) : (
                            <Badge tone="neutral">Last successful sync: —</Badge>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <StatCard label="Providers" value={kpis.totalProviders} icon={Wallet} color="brand" />
                    <StatCard label="Pending queue" value={kpis.pendingQueue} icon={ListTodo} color="warning" />
                    <StatCard label="Failed runs" value={kpis.failedRuns} icon={ShieldAlert} color="danger" />
                    <StatCard label="Catalog products" value={order_stats.woohoo_products + order_stats.vouchagram_products + order_stats.vd_products} icon={Boxes} color="accent" />
                </div>

                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {providers.map((p) => (
                        <Card key={p.key}>
                            <CardHeader className="flex items-start justify-between gap-4">
                                <div className="min-w-0">
                                    <p className="truncate text-lg font-semibold text-gray-900 dark:text-gray-100">{p.label}</p>
                                    {p.note ? (
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{p.note}</p>
                                    ) : (
                                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">Provider integration console.</p>
                                    )}
                                </div>
                                {p.healthy ? <Badge tone="success">Configured</Badge> : <Badge tone="warning">Needs setup</Badge>}
                            </CardHeader>
                            <CardBody>
                                <div className="grid gap-3 sm:grid-cols-2">
                                    <div className="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-800 dark:bg-gray-950">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Last sync</p>
                                        <p className="mt-2 font-semibold text-gray-900 dark:text-gray-100">
                                            {p.last_sync_at ? new Date(p.last_sync_at).toLocaleString() : '—'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-800 dark:bg-gray-950">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Runs</p>
                                        <p className="mt-2 text-sm text-gray-700 dark:text-gray-200">
                                            Pending: <span className="font-semibold">{Number(p.pending_queue ?? 0)}</span>
                                            <br />
                                            Failed: <span className="font-semibold">{Number(p.failed_runs ?? 0)}</span>
                                        </p>
                                    </div>
                                </div>

                                <div className="mt-5 flex flex-wrap gap-3">
                                    {p.href ? (
                                        <Link href={p.href} className="w-full sm:w-auto">
                                            <Button variant="primary" leftIcon={<ExternalLink className="h-4 w-4" />}>
                                                Open dashboard
                                            </Button>
                                        </Link>
                                    ) : (
                                        <Button variant="muted" disabled>
                                            No dashboard
                                        </Button>
                                    )}
                                    {p.healthy ? (
                                        <Badge tone="success" className="self-center">
                                            <CircleCheck className="mr-1 h-3.5 w-3.5" /> Healthy
                                        </Badge>
                                    ) : null}
                                </div>
                            </CardBody>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Operations</p>
                        <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Order volume</p>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Snapshot counters from the database (useful for sanity checks).
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                            <div className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Storefront orders</p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{order_stats.storefront_orders}</p>
                            </div>
                            <div className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">KGen provider orders</p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{order_stats.kgen_provider_orders}</p>
                            </div>
                            <div className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Woohoo products</p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{order_stats.woohoo_products}</p>
                            </div>
                            <div className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Vouchagram products</p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{order_stats.vouchagram_products}</p>
                            </div>
                            <div className="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-950">
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Value Design products</p>
                                <p className="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">{order_stats.vd_products}</p>
                            </div>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </AdminLayout>
    );
}
