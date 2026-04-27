import React, { useEffect, useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Gift, RefreshCw, Send, Download, Search, Store, Database, Activity, Copy, Check, Archive } from 'lucide-react';
import StatCard from '@/Components/Admin/StatCard';
import Badge from '@/Components/UI/Badge';
import Button from '@/Components/UI/Button';
import { Card, CardBody, CardHeader } from '@/Components/UI/Card';

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
    canSyncCatalog?: boolean;
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

async function getJson(url: string): Promise<Record<string, unknown>> {
    const res = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });
    return res.json();
}

const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: Activity },
    { id: 'brands', label: 'Brands', icon: Search },
    { id: 'saved', label: 'Saved fetches', icon: Archive },
    { id: 'send', label: 'Send (B2C)', icon: Send },
    { id: 'pull', label: 'Pull (B2B)', icon: Download },
    { id: 'status', label: 'Check status', icon: RefreshCw },
    { id: 'stores', label: 'Store list', icon: Store },
    { id: 'sync', label: 'Catalog sync', icon: Database },
] as const;

const FETCH_BRANDS_URL = '/panel/vouchagram/fetch-brands';
const SYNC_CATALOG_URL = '/panel/vouchagram/sync-catalog';
const SYNC_FROM_SNAPSHOT_URL = '/panel/vouchagram/sync-catalog-from-snapshot';
const CATALOG_SNAPSHOTS_URL = '/panel/vouchagram/catalog-snapshots';

type CatalogSnapshotListRow = {
    id: number;
    mode: string;
    brand_product_code_filter: string | null;
    item_count: number;
    fetched_at: string;
};

type PaginateMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

type CatalogSyncStats = { created: number; updated: number; deactivated: number };
type JsonValue = string | number | boolean | null | JsonObject | JsonValue[];
type JsonObject = { [key: string]: JsonValue };

function SyncStatsCard({ stats, mode }: { stats: CatalogSyncStats; mode: 'send' | 'pull' | null }) {
    const footer =
        mode === 'pull' ? (
            <p className="mt-2 text-xs text-green-700 dark:text-green-300">
                These items are meant for <strong>business / admin</strong> use. They stay off your public website unless you choose to show them under
                Products.
            </p>
        ) : (
            <p className="mt-2 text-xs text-green-700 dark:text-green-300">
                These items can appear on your <strong>public store</strong>. You can hide any product later in the Products screen.
            </p>
        );

    return (
        <div className="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-900/50 dark:bg-green-950/30">
            <h4 className="text-sm font-semibold text-green-800 dark:text-green-200">Import finished</h4>
            <ul className="mt-2 flex flex-wrap gap-6 text-sm text-green-900 dark:text-green-100">
                <li>
                    New products: <strong>{stats.created}</strong>
                </li>
                <li>
                    Updated: <strong>{stats.updated}</strong>
                </li>
                <li>
                    No longer listed: <strong>{stats.deactivated}</strong>
                </li>
            </ul>
            <p className="mt-2 text-xs text-green-600 dark:text-green-400">
                <strong>Send</strong> import = public storefront catalog. <strong>Pull</strong> import = B2B / admin catalog.
            </p>
            {footer}
        </div>
    );
}

