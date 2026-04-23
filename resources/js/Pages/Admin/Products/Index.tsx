import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    ActionButtons,
    AdminListFilters,
    Breadcrumbs,
    BulkActionBar,
    BulkEditProductsModal,
    CatalogAudienceBadge,
    InertiaQueryPagination,
} from '@/Components/Admin';
import { useBulkRowSelection } from '@/hooks/useBulkRowSelection';
import { Package, Plus, Sparkles } from 'lucide-react';

type Row = {
    id: number;
    sku: string | null;
    product_name: string;
    source_provider: string | null;
    catalog_audience: string | null;
    show_product: boolean;
    selling_price: string | number | null;
    price_display: string | null;
    thumbnail: string | null;
    has_custom_content: boolean;
    created_at: string | null;
};

type Paginated = {
    data: Row[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

type FilterState = {
    search?: string;
    source_provider?: string;
    status?: string;
    catalog_scope?: string;
    catalog_audience?: string | null;
    per_page?: number;
};

type Props = {
    products: Paginated;
    filters: FilterState;
    source_provider_options: string[];
    canViewAllCatalog: boolean;
    canViewStorefrontCatalog: boolean;
    canViewBusinessCatalog: boolean;
    canPublish: boolean;
    canUpdate: boolean;
};

function mergeFilterPatch(
    base: FilterState,
    patch: Record<string, string | undefined>,
): FilterState {
    const next: FilterState = { ...base };
    for (const [key, raw] of Object.entries(patch)) {
        if (raw === undefined) {
            delete (next as Record<string, unknown>)[key];
            continue;
        }
        if (key === 'per_page') {
            const n = parseInt(raw, 10);
            next.per_page = [10, 20, 50, 100].includes(n) ? n : 20;
        } else {
            (next as Record<string, unknown>)[key] = raw;
        }
    }
    return next;
}

function buildProductsListUrl(filters: FilterState, page?: number): string {
    const p = new URLSearchParams();
    if (filters.catalog_scope) p.set('catalog_scope', filters.catalog_scope);
    if (filters.search) p.set('search', filters.search);
    if (filters.source_provider) p.set('source_provider', filters.source_provider);
    if (filters.status) p.set('status', filters.status);
    if (filters.catalog_audience) p.set('catalog_audience', filters.catalog_audience);
    const pp = filters.per_page != null ? Number(filters.per_page) : 20;
    if (!Number.isNaN(pp) && pp !== 20) p.set('per_page', String(pp));
    if (page && page > 1) p.set('page', String(page));
    const qs = p.toString();
    return qs ? `/panel/products?${qs}` : '/panel/products';
}

export default function Index({
    products,
    filters,
    source_provider_options,
    canViewAllCatalog,
    canViewStorefrontCatalog,
    canViewBusinessCatalog,
    canPublish,
    canUpdate,
}: Props) {
    const scope = filters.catalog_scope ?? 'storefront';
    const title =
        scope === 'business'
            ? 'B2B catalog'
            : scope === 'all'
              ? 'All products'
              : 'Storefront catalog';

    const tabs: { id: string; label: string; show: boolean }[] = [
        { id: 'storefront', label: 'Storefront', show: canViewStorefrontCatalog },
        { id: 'business', label: 'B2B', show: canViewBusinessCatalog },
        { id: 'all', label: 'All', show: canViewAllCatalog },
    ].filter((t) => t.show);

    const createHref = `/panel/products/create?catalog_scope=${encodeURIComponent(scope)}`;

    const buildUrlWithPatch = (patch: Record<string, string | undefined>, page?: number) =>
        buildProductsListUrl(mergeFilterPatch(filters, patch), page);

    const pageRowIds = useMemo(() => products.data.map(r => r.id), [products.data]);
    const {
        selectedIds,
        selectedCount,
        toggleOne,
        toggleAllOnPage,
        clear,
        isSelected,
        allOnPageSelected,
        someOnPageSelected,
    } = useBulkRowSelection(pageRowIds);

    const selectAllRef = useRef<HTMLInputElement>(null);
    useEffect(() => {
        const el = selectAllRef.current;
        if (el) {
            el.indeterminate = someOnPageSelected;
        }
    }, [someOnPageSelected, allOnPageSelected]);

    const canBulkSelect = canPublish || canUpdate;
    const tableColSpan = 10 + (canBulkSelect ? 1 : 0);
    const [bulkModalOpen, setBulkModalOpen] = useState(false);

    const bulkVisibility = (show_product: boolean) => {
        if (selectedIds.length === 0) {
            return;
        }
        router.patch(
            '/panel/products/bulk-update',
            {
                ids: selectedIds,
                catalog_scope: scope,
                apply_visibility: true,
                show_product,
            },
            {
                preserveScroll: true,
                onSuccess: () => clear(),
            },
        );
    };

    return (
        <AdminLayout>
            <Head title={title} />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: title }]} />

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex flex-col gap-1">
                        <div className="flex items-center gap-2">
                            <Package className="text-indigo-600" size={28} />
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{title}</h1>
                        </div>
                        {tabs.length === 1 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400 pl-9 sm:pl-0">
                                Catalog: {title}
                            </p>
                        ) : null}
                    </div>
                    <Link
                        href={createHref}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium shrink-0"
                    >
                        <Plus size={18} />
                        Add product
                    </Link>
                </div>

                {tabs.length > 1 && (
                    <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-1">
                        {tabs.map((t) => {
                            const active = scope === t.id;
                            const href = buildProductsListUrl({ ...filters, catalog_scope: t.id });
                            return (
                                <Link
                                    key={t.id}
                                    href={href}
                                    preserveState
                                    className={`px-3 py-2 text-sm font-medium rounded-t-lg transition-colors ${
                                        active
                                            ? 'bg-white dark:bg-gray-800 text-indigo-600 border border-b-0 border-gray-200 dark:border-gray-700'
                                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'
                                    }`}
                                >
                                    {t.label}
                                </Link>
                            );
                        })}
                    </div>
                )}

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <AdminListFilters
                        filters={filters as Record<string, string | number | null | undefined>}
                        buildUrl={buildUrlWithPatch}
                        sourceProviderOptions={source_provider_options}
                    />
                    {canBulkSelect ? (
                        <BulkActionBar selectedCount={selectedCount} onClear={clear}>
                            {canPublish ? (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => bulkVisibility(true)}
                                        className="px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 text-xs font-medium hover:bg-indigo-100/80 dark:hover:bg-indigo-900/40"
                                    >
                                        Make visible
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => bulkVisibility(false)}
                                        className="px-3 py-1.5 rounded-lg bg-white dark:bg-gray-800 border border-indigo-200 dark:border-indigo-800 text-indigo-800 dark:text-indigo-200 text-xs font-medium hover:bg-indigo-100/80 dark:hover:bg-indigo-900/40"
                                    >
                                        Make hidden
                                    </button>
                                </>
                            ) : null}
                            {canPublish || canUpdate ? (
                                <button
                                    type="button"
                                    onClick={() => setBulkModalOpen(true)}
                                    className="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-medium hover:bg-indigo-700"
                                >
                                    Bulk edit…
                                </button>
                            ) : null}
                        </BulkActionBar>
                    ) : null}
                    <BulkEditProductsModal
                        open={bulkModalOpen}
                        onClose={() => setBulkModalOpen(false)}
                        selectedIds={selectedIds}
                        catalogScope={scope}
                        canPublish={canPublish}
                        canUpdate={canUpdate}
                        onSuccess={() => clear()}
                    />
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                                    {canBulkSelect ? (
                                        <th className="px-3 py-3 w-10 text-center">
                                            <span className="sr-only">Select row</span>
                                            <input
                                                ref={selectAllRef}
                                                type="checkbox"
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                checked={allOnPageSelected}
                                                onChange={toggleAllOnPage}
                                                disabled={products.data.length === 0}
                                                aria-label="Select all on this page"
                                            />
                                        </th>
                                    ) : null}
                                    <th className="px-3 py-3 font-medium w-12 text-center">#</th>
                                    <th className="px-5 py-3 font-medium w-20">Image</th>
                                    <th className="px-5 py-3 font-medium">Name</th>
                                    <th className="px-5 py-3 font-medium">SKU</th>
                                    <th className="px-5 py-3 font-medium">Provider</th>
                                    <th className="px-5 py-3 font-medium">Audience</th>
                                    <th className="px-5 py-3 font-medium">Price</th>
                                    <th className="px-5 py-3 font-medium">Visible</th>
                                    <th className="px-5 py-3 font-medium">Content</th>
                                    <th className="px-5 py-3 font-medium w-28 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={tableColSpan} className="px-5 py-12 text-center text-gray-500">
                                            No products match your filters. Try clearing search or sync the catalog
                                            from a provider.
                                        </td>
                                    </tr>
                                ) : (
                                    products.data.map((row, idx) => {
                                        const rowNum = (products.from ?? 0) + idx;
                                        return (
                                            <tr
                                                key={row.id}
                                                className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                            >
                                                {canBulkSelect ? (
                                                    <td className="px-3 py-3 text-center">
                                                        <input
                                                            type="checkbox"
                                                            className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                            checked={isSelected(row.id)}
                                                            onChange={() => toggleOne(row.id)}
                                                            aria-label={`Select ${row.product_name || row.sku || row.id}`}
                                                        />
                                                    </td>
                                                ) : null}
                                                <td className="px-3 py-3 text-center tabular-nums text-gray-500 dark:text-gray-400">
                                                    {rowNum}
                                                </td>
                                                <td className="px-5 py-2">
                                                    {row.thumbnail ? (
                                                        <img
                                                            src={row.thumbnail}
                                                            alt=""
                                                            className="w-14 h-14 object-cover rounded-lg border border-gray-200 dark:border-gray-600"
                                                        />
                                                    ) : (
                                                        <div className="w-14 h-14 rounded-lg bg-gray-100 dark:bg-gray-700" />
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                                    {row.product_name || '—'}
                                                </td>
                                                <td className="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                                    {row.sku || '—'}
                                                </td>
                                                <td className="px-5 py-3 capitalize">{row.source_provider || '—'}</td>
                                                <td className="px-5 py-3">
                                                    <CatalogAudienceBadge audience={row.catalog_audience} />
                                                </td>
                                                <td className="px-5 py-3 max-w-xs text-gray-700 dark:text-gray-300">
                                                    {row.price_display && row.price_display !== ''
                                                        ? row.price_display
                                                        : '—'}
                                                </td>
                                                <td className="px-5 py-3">
                                                    <button
                                                        type="button"
                                                        role="switch"
                                                        aria-checked={row.show_product}
                                                        title={
                                                            row.show_product
                                                                ? 'Visible on storefront — click to hide'
                                                                : 'Hidden — click to show'
                                                        }
                                                        onClick={() =>
                                                            router.patch(`/panel/products/${row.id}/toggle`, {}, {
                                                                preserveScroll: true,
                                                            })
                                                        }
                                                        className={`relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 ${
                                                            row.show_product
                                                                ? 'bg-indigo-600'
                                                                : 'bg-gray-200 dark:bg-gray-600'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform ${
                                                                row.show_product ? 'translate-x-5' : 'translate-x-0'
                                                            }`}
                                                        />
                                                    </button>
                                                </td>
                                                <td className="px-5 py-3">
                                                    {row.has_custom_content ? (
                                                        <span className="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400">
                                                            <Sparkles size={12} />
                                                            Custom
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-gray-400">Default</span>
                                                    )}
                                                </td>
                                                <td className="px-5 py-3 text-right">
                                                    <ActionButtons editHref={`/panel/products/${row.id}/edit`} />
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                    <InertiaQueryPagination
                        meta={products}
                        buildUrl={page => buildProductsListUrl(filters, page)}
                    />
                </div>
            </div>
        </AdminLayout>
    );
}
