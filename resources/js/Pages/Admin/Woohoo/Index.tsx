import React, { useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import { Card, CardBody, CardHeader } from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import Select from '@/Components/UI/Select';
import { Boxes, CircleCheck, Clock, Database, ListTodo, RefreshCw, Shield, Tag, TriangleAlert } from 'lucide-react';

export default function WoohooIndex({
    configured,
    settingsTokenPresent,
    settingsTokenUpdatedAt,
    syncedCategories,
    kpis,
    lastSyncAt,
    syncRuns,
}: {
    configured: boolean;
    settingsTokenPresent: boolean;
    settingsTokenUpdatedAt: string | null;
    syncedCategories: Array<{ id: number; name: string; external_id: string }>;
    kpis: { categoriesSynced: number; productsImported: number; pendingQueue: number; failedJobs: number };
    lastSyncAt: string | null;
    syncRuns: Array<{
        id: number;
        job_type: string;
        status: string;
        records_fetched: number;
        records_created: number;
        records_updated: number;
        records_failed: number;
        last_error_message: string | null;
        started_at: string | null;
        completed_at: string | null;
        created_at: string | null;
    }>;
}) {
    const [busy, setBusy] = useState<string | null>(null);
    const [result, setResult] = useState<any>(null);
    const [selectedSyncedCategoryId, setSelectedSyncedCategoryId] = useState<string>(() =>
        syncedCategories?.[0]?.id ? String(syncedCategories[0].id) : '',
    );
    const [testSku, setTestSku] = useState('');
    const [testAmount, setTestAmount] = useState('100');
    const [testQty, setTestQty] = useState('1');
    const [detailsSku, setDetailsSku] = useState('');

    const categories = useMemo(() => syncedCategories ?? [], [syncedCategories]);
    const runs = useMemo(() => syncRuns ?? [], [syncRuns]);

    const runAction = async (name: string, url: string, data?: Record<string, any>) => {
        setBusy(name);
        setResult(null);
        try {
            const resp = await (window as any).axios.post(url, data ?? {});
            setResult(resp.data);
        } catch (e: any) {
            const status = e?.response?.status;
            const body = e?.response?.data ?? { message: e?.message };
            setResult({ success: false, status, error: body });
        } finally {
            setBusy(null);
        }
    };

    const badgeForConfigured = configured ? (
        <Badge tone="success">Ready</Badge>
    ) : (
        <Badge tone="warning">Needs setup</Badge>
    );

    const badgeForToken = settingsTokenPresent ? <Badge tone="success">Active</Badge> : <Badge tone="danger">Missing</Badge>;

    const badgeForRunStatus = (status: string) => {
        const key = String(status || '').toLowerCase();
        if (key === 'succeeded') return <Badge tone="success">Succeeded</Badge>;
        if (key === 'running') return <Badge tone="brand">Running</Badge>;
        if (key === 'queued') return <Badge tone="warning">Queued</Badge>;
        if (key === 'failed') return <Badge tone="danger">Failed</Badge>;
        return <Badge tone="neutral">{status || 'Unknown'}</Badge>;
    };

    return (
        <AdminLayout>
            <Head title="Catalog Sync Center" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Woohoo</p>
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">Catalog Sync Center</h1>
                            {badgeForConfigured}
                        </div>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            Manage categories, products & upstream sync jobs.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {lastSyncAt ? (
                            <Badge tone="neutral">
                                Last sync: {new Date(lastSyncAt).toLocaleString()}
                            </Badge>
                        ) : (
                            <Badge tone="neutral">Last sync: —</Badge>
                        )}
                        <Button
                            variant="muted"
                            leftIcon={<RefreshCw className="h-4 w-4" />}
                            onClick={() =>
                                router.reload({
                                    only: ['syncedCategories', 'settingsTokenPresent', 'settingsTokenUpdatedAt', 'kpis', 'lastSyncAt', 'syncRuns'],
                                })
                            }
                        >
                            Refresh
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <StatCard label="Categories synced" value={kpis.categoriesSynced} icon={Tag} color="brand" />
                    <StatCard label="Products imported" value={kpis.productsImported} icon={Boxes} color="accent" />
                    <StatCard label="Pending queue" value={kpis.pendingQueue} icon={ListTodo} color="warning" />
                    <StatCard label="Failed jobs" value={kpis.failedJobs} icon={TriangleAlert} color="danger" />
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Token management</p>
                                <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Woohoo bearer token</p>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    Stored in settings (encrypted-at-rest).
                                </p>
                            </div>
                            {badgeForToken}
                        </CardHeader>
                        <CardBody>
                            <div className="grid gap-3 sm:grid-cols-2">
                                <div className="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Last generated</p>
                                    <p className="mt-2 font-semibold">
                                        {settingsTokenUpdatedAt ? new Date(settingsTokenUpdatedAt).toLocaleString() : '—'}
                                    </p>
                                </div>
                                <div className="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Security</p>
                                    <p className="mt-2 flex items-center gap-2 font-semibold">
                                        <Shield className="h-4 w-4 text-emerald-600" aria-hidden="true" />
                                        Encrypted storage
                                    </p>
                                </div>
                            </div>

                            <div className="mt-5 flex flex-wrap gap-3">
                                <Button
                                    variant="primary"
                                    leftIcon={<CircleCheck className="h-4 w-4" />}
                                    disabled={busy !== null}
                                    onClick={() => runAction('get-token', '/panel/woohoo/get-token', {})}
                                >
                                    {busy === 'get-token' ? 'Generating…' : 'Generate new token'}
                                </Button>
                                <Button
                                    variant="muted"
                                    leftIcon={<Database className="h-4 w-4" />}
                                    onClick={() => router.reload({ only: ['syncRuns', 'kpis', 'lastSyncAt'] })}
                                >
                                    View latest runs
                                </Button>
                            </div>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Category sync</p>
                            <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Catalog refresh</p>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Fetch products and queue sync jobs for upstream refresh.
                            </p>
                        </CardHeader>
                        <CardBody>
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    variant="muted"
                                    disabled={busy !== null}
                                    onClick={() => runAction('fetch-categories', '/panel/woohoo/fetch-categories', {})}
                                >
                                    {busy === 'fetch-categories' ? 'Fetching…' : 'Fetch categories'}
                                </Button>
                            </div>

                            <div className="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <Select
                                    label="Synced category"
                                    value={selectedSyncedCategoryId}
                                    onChange={(e) => setSelectedSyncedCategoryId(e.target.value)}
                                >
                                    <option value="" disabled>
                                        Select…
                                    </option>
                                    {categories.map((c) => (
                                        <option key={c.id} value={String(c.id)}>
                                            {c.name} (#{c.external_id})
                                        </option>
                                    ))}
                                </Select>

                                <div className="flex items-end gap-3">
                                    <Button
                                        className="w-full"
                                        variant="primary"
                                        disabled={busy !== null || !selectedSyncedCategoryId}
                                        onClick={() =>
                                            runAction('fetch-products', '/panel/woohoo/fetch-products', {
                                                synced_category_id: Number(selectedSyncedCategoryId),
                                            })
                                        }
                                    >
                                        {busy === 'fetch-products' ? 'Fetching…' : 'Fetch products'}
                                    </Button>
                                </div>
                            </div>

                            <div className="mt-4 flex flex-wrap gap-3">
                                <Button
                                    variant="secondary"
                                    disabled={busy !== null || !selectedSyncedCategoryId}
                                    onClick={() =>
                                        runAction('sync-category', '/panel/woohoo/sync-category', {
                                            synced_category_id: Number(selectedSyncedCategoryId),
                                        })
                                    }
                                >
                                    {busy === 'sync-category' ? 'Queued…' : 'Sync all (queued)'}
                                </Button>
                            </div>

                            <div className="mt-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                                <div className="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">Queued jobs</p>
                                    <Badge tone="neutral">{runs.length} recent</Badge>
                                </div>
                                <div className="max-h-[240px] overflow-auto">
                                    {runs.length === 0 ? (
                                        <div className="p-4 text-sm text-gray-600">No sync runs yet.</div>
                                    ) : (
                                        <table className="w-full text-left text-sm">
                                            <thead className="sticky top-0 bg-white text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                                                <tr>
                                                    <th className="px-4 py-3">Run</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3">Stats</th>
                                                    <th className="px-4 py-3">Time</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                                {runs.map((r) => (
                                                    <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-gray-950">
                                                        <td className="px-4 py-3">
                                                            <p className="font-semibold text-gray-900 dark:text-gray-100">#{r.id}</p>
                                                            <p className="text-xs text-gray-500">{r.job_type}</p>
                                                        </td>
                                                        <td className="px-4 py-3">{badgeForRunStatus(r.status)}</td>
                                                        <td className="px-4 py-3 text-xs text-gray-600">
                                                            F:{r.records_fetched} C:{r.records_created} U:{r.records_updated}{' '}
                                                            {r.records_failed > 0 ? <span className="text-red-600">X:{r.records_failed}</span> : null}
                                                            {r.last_error_message ? (
                                                                <p className="mt-1 line-clamp-1 text-red-600">{r.last_error_message}</p>
                                                            ) : null}
                                                        </td>
                                                        <td className="px-4 py-3 text-xs text-gray-600">
                                                            <div className="flex items-center gap-2">
                                                                <Clock className="h-3.5 w-3.5 text-gray-400" aria-hidden="true" />
                                                                <span>
                                                                    {r.completed_at
                                                                        ? new Date(r.completed_at).toLocaleTimeString()
                                                                        : r.started_at
                                                                          ? 'Running…'
                                                                          : 'Queued…'}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}
                                </div>
                            </div>
                        </CardBody>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Product SKU panel</p>
                        <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Sync by SKU</p>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Fetch details, preview response JSON, and queue a SKU sync.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid grid-cols-1 gap-4 lg:grid-cols-12">
                            <div className="lg:col-span-5">
                                <Input
                                    label="SKU"
                                    value={detailsSku}
                                    onChange={(e) => setDetailsSku(e.target.value)}
                                    placeholder="Paste a SKU from fetched products"
                                />
                            </div>
                            <div className="flex flex-wrap items-end gap-3 lg:col-span-7">
                                <Button
                                    variant="muted"
                                    disabled={busy !== null || !detailsSku.trim()}
                                    onClick={() =>
                                        runAction('fetch-product-details', '/panel/woohoo/fetch-product-details', {
                                            sku: detailsSku.trim(),
                                        })
                                    }
                                >
                                    {busy === 'fetch-product-details' ? 'Fetching…' : 'Fetch details'}
                                </Button>
                                <Button
                                    variant="secondary"
                                    disabled={busy !== null || !detailsSku.trim()}
                                    onClick={() =>
                                        runAction('sync-sku', '/panel/woohoo/sync-sku', {
                                            sku: detailsSku.trim(),
                                        })
                                    }
                                >
                                    {busy === 'sync-sku' ? 'Queued…' : 'Sync SKU (queued)'}
                                </Button>
                                <Button
                                    variant="primary"
                                    disabled={busy !== null}
                                    onClick={() => runAction('sync-all-product-details', '/panel/woohoo/sync-all-product-details', {})}
                                >
                                    {busy === 'sync-all-product-details' ? 'Queued…' : 'Sync all Woohoo product details (queued)'}
                                </Button>
                                <Button
                                    variant="muted"
                                    disabled={!result}
                                    onClick={() => {
                                        try {
                                            navigator.clipboard.writeText(JSON.stringify(result, null, 2));
                                        } catch {
                                            // ignore
                                        }
                                    }}
                                >
                                    Copy JSON
                                </Button>
                            </div>
                        </div>
                    </CardBody>
                </Card>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Order test panel</p>
                            <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Create a test order</p>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Use this to validate payload wiring (panel tool).
                            </p>
                        </CardHeader>
                        <CardBody>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-4">
                                <div className="sm:col-span-2">
                                    <Input label="SKU" value={testSku} onChange={(e) => setTestSku(e.target.value)} placeholder="e.g. WOOGC-123" />
                                </div>
                                <Input label="Amount" value={testAmount} onChange={(e) => setTestAmount(e.target.value)} />
                                <Input label="Qty" value={testQty} onChange={(e) => setTestQty(e.target.value)} />
                            </div>
                            <div className="mt-4 flex flex-wrap gap-3">
                                <Button
                                    variant="primary"
                                    disabled={busy !== null || !testSku.trim()}
                                    onClick={() =>
                                        runAction('test-order', '/panel/woohoo/test-order', {
                                            sku: testSku.trim(),
                                            amount: Number(testAmount || 0),
                                            qty: Number(testQty || 1),
                                        })
                                    }
                                >
                                    {busy === 'test-order' ? 'Running…' : 'Create test order'}
                                </Button>
                                <Button variant="muted" disabled={!testSku.trim()}>
                                    Validate payload
                                </Button>
                            </div>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader className="flex items-start justify-between gap-4">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Diagnostics</p>
                                <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Last response</p>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                    JSON payload from the most recent action.
                                </p>
                            </div>
                            {busy ? <Badge tone="brand">Running: {busy}</Badge> : <Badge tone="neutral">Idle</Badge>}
                        </CardHeader>
                        <CardBody>
                            <pre className="max-h-[420px] overflow-auto rounded-2xl bg-gray-50 p-4 text-xs text-gray-900 ring-1 ring-inset ring-gray-200 dark:bg-gray-950 dark:text-gray-100 dark:ring-gray-800">
                                {result ? JSON.stringify(result, null, 2) : '—'}
                            </pre>
                            <div className="mt-4 flex items-center justify-between">
                                <Link href="/panel/providers" className="text-sm font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-300">
                                    &larr; Providers hub
                                </Link>
                                <Badge tone="neutral">Responses use ResponsePayload</Badge>
                            </div>
                        </CardBody>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
