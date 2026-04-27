import React, { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import StatCard from '@/Components/Admin/StatCard';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import { Card, CardBody, CardHeader } from '@/Components/UI/Card';
import Input from '@/Components/UI/Input';
import { Boxes, KeyRound, RefreshCw, ShieldAlert, Wallet } from 'lucide-react';

type Routes = {
    token: string;
    brands: string;
    stores: string;
    evc: string;
    status: string;
    activated: string;
    wallet: string;
    syncCatalog: string;
};

type Props = {
    configured: boolean;
    distributorId: string;
    routes: Routes;
    kpis: { productsImported: number; pendingQueue: number; failedJobs: number };
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
    syncedProducts: Array<{
        id: number;
        sku: string;
        product_name: string | null;
        denomination: number | null;
        selling_price: number | null;
        show_product: boolean;
        catalog_audience: string | null;
        updated_at: string | null;
    }>;
};

type JsonValue = string | number | boolean | null | JsonObject | JsonValue[];
type JsonObject = { [key: string]: JsonValue };

function csrf(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

async function postJson(url: string, body: Record<string, unknown>): Promise<Record<string, unknown>> {
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    });

    return res.json();
}

function titleCase(key: string): string {
    return key
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

function scalarLabel(value: JsonValue): string {
    if (value === null) return 'null';
    if (typeof value === 'boolean') return value ? 'true' : 'false';
    return String(value);
}

function ScalarGrid({ data }: { data: JsonObject }) {
    const entries = Object.entries(data).filter(([, v]) => v === null || ['string', 'number', 'boolean'].includes(typeof v));
    if (entries.length === 0) return null;
    return (
        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            {entries.map(([key, value]) => (
                <div key={key} className="rounded border border-gray-100 bg-gray-50 px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                    <p className="font-medium text-gray-600 dark:text-gray-300">{titleCase(key)}</p>
                    <p className="mt-1 break-words text-gray-900 dark:text-gray-100">{scalarLabel(value as JsonValue)}</p>
                </div>
            ))}
        </div>
    );
}

function ObjectSections({ data }: { data: JsonObject }) {
    const objectEntries = Object.entries(data).filter(([, v]) => v !== null && typeof v === 'object' && !Array.isArray(v));
    if (objectEntries.length === 0) return null;
    return (
        <div className="space-y-3">
            {objectEntries.map(([key, value]) => (
                <div key={key} className="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{titleCase(key)}</p>
                    <ScalarGrid data={value as JsonObject} />
                </div>
            ))}
        </div>
    );
}

function ArraySections({ data }: { data: JsonObject }) {
    const arrayEntries = Object.entries(data).filter(([, v]) => Array.isArray(v));
    if (arrayEntries.length === 0) return null;
    return (
        <div className="space-y-3">
            {arrayEntries.map(([key, value]) => {
                const rows = value as JsonValue[];
                return (
                    <div key={key} className="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {titleCase(key)} ({rows.length})
                        </p>
                        <div className="space-y-2">
                            {rows.slice(0, 20).map((row, idx) => (
                                <div key={`${key}-${idx}`} className="rounded border border-gray-100 bg-gray-50 px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-900">
                                    {row !== null && typeof row === 'object' && !Array.isArray(row) ? (
                                        <ScalarGrid data={row as JsonObject} />
                                    ) : (
                                        <span className="text-gray-900 dark:text-gray-100">{scalarLabel(row)}</span>
                                    )}
                                </div>
                            ))}
                            {rows.length > 20 && <p className="text-xs text-gray-500">Showing first 20 items...</p>}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

export default function ValueDesignIndex({ configured, distributorId, routes, syncedProducts, kpis, lastSyncAt, syncRuns }: Props) {
    const [token, setToken] = useState('');
    const [brandCode, setBrandCode] = useState('');
    const [orderId, setOrderId] = useState('');
    const [requestRefNo, setRequestRefNo] = useState('');
    const [payload, setPayload] = useState(
        JSON.stringify(
            {
                order_id: 'ORD_TEST_001',
                distributor_id: distributorId || 'VDIDAmazepay',
                sku_code: '',
                no_of_card: 1,
                amount: '100',
                receiptNo: 'RCPT001',
                reqId: 'REQ001',
                firstname: 'Test',
                lastname: 'User',
                email: 'test@example.com',
                mobile_no: '+919999999999',
                address: 'Address',
                city: 'Mumbai',
                state: 'Maharashtra',
                country: 'IN',
                pincode: '400001',
                curr: '356',
            },
            null,
            2
        )
    );
    const [loading, setLoading] = useState(false);
    const [syncBusy, setSyncBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<Record<string, unknown> | null>(null);
    const runs = useMemo(() => syncRuns ?? [], [syncRuns]);

    const badgeForRunStatus = (status: string) => {
        const key = String(status || '').toLowerCase();
        if (key === 'succeeded') return <Badge tone="success">Succeeded</Badge>;
        if (key === 'running') return <Badge tone="brand">Running</Badge>;
        if (key === 'queued') return <Badge tone="warning">Queued</Badge>;
        if (key === 'failed') return <Badge tone="danger">Failed</Badge>;
        return <Badge tone="neutral">{status || 'Unknown'}</Badge>;
    };

    const normalizedData = (() => {
        if (!result) return null;
        const data = result.data as JsonValue;
        if (!data) return null;
        if (typeof data !== 'object' || Array.isArray(data)) return { value: data };
        const dataObj = data as JsonObject;
        if (dataObj.raw && typeof dataObj.raw === 'object' && !Array.isArray(dataObj.raw)) {
            return dataObj.raw as JsonObject;
        }
        return dataObj;
    })();

    const tokenRequired = useMemo(
        () => [
            { label: 'Get brands', action: () => run(routes.brands, { token, brand_code: brandCode || null }) },
            { label: 'Get stores', action: () => run(routes.stores, { token, brand_code: brandCode || null }) },
            {
                label: 'Get EVC',
                action: () => {
                    const parsed = JSON.parse(payload) as Record<string, unknown>;
                    return run(routes.evc, { token, payload: parsed });
                },
            },
            { label: 'Get EVC status', action: () => run(routes.status, { token, order_id: orderId, request_ref_no: requestRefNo }) },
            { label: 'Get activated EVC', action: () => run(routes.activated, { token, order_id: orderId, request_ref_no: requestRefNo }) },
            { label: 'Get wallet balance', action: () => run(routes.wallet, { token }) },
        ],
        [token, brandCode, payload, orderId, requestRefNo, routes]
    );

    async function run(url: string, body: Record<string, unknown>) {
        setLoading(true);
        setError(null);
        setResult(null);
        try {
            const data = await postJson(url, body);
            if (data.success === false) {
                setError(String(data.message ?? 'Request failed.'));
            }
            if (url === routes.token && data.success === true) {
                const tokenValue = (data.data as Record<string, unknown> | undefined)?.token;
                if (typeof tokenValue === 'string' && tokenValue !== '') {
                    setToken(tokenValue);
                }
            }
            setResult(data);
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Request failed.');
        } finally {
            setLoading(false);
        }
    }

    async function syncCatalog() {
        setSyncBusy(true);
        setError(null);
        setResult(null);
        try {
            const data = await postJson(routes.syncCatalog, {});
            if (data.success === false) {
                setError(String(data.message ?? 'Catalog sync failed.'));
            }
            setResult(data);
            if (data.success === true) {
                router.reload({ only: ['syncedProducts'] });
            }
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Catalog sync failed.');
        } finally {
            setSyncBusy(false);
        }
    }

    return (
        <AdminLayout>
            <Head title="Value Design Sync Center" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Value Design</p>
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <h1 className="text-2xl font-semibold text-gray-900 dark:text-gray-100">Catalog Sync Center</h1>
                            {configured ? <Badge tone="success">Configured</Badge> : <Badge tone="warning">Needs setup</Badge>}
                        </div>
                        <p className="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                            Generate a token, run catalog sync, and perform EVC operations from one console.
                        </p>
                        <p className="mt-2 text-xs text-gray-500">Distributor ID: {distributorId || '—'}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-3">
                        {lastSyncAt ? (
                            <Badge tone="neutral">Last sync: {new Date(lastSyncAt).toLocaleString()}</Badge>
                        ) : (
                            <Badge tone="neutral">Last sync: —</Badge>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-4">
                    <StatCard label="Products imported" value={kpis.productsImported} icon={Boxes} color="accent" />
                    <StatCard label="Pending queue" value={kpis.pendingQueue} icon={RefreshCw} color="warning" />
                    <StatCard label="Failed jobs" value={kpis.failedJobs} icon={ShieldAlert} color="danger" />
                    <StatCard label="Token status" value={token ? 'Present' : '—'} icon={KeyRound} color="brand" />
                </div>

                {error && (
                    <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                        {error}
                    </div>
                )}

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Token management</p>
                            <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Generate token</p>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">Generate a token first, then run downstream calls.</p>
                        </CardHeader>
                        <CardBody>
                            <div className="flex flex-wrap items-end gap-3">
                                <Button variant="primary" disabled={loading} onClick={() => run(routes.token, {})}>
                                    {loading ? 'Generating…' : 'Generate token'}
                                </Button>
                                <div className="min-w-[280px] flex-1">
                                    <Input
                                        label="Token"
                                        value={token}
                                        onChange={(e) => setToken(e.target.value)}
                                        placeholder="Paste token here (or copy from response)"
                                    />
                                </div>
                            </div>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Catalog sync</p>
                            <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Sync brands to products</p>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                Inserts/updates Value Design brands into your `products` table.
                            </p>
                        </CardHeader>
                        <CardBody>
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="min-w-[220px]">
                                    <Input label="BrandCode (optional)" value={brandCode} onChange={(e) => setBrandCode(e.target.value)} placeholder="e.g. AMAZON" />
                                </div>
                                <Button variant="muted" disabled={loading || !token} onClick={tokenRequired[0].action}>
                                    Get brands
                                </Button>
                                <Button variant="muted" disabled={loading || !token} onClick={tokenRequired[1].action}>
                                    Get stores
                                </Button>
                                <Button variant="secondary" disabled={syncBusy} onClick={syncCatalog}>
                                    {syncBusy ? 'Syncing…' : 'Sync catalog'}
                                </Button>
                            </div>

                            <div className="mt-6 rounded-2xl border border-gray-200 dark:border-gray-800">
                                <div className="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                                    <p className="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent runs</p>
                                    <Badge tone="neutral">{runs.length} recent</Badge>
                                </div>
                                <div className="max-h-[240px] overflow-auto">
                                    {runs.length === 0 ? (
                                        <p className="p-4 text-sm text-gray-600">No runs yet.</p>
                                    ) : (
                                        <table className="w-full text-left text-sm">
                                            <thead className="sticky top-0 bg-white text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                                                <tr>
                                                    <th className="px-4 py-3">Run</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3">Counts</th>
                                                    <th className="px-4 py-3">Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                                {runs.map((r) => (
                                                    <tr key={r.id} className="hover:bg-gray-50 dark:hover:bg-gray-950">
                                                        <td className="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">#{r.id}</td>
                                                        <td className="px-4 py-3">{badgeForRunStatus(r.status)}</td>
                                                        <td className="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                                            C:{r.records_created} U:{r.records_updated}{' '}
                                                            {r.records_failed ? <span className="text-red-600">X:{r.records_failed}</span> : null}
                                                            {r.last_error_message ? (
                                                                <p className="mt-1 line-clamp-1 text-red-600">{r.last_error_message}</p>
                                                            ) : null}
                                                        </td>
                                                        <td className="px-4 py-3 text-xs text-gray-600">
                                                            {r.completed_at ? new Date(r.completed_at).toLocaleString() : '—'}
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
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">EVC operations</p>
                        <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">EVC tools</p>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Create EVC, check status, fetch activated EVC, and wallet balance.
                        </p>
                    </CardHeader>
                    <CardBody>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input label="order_id" value={orderId} onChange={(e) => setOrderId(e.target.value)} placeholder="ORD123" />
                            <Input label="request_ref_no" value={requestRefNo} onChange={(e) => setRequestRefNo(e.target.value)} placeholder="REF123" />
                        </div>
                        <textarea
                            value={payload}
                            onChange={(e) => setPayload(e.target.value)}
                            rows={12}
                            className="mt-4 w-full rounded-2xl border border-gray-300 bg-white px-4 py-3 text-xs text-gray-900 shadow-sm outline-none transition focus:border-product-primary focus:ring-2 focus:ring-product-primary/20 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100"
                        />
                        <div className="mt-4 flex flex-wrap gap-3">
                            <Button variant="primary" disabled={loading || !token} onClick={tokenRequired[2].action}>
                                Get EVC
                            </Button>
                            <Button variant="muted" disabled={loading || !token || !orderId || !requestRefNo} onClick={tokenRequired[3].action}>
                                Get status
                            </Button>
                            <Button variant="muted" disabled={loading || !token || !orderId || !requestRefNo} onClick={tokenRequired[4].action}>
                                Get activated EVC
                            </Button>
                            <Button variant="secondary" disabled={loading || !token} onClick={tokenRequired[5].action} leftIcon={<Wallet className="h-4 w-4" />}>
                                Wallet balance
                            </Button>
                        </div>
                    </CardBody>
                </Card>

                <Card>
                    <CardHeader>
                        <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Catalog</p>
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Synced products (products table)</h2>
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Showing latest {syncedProducts.length} products where <code>source_provider = value_design</code>.
                    </p>
                    </CardHeader>
                    <CardBody>
                    <div className="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                        <table className="w-full min-w-[850px] text-left text-sm">
                            <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                <tr>
                                    <th className="px-3 py-2">SKU</th>
                                    <th className="px-3 py-2">Product</th>
                                    <th className="px-3 py-2">Denomination</th>
                                    <th className="px-3 py-2">Selling</th>
                                    <th className="px-3 py-2">Audience</th>
                                    <th className="px-3 py-2">Visible</th>
                                    <th className="px-3 py-2">Updated</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                {syncedProducts.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-3 py-8 text-center text-gray-500">
                                            No Value Design products found yet. Click <strong>Sync brands to products</strong>.
                                        </td>
                                    </tr>
                                ) : (
                                    syncedProducts.map((row) => (
                                        <tr key={row.id} className="bg-white dark:bg-gray-900/50">
                                            <td className="px-3 py-2 font-mono text-xs">{row.sku}</td>
                                            <td className="px-3 py-2">{row.product_name ?? '—'}</td>
                                            <td className="px-3 py-2">{row.denomination ?? '—'}</td>
                                            <td className="px-3 py-2">{row.selling_price ?? '—'}</td>
                                            <td className="px-3 py-2">{row.catalog_audience ?? '—'}</td>
                                            <td className="px-3 py-2">
                                                <span
                                                    className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                                        row.show_product ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-900'
                                                    }`}
                                                >
                                                    {row.show_product ? 'Yes' : 'No'}
                                                </span>
                                            </td>
                                            <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">{row.updated_at ?? '—'}</td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    </CardBody>
                </Card>

                {result && (
                    <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div className="mb-3 flex items-center justify-between">
                            <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">Response</h3>
                            <span
                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                    result.success === true ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'
                                }`}
                            >
                                {result.success === true ? 'Success' : 'Error'}
                            </span>
                        </div>
                        {normalizedData && typeof normalizedData === 'object' && !Array.isArray(normalizedData) ? (
                            <div className="space-y-3">
                                <ScalarGrid data={normalizedData as JsonObject} />
                                <ObjectSections data={normalizedData as JsonObject} />
                                <ArraySections data={normalizedData as JsonObject} />
                            </div>
                        ) : null}
                        <details className="mt-3">
                            <summary className="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300">View raw JSON</summary>
                            <pre className="mt-2 max-h-[500px] overflow-auto rounded border border-gray-200 bg-white p-3 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {JSON.stringify(result, null, 2)}
                            </pre>
                        </details>
                    </div>
                )}
                {loading && <p className="text-sm text-gray-500">Loading...</p>}
                <p className="text-xs text-gray-500 dark:text-gray-400">
                    Storefront mapping note: synced VD products use provider key <code>value_design</code> with default audience mapping handled in product model scopes.
                </p>
            </div>
        </AdminLayout>
    );
}
