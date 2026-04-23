import React, { useMemo, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

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

export default function ValueDesignIndex({ configured, distributorId, routes, syncedProducts }: Props) {
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
            <Head title="Value Design" />
            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">Value Design</h1>
                    <p className="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-400">
                        Admin integration console for VD UAT operations. Generate token first, then run downstream actions.
                    </p>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <p className="text-sm">
                        Configuration:{' '}
                        <span className={configured ? 'text-emerald-600' : 'text-amber-600'}>
                            {configured ? 'Configured' : 'Missing env/config'}
                        </span>
                    </p>
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">Distributor ID: {distributorId || 'Not set'}</p>
                </div>

                {error && (
                    <div className="rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                        {error}
                    </div>
                )}

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Step 1: Generate token</h2>
                    <div className="mt-3 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            disabled={loading}
                            onClick={() => run(routes.token, {})}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Generate token
                        </button>
                        <input
                            value={token}
                            onChange={(e) => setToken(e.target.value)}
                            placeholder="Paste token here (or copy from response)"
                            className="min-w-[320px] flex-1 rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                        />
                    </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Step 2: Brand & store lookups</h2>
                    <div className="mt-3 flex flex-wrap gap-3">
                        <input
                            value={brandCode}
                            onChange={(e) => setBrandCode(e.target.value)}
                            placeholder="BrandCode (optional)"
                            className="min-w-[240px] rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                        />
                        <button
                            type="button"
                            disabled={loading || !token}
                            onClick={tokenRequired[0].action}
                            className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-gray-600"
                        >
                            Get brands
                        </button>
                        <button
                            type="button"
                            disabled={loading || !token}
                            onClick={tokenRequired[1].action}
                            className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-gray-600"
                        >
                            Get stores
                        </button>
                        <button
                            type="button"
                            disabled={syncBusy}
                            onClick={syncCatalog}
                            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            {syncBusy ? 'Syncing products...' : 'Sync brands to products'}
                        </button>
                    </div>
                    <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Use <strong>Sync brands to products</strong> to insert/update VD brands in the `products` table.
                    </p>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Step 3: EVC operations</h2>
                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                        <input
                            value={orderId}
                            onChange={(e) => setOrderId(e.target.value)}
                            placeholder="order_id"
                            className="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                        />
                        <input
                            value={requestRefNo}
                            onChange={(e) => setRequestRefNo(e.target.value)}
                            placeholder="request_ref_no"
                            className="rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                        />
                    </div>
                    <textarea
                        value={payload}
                        onChange={(e) => setPayload(e.target.value)}
                        rows={12}
                        className="mt-3 w-full rounded border border-gray-300 px-3 py-2 text-xs dark:border-gray-600 dark:bg-gray-900"
                    />
                    <div className="mt-3 flex flex-wrap gap-3">
                        <button
                            type="button"
                            disabled={loading || !token}
                            onClick={tokenRequired[2].action}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Get EVC
                        </button>
                        <button
                            type="button"
                            disabled={loading || !token || !orderId || !requestRefNo}
                            onClick={tokenRequired[3].action}
                            className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-gray-600"
                        >
                            Get EVC status
                        </button>
                        <button
                            type="button"
                            disabled={loading || !token || !orderId || !requestRefNo}
                            onClick={tokenRequired[4].action}
                            className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white disabled:opacity-50 dark:bg-gray-600"
                        >
                            Get activated EVC
                        </button>
                        <button
                            type="button"
                            disabled={loading || !token}
                            onClick={tokenRequired[5].action}
                            className="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Wallet balance
                        </button>
                    </div>
                </div>

                <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Synced products (products table)</h2>
                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Showing latest {syncedProducts.length} products where <code>source_provider = value_design</code>.
                    </p>
                    <div className="mt-3 overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
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
                </div>

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
