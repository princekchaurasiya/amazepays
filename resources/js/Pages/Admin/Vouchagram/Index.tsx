import React, { useEffect, useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Gift, RefreshCw, Send, Download, Search, Store, Database, Activity, Copy, Check } from 'lucide-react';

/** Sanitized row from GET /panel/vouchagram/fetch-brands */
export type VouchagramBrandRow = {
    BrandProductCode: string;
    BrandName?: string;
    Brandtype?: string;
    BrandImage?: string;
    DenomType?: string;
    denominationList?: string | null;
    MinValue?: number | null;
    MaxValue?: number | null;
    stockAvailable?: string | boolean;
    Category?: string;
    RedemptionType?: string;
    OnlineRedemptionUrl?: string;
};

type Props = {
    sendConfigured: boolean;
    pullConfigured: boolean;
};

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

const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: Activity },
    { id: 'brands', label: 'Brands', icon: Search },
    { id: 'send', label: 'Send (B2C)', icon: Send },
    { id: 'pull', label: 'Pull (B2B)', icon: Download },
    { id: 'status', label: 'Check status', icon: RefreshCw },
    { id: 'stores', label: 'Store list', icon: Store },
    { id: 'sync', label: 'Catalog sync', icon: Database },
] as const;

const FETCH_BRANDS_URL = '/panel/vouchagram/fetch-brands';
const SYNC_CATALOG_URL = '/panel/vouchagram/sync-catalog';

type CatalogSyncStats = { created: number; updated: number; deactivated: number };

function SyncStatsCard({ stats }: { stats: CatalogSyncStats }) {
    return (
        <div className="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900/50 dark:bg-green-950/30">
            <h4 className="text-sm font-semibold text-green-800 dark:text-green-200">Catalog sync complete</h4>
            <ul className="mt-2 flex flex-wrap gap-6 text-sm text-green-900 dark:text-green-100">
                <li>
                    Created: <strong>{stats.created}</strong>
                </li>
                <li>
                    Updated: <strong>{stats.updated}</strong>
                </li>
                <li>
                    Deactivated: <strong>{stats.deactivated}</strong>
                </li>
            </ul>
            <p className="mt-2 text-xs text-green-700 dark:text-green-300">
                New products are <strong>visible on the storefront by default</strong>. Turn off &quot;Visible on storefront&quot; in Products if you
                want to hide one.
            </p>
        </div>
    );
}

function formatBrandPricing(row: VouchagramBrandRow): string {
    const dt = (row.DenomType ?? '').toString().toUpperCase();
    const list = row.denominationList;
    const min = row.MinValue;
    const max = row.MaxValue;
    if (dt === 'D' || (list == null && (min != null || max != null))) {
        return `Range: ${min ?? '—'} – ${max ?? '—'}`;
    }
    if (list != null && String(list).trim() !== '') {
        return `Fixed: ${list}`;
    }
    if (min != null || max != null) {
        return `Range: ${min ?? '—'} – ${max ?? '—'}`;
    }
    return '—';
}

function redemptionLabel(t: string | undefined): string {
    switch (String(t)) {
        case '1':
            return 'Online';
        case '2':
            return 'Offline';
        case '3':
            return 'Online + offline';
        default:
            return t ? String(t) : '—';
    }
}

function stockOk(v: string | boolean | undefined): boolean {
    if (typeof v === 'boolean') return v;
    return String(v).toLowerCase() === 'true';
}