function BrandDataTable({
    rows,
    copiedCode,
    onCopy,
}: {
    rows: VouchagramBrandRow[];
    copiedCode: string | null;
    onCopy: (code: string) => void;
}) {
    return (
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
                    {rows.length === 0 ? (
                        <tr>
                            <td colSpan={8} className="px-3 py-8 text-center text-gray-500">
                                No products returned.
                            </td>
                        </tr>
                    ) : (
                        rows.map((row) => (
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
                                <td className="px-3 py-2 font-medium text-gray-900 dark:text-white">{row.BrandName ?? '—'}</td>
                                <td className="px-3 py-2">
                                    <div className="flex items-center gap-1">
                                        <code className="max-w-[180px] truncate text-xs text-gray-800 dark:text-gray-200">
                                            {row.BrandProductCode}
                                        </code>
                                        <button
                                            type="button"
                                            title="Copy code"
                                            onClick={() => onCopy(row.BrandProductCode)}
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
                                    <span className="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">{row.Brandtype ?? '—'}</span>
                                </td>
                                <td className="px-3 py-2 text-xs text-gray-700 dark:text-gray-300">{formatBrandPricing(row)}</td>
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
                                <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-400">{redemptionLabel(row.RedemptionType)}</td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
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
                <div key={key} className="rounded border border-gray-100 bg-white px-3 py-2 text-xs dark:border-gray-700 dark:bg-gray-800">
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

export default function VouchagramIndex({ sendConfigured, pullConfigured, canSyncCatalog = false, kpis, lastSyncAt, syncRuns }: Props) {
    const [active, setActive] = useState<(typeof tabs)[number]['id']>('dashboard');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [result, setResult] = useState<Record<string, unknown> | null>(null);
    const [brandRows, setBrandRows] = useState<VouchagramBrandRow[] | null>(null);
    const [copiedCode, setCopiedCode] = useState<string | null>(null);
    const [brandsSendBusy, setBrandsSendBusy] = useState(false);
    const [brandsPullBusy, setBrandsPullBusy] = useState(false);
    const [catalogSendBusy, setCatalogSendBusy] = useState(false);
    const [catalogPullBusy, setCatalogPullBusy] = useState(false);
    const [snapshotImportBusy, setSnapshotImportBusy] = useState(false);
    const [syncStats, setSyncStats] = useState<CatalogSyncStats | null>(null);
    const [lastSyncContext, setLastSyncContext] = useState<'brands' | 'sync' | null>(null);
    const [lastSyncMode, setLastSyncMode] = useState<'send' | 'pull' | null>(null);
    /** Partner product list shown on Catalog sync tab after a successful import. */
    const [syncTabBrandRows, setSyncTabBrandRows] = useState<VouchagramBrandRow[] | null>(null);
    const [syncTabActivityLog, setSyncTabActivityLog] = useState<string[]>([]);

    const [savedListFilterMode, setSavedListFilterMode] = useState<'all' | 'send' | 'pull'>('all');
    const [savedList, setSavedList] = useState<CatalogSnapshotListRow[] | null>(null);
    const [savedListMeta, setSavedListMeta] = useState<PaginateMeta | null>(null);
    const [savedListLoading, setSavedListLoading] = useState(false);
    const [savedListError, setSavedListError] = useState<string | null>(null);

    const [savedDetailId, setSavedDetailId] = useState<number | null>(null);
    const [savedDetailHeader, setSavedDetailHeader] = useState<CatalogSnapshotListRow | null>(null);
    const [savedDetailRows, setSavedDetailRows] = useState<VouchagramBrandRow[] | null>(null);
    const [savedDetailMeta, setSavedDetailMeta] = useState<PaginateMeta | null>(null);
    const [savedDetailLoading, setSavedDetailLoading] = useState(false);
    const [savedDetailError, setSavedDetailError] = useState<string | null>(null);

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

    useEffect(() => {
        if (active !== 'sync') {
            setSyncTabBrandRows(null);
            setSyncTabActivityLog([]);
        }
    }, [active]);

    useEffect(() => {
        if (active !== 'saved') {
            return;
        }

        let cancelled = false;

        const load = async () => {
            setSavedListLoading(true);
            setSavedListError(null);
            try {
                const params = new URLSearchParams({ page: '1', per_page: '20' });
                if (savedListFilterMode !== 'all') {
                    params.set('mode', savedListFilterMode);
                }
                const data = await getJson(`${CATALOG_SNAPSHOTS_URL}?${params.toString()}`);
                if (cancelled) {
                    return;
                }
                if (data.success !== true) {
                    setSavedList(null);
                    setSavedListMeta(null);
                    setSavedListError(String(data.message ?? 'Could not load saved fetches.'));
                    return;
                }
                const rows = Array.isArray(data.data) ? (data.data as CatalogSnapshotListRow[]) : [];
                setSavedList(rows);
                const m = data.meta as Record<string, unknown> | undefined;
                if (m && typeof m.current_page === 'number') {
                    setSavedListMeta({
                        current_page: Number(m.current_page),
                        last_page: Number(m.last_page),
                        per_page: Number(m.per_page),
                        total: Number(m.total),
                    });
                } else {
                    setSavedListMeta(null);
                }
            } catch (e: unknown) {
                if (!cancelled) {
                    setSavedList(null);
                    setSavedListMeta(null);
                    setSavedListError(e instanceof Error ? e.message : 'Request failed');
                }
            } finally {
                if (!cancelled) {
                    setSavedListLoading(false);
                }
            }
        };

        void load();

        return () => {
            cancelled = true;
        };
    }, [active, savedListFilterMode]);

    const runs = useMemo(() => syncRuns ?? [], [syncRuns]);
    const badgeForRunStatus = (status: string) => {
        const key = String(status || '').toLowerCase();
        if (key === 'succeeded') return <Badge tone="success">Succeeded</Badge>;
        if (key === 'running') return <Badge tone="brand">Running</Badge>;
        if (key === 'queued') return <Badge tone="warning">Queued</Badge>;
        if (key === 'failed') return <Badge tone="danger">Failed</Badge>;
        return <Badge tone="neutral">{status || 'Unknown'}</Badge>;
    };

    const loadSavedListPage = async (page: number) => {
        if (active !== 'saved') {
            return;
        }
        setSavedListLoading(true);
        setSavedListError(null);
        try {
            const params = new URLSearchParams({ page: String(page), per_page: '20' });
            if (savedListFilterMode !== 'all') {
                params.set('mode', savedListFilterMode);
            }
            const data = await getJson(`${CATALOG_SNAPSHOTS_URL}?${params.toString()}`);
            if (data.success !== true) {
                setSavedList(null);
                setSavedListMeta(null);
                setSavedListError(String(data.message ?? 'Could not load saved fetches.'));
                return;
            }
            const rows = Array.isArray(data.data) ? (data.data as CatalogSnapshotListRow[]) : [];
            setSavedList(rows);
            const m = data.meta as Record<string, unknown> | undefined;
            if (m && typeof m.current_page === 'number') {
                setSavedListMeta({
                    current_page: Number(m.current_page),
                    last_page: Number(m.last_page),
                    per_page: Number(m.per_page),
                    total: Number(m.total),
                });
            } else {
                setSavedListMeta(null);
            }
        } catch (e: unknown) {
            setSavedList(null);
            setSavedListMeta(null);
            setSavedListError(e instanceof Error ? e.message : 'Request failed');
        } finally {
            setSavedListLoading(false);
        }
    };

    const loadSavedDetail = async (id: number, page = 1, listRow?: CatalogSnapshotListRow) => {
        setSavedDetailId(id);
        if (listRow) {
            setSavedDetailHeader(listRow);
        }
        setSavedDetailLoading(true);
        setSavedDetailError(null);
        try {
            const params = new URLSearchParams({ page: String(page), per_page: '100' });
            const data = await getJson(`${CATALOG_SNAPSHOTS_URL}/${id}?${params.toString()}`);
            if (data.success !== true) {
                setSavedDetailHeader(null);
                setSavedDetailRows(null);
                setSavedDetailMeta(null);
                setSavedDetailError(String(data.message ?? 'Could not load snapshot.'));
                return;
            }
            const snap = data.snapshot as Record<string, unknown> | undefined;
            if (snap && snap.id != null) {
                setSavedDetailHeader({
                    id: Number(snap.id),
                    mode: String(snap.mode ?? ''),
                    brand_product_code_filter: (snap.brand_product_code_filter as string | null) ?? null,
                    item_count: Number(snap.item_count ?? 0),
                    fetched_at: String(snap.fetched_at ?? ''),
                });
            } else {
                setSavedDetailHeader(null);
            }
            setSavedDetailRows(Array.isArray(data.data) ? (data.data as VouchagramBrandRow[]) : []);
            const m = data.meta as Record<string, unknown> | undefined;
            if (m && typeof m.current_page === 'number') {
                setSavedDetailMeta({
                    current_page: Number(m.current_page),
                    last_page: Number(m.last_page),
                    per_page: Number(m.per_page),
                    total: Number(m.total),
                });
            } else {
                setSavedDetailMeta(null);
            }
        } catch (e: unknown) {
            setSavedDetailHeader(null);
            setSavedDetailRows(null);
            setSavedDetailMeta(null);
            setSavedDetailError(e instanceof Error ? e.message : 'Request failed');
        } finally {
            setSavedDetailLoading(false);
        }
    };

    const runSyncCatalog = async (mode: 'send' | 'pull', preserveBrandTable: boolean) => {
        setError(null);
        setSyncStats(null);
        setLastSyncContext(null);
        setLastSyncMode(null);
        if (preserveBrandTable) {
            if (mode === 'send') {
                setBrandsSendBusy(true);
            } else {
                setBrandsPullBusy(true);
            }
        } else {
            if (mode === 'send') {
                setCatalogSendBusy(true);
            } else {
                setCatalogPullBusy(true);
            }
            setSyncTabBrandRows(null);
            setSyncTabActivityLog([]);
        }
        const log: string[] = [];
        try {
            const data = await postJson(SYNC_CATALOG_URL, { mode });
            if (data.success === false && data.message) {
                setError(String(data.message));
                if (!preserveBrandTable) {
                    setSyncTabActivityLog([`Import failed: ${String(data.message)}`]);
                }
            }
            if (data.success === true && data.stats && typeof data.stats === 'object' && !Array.isArray(data.stats)) {
                const s = data.stats as Record<string, unknown>;
                const created = Number(s.created ?? 0);
                const updated = Number(s.updated ?? 0);
                const deactivated = Number(s.deactivated ?? 0);
                setSyncStats({ created, updated, deactivated });
                setLastSyncContext(preserveBrandTable ? 'brands' : 'sync');
                if (data.mode === 'send' || data.mode === 'pull') {
                    setLastSyncMode(data.mode);
                }
                log.push(
                    `Imported from partner: ${created} new, ${updated} updated, ${deactivated} marked no longer available.`
                );

                if (!preserveBrandTable) {
                    const br = await postJson(FETCH_BRANDS_URL, { mode, brand_code: null });
                    if (br.success === true && Array.isArray(br.data)) {
                        const rows = br.data as VouchagramBrandRow[];
                        setSyncTabBrandRows(rows);
                        log.push(
                            rows.length > 0
                                ? `Loaded ${rows.length} products below so you can review what is available from the partner.`
                                : 'Import finished; the partner returned no products in the live list (table below is empty).'
                        );
                        if (typeof br.snapshot_id === 'number') {
                            log.push(`Catalog snapshot saved as #${br.snapshot_id}.`);
                        }
                    } else {
                        setSyncTabBrandRows(null);
                        log.push('Import succeeded, but the product list preview could not be loaded. Try the Brands tab to fetch the list.');
                    }
                    setSyncTabActivityLog(log);
                }
            }
        } catch (e: unknown) {
            const msg = e instanceof Error ? e.message : 'Request failed';
            setError(msg);
            if (!preserveBrandTable) {
                setSyncTabActivityLog([`Something went wrong: ${msg}`]);
            }
        } finally {
            if (preserveBrandTable) {
                if (mode === 'send') {
                    setBrandsSendBusy(false);
                } else {
                    setBrandsPullBusy(false);
                }
            } else {
                if (mode === 'send') {
                    setCatalogSendBusy(false);
                } else {
                    setCatalogPullBusy(false);
                }
            }
        }
    };

    const runSyncFromSnapshot = async (snapshotId: number) => {
        setError(null);
        setSyncStats(null);
        setLastSyncContext('brands');
        setLastSyncMode(null);
        setSnapshotImportBusy(true);
        try {
            const data = await postJson(SYNC_FROM_SNAPSHOT_URL, { snapshot_id: snapshotId });
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
                if (data.mode === 'send' || data.mode === 'pull') {
                    setLastSyncMode(data.mode as 'send' | 'pull');
                }
            }
        } catch (e: unknown) {
            setError(e instanceof Error ? e.message : 'Request failed');
        } finally {
            setSnapshotImportBusy(false);
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
    const normalizedResponseData = (() => {
        if (!result) return null;
        const data = result.data as JsonValue;
        if (!data) return null;
        if (typeof data !== 'object' || Array.isArray(data)) return { value: data } as JsonObject;
        return data as JsonObject;
    })();

    return (
        <AdminLayout>
            <Head title="Vouchagram Sync Center" />
            <div className="space-y-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-product-primary dark:bg-blue-900/25 dark:text-blue-100">
                            <Gift className="h-5 w-5" />
                        </div>
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Vouchagram / Gyftr</p>
                            <h1 className="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">Partner Sync Center</h1>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Send (B2C) + Pull (B2B) tools, catalog snapshots, and product imports.
                            </p>
                        </div>
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
                    <StatCard label="Products imported" value={kpis.productsImported} icon={Database} color="accent" />
                    <StatCard label="Pending queue" value={kpis.pendingQueue} icon={Activity} color="warning" />
                    <StatCard label="Failed jobs" value={kpis.failedJobs} icon={RefreshCw} color="danger" />
                    <StatCard
                        label="Credentials"
                        value={`${sendConfigured ? 'Send' : '—'} / ${pullConfigured ? 'Pull' : '—'}`}
                        icon={Search}
                        color="brand"
                    />
                </div>

                <div className="flex flex-wrap gap-2 border-b border-gray-200 pb-2 dark:border-gray-800">
                    {tabs.map((t) => {
                        const Icon = t.icon;
                        return (
                            <button
                                key={t.id}
                                type="button"
                                onClick={() => setActive(t.id)}
                                className={[
                                    'inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition',
                                    active === t.id
                                        ? 'bg-product-primary text-white'
                                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200',
                                ].join(' ')}
                            >
                                <Icon className="h-4 w-4" />
                                {t.label}
                            </button>
                        );
                    })}
                </div>

                {error && (
                    <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-900/20 dark:text-red-200">
                        {error}
                    </div>
                )}

                {active === 'dashboard' && (
                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Send (B2C)</p>
                                    <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">Voucher send tools</p>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Used for public-store vouchers and customer deliveries.
                                    </p>
                                </div>
                                {sendConfigured ? <Badge tone="success">Configured</Badge> : <Badge tone="warning">Needs setup</Badge>}
                            </CardHeader>
                            <CardBody>
                                {!sendConfigured ? (
                                    <p className="text-sm text-amber-900">Missing env keys: set `VOUCHAGRAM_SEND_*` in `.env`.</p>
                                ) : (
                                    <p className="text-sm text-gray-700 dark:text-gray-200">Ready to use.</p>
                                )}
                            </CardBody>
                        </Card>
                        <Card>
                            <CardHeader className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Pull (B2B)</p>
                                    <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">Voucher pull tools</p>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Used for business/admin catalogs and B2B workflows.
                                    </p>
                                </div>
                                {pullConfigured ? <Badge tone="success">Configured</Badge> : <Badge tone="warning">Needs setup</Badge>}
                            </CardHeader>
                            <CardBody>
                                {!pullConfigured ? (
                                    <p className="text-sm text-amber-900">Missing env keys: set `VOUCHAGRAM_PULL_*` in `.env`.</p>
                                ) : (
                                    <p className="text-sm text-gray-700 dark:text-gray-200">Ready to use.</p>
                                )}
                            </CardBody>
                        </Card>

                        <Card className="lg:col-span-2">
                            <CardHeader className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Recent sync runs</p>
                                    <p className="mt-2 text-lg font-semibold text-gray-900 dark:text-white">Run history</p>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">
                                        Catalog imports are recorded here for audit and debugging.
                                    </p>
                                </div>
                                <Badge tone="neutral">{runs.length} recent</Badge>
                            </CardHeader>
                            <CardBody>
                                {runs.length === 0 ? (
                                    <p className="text-sm text-gray-600">No runs yet.</p>
                                ) : (
                                    <div className="overflow-auto rounded-2xl border border-gray-200 dark:border-gray-800">
                                        <table className="w-full text-left text-sm">
                                            <thead className="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-950">
                                                <tr>
                                                    <th className="px-4 py-3">Run</th>
                                                    <th className="px-4 py-3">Type</th>
                                                    <th className="px-4 py-3">Status</th>
                                                    <th className="px-4 py-3">Counts</th>
                                                    <th className="px-4 py-3">Completed</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-gray-800">
                                                {runs.map((r) => (
                                                    <tr key={r.id} className="bg-white dark:bg-gray-900/50">
                                                        <td className="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100">#{r.id}</td>
                                                        <td className="px-4 py-3 text-xs text-gray-600">{r.job_type}</td>
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
                                    </div>
                                )}
                            </CardBody>
                        </Card>
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
                        {result?.success === true && typeof result.snapshot_id === 'number' && (
                            <div className="space-y-2">
                                <p className="rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                                    Saved as catalog <strong>#{result.snapshot_id}</strong>
                                    {typeof result.snapshot_fetched_at === 'string' ? (
                                        <span className="ml-2 text-xs opacity-80">({result.snapshot_fetched_at})</span>
                                    ) : null}
                                    . This stores rows for history; it does not create <code className="text-xs">products</code> until you
                                    import below.
                                </p>
                                {canSyncCatalog ? (
                                    <button
                                        type="button"
                                        disabled={snapshotImportBusy || brandsSendBusy || brandsPullBusy}
                                        onClick={() => void runSyncFromSnapshot(Number(result.snapshot_id))}
                                        className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                    >
                                        {snapshotImportBusy
                                            ? 'Importing from snapshot…'
                                            : `Import snapshot #${String(result.snapshot_id)} into Products (no live API)`}
                                    </button>
                                ) : (
                                    <p className="text-xs text-amber-700 dark:text-amber-300">
                                        Importing into Products requires the <strong>providers.sync</strong> permission.
                                    </p>
                                )}
                            </div>
                        )}
                        {active === 'brands' && lastSyncContext === 'brands' && syncStats && (
                            <SyncStatsCard stats={syncStats} mode={lastSyncMode} />
                        )}
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
                                        disabled={loading || brandsSendBusy || brandsPullBusy || snapshotImportBusy || !sendConfigured}
                                        onClick={() => runSyncCatalog('send', true)}
                                        title={
                                            brandsPullBusy
                                                ? 'Wait until the Pull import finishes.'
                                                : 'Partner Send API — saves products for your public storefront.'
                                        }
                                        className="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700 disabled:opacity-50"
                                    >
                                        {brandsSendBusy
                                            ? 'Importing…'
                                            : `Re-fetch from partner (Send API) — ${brandRows!.length} listed`}
                                    </button>
                                    <button
                                        type="button"
                                        disabled={loading || brandsSendBusy || brandsPullBusy || snapshotImportBusy || !pullConfigured}
                                        onClick={() => runSyncCatalog('pull', true)}
                                        title={
                                            brandsSendBusy
                                                ? 'Wait until the Send import finishes.'
                                                : 'Partner Pull API — saves products for B2B / admin use.'
                                        }
                                        className="rounded-lg bg-teal-700 px-3 py-1.5 text-xs font-medium text-white hover:bg-teal-800 disabled:opacity-50"
                                    >
                                        {brandsPullBusy
                                            ? 'Importing…'
                                            : `Re-fetch from partner (Pull API) — ${brandRows!.length} listed`}
                                    </button>
                                </div>
                                <BrandDataTable rows={brandRows!} copiedCode={copiedCode} onCopy={copyCode} />
                            </div>
                        )}
                    </div>
                )}

                {active === 'saved' && (
                    <div className="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Saved catalog fetches</h3>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Each successful &quot;Fetch brands&quot; is stored with an internal id. Open a row to view the same
                                sanitized columns as on the Brands tab.
                            </p>
                        </div>
                        <div className="flex flex-wrap items-center gap-4">
                            <label className="flex items-center gap-2 text-sm">
                                Mode
                                <select
                                    value={savedListFilterMode}
                                    onChange={(e) => setSavedListFilterMode(e.target.value as 'all' | 'send' | 'pull')}
                                    className="rounded border border-gray-300 px-2 py-1 dark:border-gray-600 dark:bg-gray-900"
                                >
                                    <option value="all">All</option>
                                    <option value="send">Send</option>
                                    <option value="pull">Pull</option>
                                </select>
                            </label>
                            {savedListLoading && <span className="text-xs text-gray-500">Loading…</span>}
                        </div>
                        {savedListError && (
                            <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                                {savedListError}
                            </p>
                        )}
                        <div className="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-600">
                            <table className="w-full min-w-[640px] text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <tr>
                                        <th className="px-3 py-2">ID</th>
                                        <th className="px-3 py-2">Mode</th>
                                        <th className="px-3 py-2">Fetched</th>
                                        <th className="px-3 py-2">Filter</th>
                                        <th className="px-3 py-2">Items</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                                    {!savedList || savedList.length === 0 ? (
                                        <tr>
                                            <td colSpan={6} className="px-3 py-8 text-center text-gray-500">
                                                {savedListLoading ? 'Loading…' : 'No saved fetches yet. Use Fetch brands on the Brands tab.'}
                                            </td>
                                        </tr>
                                    ) : (
                                        savedList.map((row) => (
                                            <tr
                                                key={row.id}
                                                className={`bg-white dark:bg-gray-900/50 ${savedDetailId === row.id ? 'ring-1 ring-inset ring-indigo-300 dark:ring-indigo-700' : ''}`}
                                            >
                                                <td className="px-3 py-2 font-mono text-xs">{row.id}</td>
                                                <td className="px-3 py-2">
                                                    <span className="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-800">{row.mode}</span>
                                                </td>
                                                <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">{row.fetched_at}</td>
                                                <td className="max-w-[200px] truncate px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                                    {row.brand_product_code_filter ?? '— (all)'}
                                                </td>
                                                <td className="px-3 py-2">{row.item_count}</td>
                                                <td className="px-3 py-2 text-right">
                                                    <button
                                                        type="button"
                                                        disabled={savedDetailLoading}
                                                        onClick={() => void loadSavedDetail(row.id, 1, row)}
                                                        className="rounded-lg bg-indigo-600 px-3 py-1 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                                    >
                                                        View rows
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {savedListMeta && savedListMeta.last_page > 1 && (
                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                <button
                                    type="button"
                                    disabled={savedListLoading || savedListMeta.current_page <= 1}
                                    onClick={() => void loadSavedListPage(savedListMeta.current_page - 1)}
                                    className="rounded border border-gray-300 px-3 py-1 dark:border-gray-600 disabled:opacity-50"
                                >
                                    Previous
                                </button>
                                <span className="text-gray-600 dark:text-gray-400">
                                    Page {savedListMeta.current_page} of {savedListMeta.last_page} ({savedListMeta.total} total)
                                </span>
                                <button
                                    type="button"
                                    disabled={savedListLoading || savedListMeta.current_page >= savedListMeta.last_page}
                                    onClick={() => void loadSavedListPage(savedListMeta.current_page + 1)}
                                    className="rounded border border-gray-300 px-3 py-1 dark:border-gray-600 disabled:opacity-50"
                                >
                                    Next
                                </button>
                            </div>
                        )}

                        {savedDetailError && (
                            <p className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                                {savedDetailError}
                            </p>
                        )}

                        {savedDetailHeader && (
                            <div className="space-y-3 border-t border-gray-200 pt-6 dark:border-gray-600">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <h4 className="text-sm font-semibold text-gray-900 dark:text-white">
                                        Snapshot #{savedDetailHeader.id}{' '}
                                        <span className="font-normal text-gray-500 dark:text-gray-400">
                                            ({savedDetailHeader.mode}, {savedDetailHeader.item_count} items)
                                        </span>
                                    </h4>
                                    {canSyncCatalog && savedDetailHeader.item_count > 0 && (
                                        <button
                                            type="button"
                                            disabled={snapshotImportBusy || brandsSendBusy || brandsPullBusy}
                                            onClick={() => void runSyncFromSnapshot(savedDetailHeader.id)}
                                            className="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        >
                                            {snapshotImportBusy ? 'Importing…' : 'Import this snapshot into Products'}
                                        </button>
                                    )}
                                </div>
                                {savedDetailLoading && <p className="text-xs text-gray-500">Loading rows…</p>}
                                {savedDetailRows && (
                                    <>
                                        <BrandDataTable rows={savedDetailRows} copiedCode={copiedCode} onCopy={copyCode} />
                                        {savedDetailMeta && savedDetailMeta.last_page > 1 && (
                                            <div className="flex flex-wrap items-center gap-2 text-sm">
                                                <button
                                                    type="button"
                                                    disabled={savedDetailLoading || savedDetailMeta.current_page <= 1}
                                                    onClick={() =>
                                                        savedDetailId != null && void loadSavedDetail(savedDetailId, savedDetailMeta.current_page - 1)
                                                    }
                                                    className="rounded border border-gray-300 px-3 py-1 dark:border-gray-600 disabled:opacity-50"
                                                >
                                                    Previous page
                                                </button>
                                                <span className="text-gray-600 dark:text-gray-400">
                                                    Page {savedDetailMeta.current_page} of {savedDetailMeta.last_page} (
                                                    {savedDetailMeta.total} rows)
                                                </span>
                                                <button
                                                    type="button"
                                                    disabled={savedDetailLoading || savedDetailMeta.current_page >= savedDetailMeta.last_page}
                                                    onClick={() =>
                                                        savedDetailId != null && void loadSavedDetail(savedDetailId, savedDetailMeta.current_page + 1)
                                                    }
                                                    className="rounded border border-gray-300 px-3 py-1 dark:border-gray-600 disabled:opacity-50"
                                                >
                                                    Next page
                                                </button>
                                            </div>
                                        )}
                                    </>
                                )}
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
                    <div className="space-y-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Update product list from partner</h3>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                                Two separate imports: the partner <strong>Send API</strong> fills your <strong>public store</strong> catalog (what
                                shoppers see). The <strong>Pull API</strong> fills your <strong>B2B / admin</strong> catalog (business use; hidden from
                                the public site by default). After each import we load the table below so you can review brands, codes, pricing, and
                                stock—like the Brands tab.
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-3">
                            <button
                                type="button"
                                disabled={catalogSendBusy || catalogPullBusy || !sendConfigured}
                                onClick={() => runSyncCatalog('send', false)}
                                title={
                                    catalogPullBusy
                                        ? 'Wait until the Pull import finishes.'
                                        : 'Partner Send API — full catalog import for the public storefront.'
                                }
                                className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            >
                                {catalogSendBusy
                                    ? 'Importing…'
                                    : 'Import for public store (Send API)'}
                            </button>
                            <button
                                type="button"
                                disabled={catalogSendBusy || catalogPullBusy || !pullConfigured}
                                onClick={() => runSyncCatalog('pull', false)}
                                title={
                                    catalogSendBusy
                                        ? 'Wait until the Send import finishes.'
                                        : 'Partner Pull API — full catalog import for B2B / admin.'
                                }
                                className="rounded-lg bg-teal-700 px-4 py-2 text-sm font-medium text-white hover:bg-teal-800 disabled:opacity-50"
                            >
                                {catalogPullBusy
                                    ? 'Importing…'
                                    : 'Import for B2B (Pull API)'}
                            </button>
                        </div>

                        {syncTabActivityLog.length > 0 && (
                            <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-600 dark:bg-gray-900/50">
                                <h4 className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">What happened</h4>
                                <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-gray-800 dark:text-gray-200">
                                    {syncTabActivityLog.map((line, i) => (
                                        <li key={i}>{line}</li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {lastSyncContext === 'sync' && syncStats && <SyncStatsCard stats={syncStats} mode={lastSyncMode} />}

                        {syncTabBrandRows !== null && (
                            <div className="space-y-2">
                                <h4 className="text-sm font-semibold text-gray-900 dark:text-white">Partner products (after last import)</h4>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Same view as the Brands tab—so you can confirm what the partner returned.
                                </p>
                                <BrandDataTable rows={syncTabBrandRows} copiedCode={copiedCode} onCopy={copyCode} />
                            </div>
                        )}
                    </div>
                )}

                {result !== null && !hideRawJsonForBrands && !hideRawJsonForSync && (
                    <div className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div className="mb-3 flex items-center justify-between">
                            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-200">Response</h4>
                            <span
                                className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                                    result.success === true ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'
                                }`}
                            >
                                {result.success === true ? 'Success' : 'Error'}
                            </span>
                        </div>
                        {normalizedResponseData && (
                            <div className="space-y-3">
                                <ScalarGrid data={normalizedResponseData} />
                                <ObjectSections data={normalizedResponseData} />
                                <ArraySections data={normalizedResponseData} />
                            </div>
                        )}
                        <details className="mt-3">
                            <summary className="cursor-pointer text-xs font-medium text-gray-600 dark:text-gray-300">View raw JSON</summary>
                            <pre className="mt-2 max-h-[480px] overflow-auto rounded border border-gray-200 bg-white p-3 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                                {JSON.stringify(result, null, 2)}
                            </pre>
                        </details>
                    </div>
                )}

                {loading && active !== 'sync' && <p className="text-sm text-gray-500">Loading…</p>}
            </div>
        </AdminLayout>
    );
}
