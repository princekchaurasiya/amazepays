import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { Package, Plus, Sparkles } from 'lucide-react';

type Row = {
    id: number;
    sku: string | null;
    product_name: string;
    source_provider: string | null;
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

type Props = {
    products: Paginated;
    filters: { search?: string; source_provider?: string; status?: string };
};

export default function Index({ products, filters }: Props) {
    return (
        <AdminLayout>
            <Head title="Products" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Products' }]} />

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Package className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Products</h1>
                    </div>
                    <Link
                        href="/panel/products/create"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium"
                    >
                        <Plus size={18} />
                        Add product
                    </Link>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                                    <th className="px-5 py-3 font-medium w-20">Image</th>
                                    <th className="px-5 py-3 font-medium">Name</th>
                                    <th className="px-5 py-3 font-medium">SKU</th>
                                    <th className="px-5 py-3 font-medium">Provider</th>
                                    <th className="px-5 py-3 font-medium">Price</th>
                                    <th className="px-5 py-3 font-medium">Visible</th>
                                    <th className="px-5 py-3 font-medium">Content</th>
                                    <th className="px-5 py-3 font-medium w-28 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-5 py-12 text-center text-gray-500">
                                            No products match your filters. Try clearing search or sync the catalog from a provider.
                                        </td>
                                    </tr>
                                ) : (
                                    products.data.map(row => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                        >
                                            <td className="px-5 py-2">
                                                {row.thumbnail ? (
                                                    <img src={row.thumbnail} alt="" className="w-14 h-14 object-cover rounded-lg border border-gray-200 dark:border-gray-600" />
                                                ) : (
                                                    <div className="w-14 h-14 rounded-lg bg-gray-100 dark:bg-gray-700" />
                                                )}
                                            </td>
                                            <td className="px-5 py-3 font-medium text-gray-900 dark:text-white">{row.product_name || '—'}</td>
                                            <td className="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">{row.sku || '—'}</td>
                                            <td className="px-5 py-3 capitalize">{row.source_provider || '—'}</td>
                                            <td className="px-5 py-3 max-w-xs text-gray-700 dark:text-gray-300">
                                                {row.price_display && row.price_display !== '' ? row.price_display : '—'}
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
                                                        router.patch(`/panel/products/${row.id}/toggle`, {}, { preserveScroll: true })
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
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    {products.last_page > 1 && (
                        <div className="flex items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-500">
                            <span>
                                {products.from}–{products.to} of {products.total}
                            </span>
                            <div className="flex gap-2">
                                {products.current_page > 1 && (
                                    <Link
                                        href={`/panel/products?page=${products.current_page - 1}`}
                                        preserveState
                                        className="px-3 py-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Previous
                                    </Link>
                                )}
                                {products.current_page < products.last_page && (
                                    <Link
                                        href={`/panel/products?page=${products.current_page + 1}`}
                                        preserveState
                                        className="px-3 py-1 rounded border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        Next
                                    </Link>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}