export default function VouchagramIndex({ sendConfigured, pullConfigured }: Props) {
    const [active, setActive] = useState<(typeof tabs)[number]['id']>('dashboard');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<Record<string, unknown> | null>(null);
    const [brandRows, setBrandRows] = useState<VouchagramBrandRow[] | null>(null);
    const [copiedCode, setCopiedCode] = useState<string | null>(null);
    const [syncLoading, setSyncLoading] = useState(false);
    const [syncStats, setSyncStats] = useState<CatalogSyncStats | null>(null);
    const [lastSyncContext, setLastSyncContext] = useState<'brands' | 'sync' | null>(null);

    const [brandMode, setBrandMode] = useState<'send' | 'pull'>('send');
    const [brandCode, setBrandCode] = useState('');

    const [sendForm, setSendForm] = useState({
        brand_product_code: '',
        external_order_id: `TEST_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}_${Math.random().toString(36).slice(2, 8)}`,
        quantity: 1,
        denomination: '500',
        customer_first_name: 'John',
        customer_last_name: 'Doe',
        email: '',
        mobile: '',
    });

    const [pullForm, setPullForm] = useState({
        brand_product_code: '',
        external_order_id: `PULL_${new Date().toISOString().slice(0, 10).replace(/-/g, '')}_${Math.random().toString(36).slice(2, 8)}`,
        quantity: 1,
        denomination: '500',
    });

    const [statusForm, setStatusForm] = useState({
        mode: 'send' as 'send' | 'pull',
        external_order_id: '',
    });

    const [stockForm, setStockForm] = useState({
        mode: 'send' as 'send' | 'pull',
        brand_product_code: '',
        denomination: '500',
    });

    const [storeForm, setStoreForm] = useState({
        mode: 'send' as 'send' | 'pull',
        brand_product_code: '',
        shop: '',
    });

    useEffect(() => {
        if (active !== 'brands') {
            setBrandRows(null);
        }
    }, [active]);

    const runSyncCatalog = async (preserveBrandTable: boolean) => {
        setError(null);
        setSyncStats(null);
        setLastSyncContext(null);
        if (preserveBrandTable) {
            setSyncLoading(true);
        } else {
            setLoading(true);
            setResult(null);
            setBrandRows(null);
        }
        try {
            const data = await postJson(SYNC_CATALOG_URL, {});
            if (data.success === false && data.message) {
                setError(String(data.message));
            }
            if (data.success === true && data.stats && typeof data.stats === 'object' && !Array.isArray(data.stats)) {
                const s = data.stats as Record<string, unknown>;
                setSyncStats({
                    created: Number(s.created ?? 0),
                    updated: Number(s.updated ?? 0),
                    deactivated: Number(s.deactivated ?? 0),
                });
                setLastSyncContext(preserveBrandTable ? 'brands' : 'sync');
            }
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Request failed');
        } finally {
            if (preserveBrandTable) {
                setSyncLoading(false);
            } else {
                setLoading(false);
            }
        }
    };

    const run = async (url: string, body: Record<string, unknown>) => {
        setLoading(true);
        setError(null);
        setResult(null);
        setBrandRows(null);
        setSyncStats(null);
        setLastSyncContext(null);
        try {
            const data = await postJson(url, body);
            if (data.message && data.success === false) {
                setError(String(data.message));
            }
            setResult(data);
            if (url === FETCH_BRANDS_URL) {
                if (data.success === true && Array.isArray(data.data)) {
                    setBrandRows(data.data as VouchagramBrandRow[]);
                } else {
                    setBrandRows(null);
                }
            }
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Request failed');
        } finally {
            setLoading(false);
        }
    };

    const copyCode = async (code: string) => {
        try {
            await navigator.clipboard.writeText(code);
            setCopiedCode(code);
            setTimeout(() => setCopiedCode(null), 2000);
        } catch {
            /* ignore */
        }
    };

    const showBrandsTable = active === 'brands' && brandRows !== null;
    const hideRawJsonForBrands =
        showBrandsTable && result?.success === true && Array.isArray(result.data);
    const hideRawJsonForSync =
        result?.success === true &&
        result.stats != null &&
        typeof result.stats === 'object' &&
        !Array.isArray(result.stats);

    return (
        <AdminLayout>
            <Head title="Vouchagram" />
            <div className="space-y-6">
                <div className="flex items-center gap-3">
                    <Gift className="h-8 w-8 text-indigo-600" />
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Vouchagram API</h1>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Send Voucher (B2C) and Pull Voucher (B2B) — test tools and catalog sync
                        </p>
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
                    {tabs.map((t) => {
                        const Icon = t.icon;
                        return (
                            <button
                                key={t.id}
                                type="button"
                                onClick={() => setActive(t.id)}
                                className={`inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
                                    active === t.id
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200'
                                }`}
                            >
                                <Icon className="h-4 w-4" />
                                {t.label}
                            </button>
                        );
                    })}
                </div>

                {error && (
                    <div className="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                        {error}
                    </div>
                )}

                {active === 'dashboard' && (
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 className="font-semibold text-gray-900 dark:text-white">Send Voucher (B2C)</h3>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Credentials: {sendConfigured ? (
                                    <span className="text-green-600">configured</span>
                                ) : (
                                    <span className="text-amber-600">missing — set VOUCHAGRAM_SEND_* in .env</span>
                                )}
                            </p>
                        </div>
                        <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 className="font-semibold text-gray-900 dark:text-white">Pull Voucher (B2B)</h3>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Credentials: {pullConfigured ? (
                                    <span className="text-green-600">configured</span>
                                ) : (
                                    <span className="text-amber-600">missing — set VOUCHAGRAM_PULL_* in .env</span>
                                )}
                            </p>
                        </div>
                    </div>
                )}

                {active === 'brands' && (
                    <div className="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex flex-wrap gap-4">
                            <label className="flex items-center gap-2 text-sm">
                                Mode
                                <select
                                    value={brandMode}
                                    onChange={(e) => setBrandMode(e.target.value as 'send' | 'pull')}
                                    className="rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-900"
                                >
                                    <option value="send">Send (B2C credentials)</option>
                                    <option value="pull">Pull (B2B credentials)</option>
                                </select>
                            </label>
                            <input
                                type="text"
                                placeholder="BrandProductCode (optional, empty = all)"
                                value={brandCode}
                                onChange={(e) => setBrandCode(e.target.value)}
                                className="min-w-[240px] flex-1 rounded border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                            />
                            <button
                                type="button"
                                disabled={loading}
                                onClick={() => run(FETCH_BRANDS_URL, { mode: brandMode, brand_code: brandCode || null })}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Fetch brands
                            </button>
                        </div>
                        <div className="border-t border-gray-200 pt-4 dark:border-gray-600">
                            <h4 className="mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">Check stock</h4>
                            <div className="flex flex-wrap gap-4">
                                <select
                                    value={stockForm.mode}
                                    onChange={(e) => setStockForm({ ...stockForm, mode: e.target.value as 'send' | 'pull' })}
                                    className="rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-900"
                                >
                                    <option value="send">Send creds</option>
                                    <option value="pull">Pull creds</option>
                                </select>
                                <input
                                    type="text"
                                    placeholder="BrandProductCode"
                                    value={stockForm.brand_product_code}
                                    onChange={(e) => setStockForm({ ...stockForm, brand_product_code: e.target.value })}
                                    className="min-w-[200px] rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                />
                                <input
                                    type="text"
                                    placeholder="Denomination"
                                    value={stockForm.denomination}
                                    onChange={(e) => setStockForm({ ...stockForm, denomination: e.target.value })}
                                    className="w-32 rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                />
                                <button
                                    type="button"
                                    disabled={loading}
                                    onClick={() => run('/panel/vouchagram/check-stock', { ...stockForm })}
                                    className="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900 disabled:opacity-50 dark:bg-gray-600"
                                >
                                    Check stock
                                </button>
                            </div>
                        </div>

                        {showBrandsTable && (
                            <div className="mt-6 space-y-3">
                                <div className="flex flex-wrap items-center gap-2">
                                    <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">
                                        {brandRows!.length} brand{brandRows!.length === 1 ? '' : 's'}
                                    </span>
                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                        Long text fields (TNC, descriptions) are not returned for security.
                                    </span>
                                    <button
                                        type="button"
                                        disabled={loading || syncLoading}
                                        onClick={() => runSyncCatalog(true)}
                                        title="Fetches the full catalog from Vouchagram (same as Catalog sync tab) and upserts into the products table"
                                        className="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                                    >
                                        {syncLoading
                                            ? 'Syncing…'
                                            : `Sync ${brandRows!.length} listed brands to products`}
                                    </button>
                                </div>
                                {lastSyncContext === 'brands' && syncStats && (
                                    <SyncStatsCard stats={syncStats} />
                                )}
                                <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                                    <table className="w-full min-w-[900px] text-left text-sm">
                                        <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                            <tr>
                                                <th className="px-3 py-2">Image</th>
                                                <th className="px-3 py-2">Brand</th>
                                                <th className="px-3 py-2">Code</th>
                                                <th className="px-3 py-2">Type</th>
                                                <th className="px-3 py-2">Pricing</th>
                                                <th className="px-3 py-2">Stock</th>
                                                <th className="px-3 py-2">Category</th>
                                                <th className="px-3 py-2">Redemption</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                            {brandRows!.length === 0 ? (
                                                <tr>
                                                    <td colSpan={8} className="px-3 py-8 text-center text-gray-500">
                                                        No brands returned.
                                                    </td>
                                                </tr>
                                            ) : (
                                                brandRows!.map((row) => (
                                                    <tr key={row.BrandProductCode} className="bg-white dark:bg-gray-900/50">
                                                        <td className="px-3 py-2">
                                                            {row.BrandImage ? (
                                                                <img
                                                                    src={row.BrandImage}
                                                                    alt=""
                                                                    className="h-10 w-10 rounded object-contain bg-gray-50 dark:bg-gray-800"
                                                                />
                                                            ) : (
                                                                <span className="text-gray-400">—</span>
                                                            )}
                                                        </td>
                                                        <td className="px-3 py-2 font-medium text-gray-900 dark:text-white">
                                                            {row.BrandName ?? '—'}
                                                        </td>
                                                        <td className="px-3 py-2">
                                                            <div className="flex items-center gap-1">
                                                                <code className="max-w-[180px] truncate text-xs text-gray-800 dark:text-gray-200">
                                                                    {row.BrandProductCode}
                                                                </code>
                                                                <button
                                                                    type="button"
                                                                    title="Copy code"
                                                                    onClick={() => copyCode(row.BrandProductCode)}
                                                                    className="rounded p-1 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                                                                >
                                                                    {copiedCode === row.BrandProductCode ? (
                                                                        <Check className="h-3.5 w-3.5 text-green-600" />
                                                                    ) : (
                                                                        <Copy className="h-3.5 w-3.5" />
                                                                    )}
                                                                </button>
                                                            </div>
                                                        </td>
                                                        <td className="px-3 py-2">
                                                            <span className="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">
                                                                {row.Brandtype ?? '—'}
                                                            </span>
                                                        </td>
                                                        <td className="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">
                                                            {formatBrandPricing(row)}
                                                        </td>
                                                        <td className="px-3 py-2">
                                                            <span
                                                                className={`inline-block h-2.5 w-2.5 rounded-full ${
                                                                    stockOk(row.stockAvailable) ? 'bg-green-500' : 'bg-red-500'
                                                                }`}
                                                                title={String(row.stockAvailable ?? '')}
                                                            />
                                                        </td>
                                                        <td className="max-w-[160px] truncate px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                            {row.Category ?? '—'}
                                                        </td>
                                                        <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">
                                                            {redemptionLabel(row.RedemptionType)}
                                                        </td>
                                                    </tr>
                                                ))
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {active === 'send' && (
                    <div className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="grid gap-3 md:grid-cols-2">
                            {(['brand_product_code', 'external_order_id', 'denomination', 'email', 'mobile', 'customer_first_name', 'customer_last_name'] as const).map((field) => (
                                <label key={field} className="block text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">{field}</span>
                                    <input
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                        value={String((sendForm as any)[field] ?? '')}
                                        onChange={(e) => setSendForm({ ...sendForm, [field]: e.target.value })}
                                    />
                                </label>
                            ))}
                            <label className="block text-sm">
                                quantity (max 10)
                                <input
                                    type="number"
                                    min={1}
                                    max={10}
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                    value={sendForm.quantity}
                                    onChange={(e) => setSendForm({ ...sendForm, quantity: Number(e.target.value) })}
                                />
                            </label>
                        </div>
                        <button
                            type="button"
                            disabled={loading}
                            onClick={() => run('/panel/vouchagram/send-voucher', { ...sendForm })}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Send voucher
                        </button>
                    </div>
                )}

                {active === 'pull' && (
                    <div className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="grid gap-3 md:grid-cols-2">
                            {(['brand_product_code', 'external_order_id', 'denomination'] as const).map((field) => (
                                <label key={field} className="block text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">{field}</span>
                                    <input
                                        className="mt-1 w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                        value={String((pullForm as any)[field] ?? '')}
                                        onChange={(e) => setPullForm({ ...pullForm, [field]: e.target.value })}
                                    />
                                </label>
                            ))}
                            <label className="block text-sm">
                                quantity
                                <input
                                    type="number"
                                    min={1}
                                    max={10}
                                    className="mt-1 w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                                    value={pullForm.quantity}
                                    onChange={(e) => setPullForm({ ...pullForm, quantity: Number(e.target.value) })}
                                />
                            </label>
                        </div>
                        <button
                            type="button"
                            disabled={loading}
                            onClick={() => run('/panel/vouchagram/pull-voucher', { ...pullForm })}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Pull voucher
                        </button>
                    </div>
                )}

                {active === 'status' && (
                    <div className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex flex-wrap gap-4">
                            <select
                                value={statusForm.mode}
                                onChange={(e) => setStatusForm({ ...statusForm, mode: e.target.value as 'send' | 'pull' })}
                                className="rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-900"
                            >
                                <option value="send">Send voucher status</option>
                                <option value="pull">Pull voucher status</option>
                            </select>
                            <input
                                type="text"
                                placeholder="External order id"
                                value={statusForm.external_order_id}
                                onChange={(e) => setStatusForm({ ...statusForm, external_order_id: e.target.value })}
                                className="min-w-[240px] flex-1 rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <button
                                type="button"
                                disabled={loading}
                                onClick={() =>
                                    run(
                                        statusForm.mode === 'send' ? '/panel/vouchagram/check-send-status' : '/panel/vouchagram/check-pull-status',
                                        { external_order_id: statusForm.external_order_id }
                                    )
                                }
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Check status
                            </button>
                        </div>
                    </div>
                )}

                {active === 'stores' && (
                    <div className="space-y-4 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div className="flex flex-wrap gap-4">
                            <select
                                value={storeForm.mode}
                                onChange={(e) => setStoreForm({ ...storeForm, mode: e.target.value as 'send' | 'pull' })}
                                className="rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-900"
                            >
                                <option value="send">Send creds</option>
                                <option value="pull">Pull creds</option>
                            </select>
                            <input
                                type="text"
                                placeholder="BrandProductCode"
                                value={storeForm.brand_product_code}
                                onChange={(e) => setStoreForm({ ...storeForm, brand_product_code: e.target.value })}
                                className="min-w-[200px] rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <input
                                type="text"
                                placeholder="shop (optional)"
                                value={storeForm.shop}
                                onChange={(e) => setStoreForm({ ...storeForm, shop: e.target.value })}
                                className="w-32 rounded border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-900"
                            />
                            <button
                                type="button"
                                disabled={loading}
                                onClick={() => run('/panel/vouchagram/store-list', { ...storeForm })}
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                Load stores
                            </button>
                        </div>
                    </div>
                )}

                {active === 'sync' && (
                    <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                            Runs <code className="rounded bg-gray-100 px-1 dark:bg-gray-900">CatalogSyncService</code> for{' '}
                            <code>vouchagram</code> and fills product URLs/slugs. Requires <code>providers.sync</code> permission.
                        </p>
                        <button
                            type="button"
                            disabled={loading || syncLoading}
                            onClick={() => runSyncCatalog(false)}
                            className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            {loading ? 'Syncing…' : 'Sync catalog now'}
                        </button>
                        {lastSyncContext === 'sync' && syncStats && <SyncStatsCard stats={syncStats} />}
                    </div>
                )}

                {result !== null && !hideRawJsonForBrands && !hideRawJsonForSync && (
                    <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <h4 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">Response</h4>
                        <pre className="max-h-[480px] overflow-auto text-xs text-gray-800 dark:text-gray-200">
                            {JSON.stringify(result, null, 2)}
                        </pre>
                    </div>
                )}

                {loading && <p className="text-sm text-gray-500">Loading…</p>}
            </div>
        </AdminLayout>
    );
}
