import React, { FormEvent, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, AdminImageDropzone, Breadcrumbs } from '@/Components/Admin';
import { FolderTree, Plus, X, Search, Package, Pencil } from 'lucide-react';

type CategoryRow = {
    id: number;
    name: string;
    slug: string;
    order: number;
    thumbnail: string | null;
    accent_color: string | null;
    products_count: number;
    product_ids: number[];
};

type ProductOption = { id: number; label: string; sku: string | null };

type Props = {
    categories: CategoryRow[];
    products: ProductOption[];
};

export default function Index({ categories, products }: Props) {
    const [categoryModalOpen, setCategoryModalOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [assignCategory, setAssignCategory] = useState<CategoryRow | null>(null);
    const [productSearch, setProductSearch] = useState('');
    const [assignSelected, setAssignSelected] = useState<number[]>([]);

    const categoryForm = useForm({
        name: '',
        order: 0 as number,
        accent_color: '' as string,
        thumbnail: null as File | null,
    });

    const openAdd = () => {
        setEditingId(null);
        categoryForm.setData({ name: '', order: 0, accent_color: '', thumbnail: null });
        categoryForm.clearErrors();
        setCategoryModalOpen(true);
    };

    const openEdit = (row: CategoryRow) => {
        setEditingId(row.id);
        categoryForm.setData({
            name: row.name,
            order: row.order,
            accent_color: row.accent_color ?? '',
            thumbnail: null,
        });
        categoryForm.clearErrors();
        setCategoryModalOpen(true);
    };

    const closeCategoryModal = () => {
        setCategoryModalOpen(false);
        setEditingId(null);
        categoryForm.reset();
        categoryForm.clearErrors();
    };

    const submitCategory = (e: FormEvent) => {
        e.preventDefault();
        if (editingId) {
            categoryForm.put(`/panel/categories/${editingId}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeCategoryModal(),
            });
        } else {
            categoryForm.post('/panel/categories', {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => closeCategoryModal(),
            });
        }
    };

    const openAssign = (row: CategoryRow) => {
        setAssignCategory(row);
        setAssignSelected([...row.product_ids]);
        setProductSearch('');
    };

    const closeAssign = () => {
        setAssignCategory(null);
        setAssignSelected([]);
        setProductSearch('');
    };

    const toggleAssignProduct = (id: number) => {
        setAssignSelected(prev => (prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]));
    };

    const saveAssign = () => {
        if (!assignCategory) return;
        router.post(
            `/panel/categories/${assignCategory.id}/products`,
            { product_ids: assignSelected },
            { preserveScroll: true, onSuccess: () => closeAssign() },
        );
    };

    const filteredProducts = useMemo(() => {
        const q = productSearch.trim().toLowerCase();
        if (!q) return products;
        return products.filter(
            p =>
                p.label.toLowerCase().includes(q) || (p.sku && String(p.sku).toLowerCase().includes(q)),
        );
    }, [products, productSearch]);

    const inputCls =
        'w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-white';

    const labelCls = 'block text-xs text-gray-500 dark:text-gray-400 mb-1';

    return (
        <AdminLayout>
            <Head title="Categories" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Categories' }]} />
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <FolderTree className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Categories</h1>
                    </div>
                    <button
                        type="button"
                        onClick={openAdd}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium"
                    >
                        <Plus size={18} />
                        Add category
                    </button>
                </div>

                <p className="text-sm text-gray-600 dark:text-gray-400">
                    Manage storefront category tiles and which products appear in each category.
                </p>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-left text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50">
                                    <th className="px-5 py-3 font-medium w-16">Image</th>
                                    <th className="px-5 py-3 font-medium">Name</th>
                                    <th className="px-5 py-3 font-medium">Slug</th>
                                    <th className="px-5 py-3 font-medium">Products</th>
                                    <th className="px-5 py-3 font-medium">Order</th>
                                    <th className="px-5 py-3 font-medium text-right w-56">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {categories.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                            No categories yet. Click &quot;Add category&quot; to create one.
                                        </td>
                                    </tr>
                                ) : (
                                    categories.map(row => (
                                        <tr
                                            key={row.id}
                                            className="border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/30"
                                        >
                                            <td className="px-5 py-2">
                                                {row.thumbnail ? (
                                                    <img
                                                        src={row.thumbnail}
                                                        alt=""
                                                        className="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600"
                                                    />
                                                ) : (
                                                    <div className="w-12 h-12 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                        <Package size={18} className="text-gray-400" />
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-5 py-3">
                                                {row.accent_color ? (
                                                    <span
                                                        title={row.accent_color}
                                                        className="inline-block h-8 w-8 rounded-full border border-gray-200 dark:border-gray-600 shadow-inner"
                                                        style={{ backgroundColor: row.accent_color }}
                                                    />
                                                ) : (
                                                    <span className="text-gray-400 text-xs">—</span>
                                                )}
                                            </td>
                                            <td className="px-5 py-3 font-medium text-gray-900 dark:text-white">{row.name}</td>
                                            <td className="px-5 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                                {row.slug}
                                            </td>
                                            <td className="px-5 py-3">{row.products_count}</td>
                                            <td className="px-5 py-3">{row.order}</td>
                                            <td className="px-5 py-3 text-right">
                                                <div className="inline-flex flex-wrap items-center justify-end gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => openAssign(row)}
                                                        className="px-2 py-1.5 text-xs font-medium rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700"
                                                    >
                                                        Manage products
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(row)}
                                                        className="inline-flex items-center justify-center rounded-lg p-2 text-gray-600 hover:bg-gray-100 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-indigo-400"
                                                        title="Edit"
                                                    >
                                                        <Pencil size={16} aria-hidden />
                                                        <span className="sr-only">Edit</span>
                                                    </button>
                                                    <ActionButtons
                                                        onDelete={() =>
                                                            router.delete(`/panel/categories/${row.id}`, { preserveScroll: true })
                                                        }
                                                        deleteConfirmTitle="Delete this category?"
                                                        deleteConfirmMessage={`Remove “${row.name}” and its product links?`}
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Add / Edit category */}
            {categoryModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <button
                        type="button"
                        className="fixed inset-0 bg-black/50 backdrop-blur-sm"
                        aria-label="Close"
                        onClick={closeCategoryModal}
                    />
                    <div className="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full p-6 border border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            onClick={closeCategoryModal}
                            className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            <X size={20} />
                        </button>
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {editingId ? 'Edit category' : 'Add category'}
                        </h2>
                        <form onSubmit={submitCategory} className="space-y-4">
                            <div>
                                <label className={labelCls}>Name</label>
                                <input
                                    type="text"
                                    value={categoryForm.data.name}
                                    onChange={e => categoryForm.setData('name', e.target.value)}
                                    className={inputCls}
                                    required
                                />
                                {categoryForm.errors.name && (
                                    <p className="text-red-500 text-xs mt-1">{categoryForm.errors.name}</p>
                                )}
                            </div>
                            <div>
                                <label className={labelCls}>Sort order</label>
                                <input
                                    type="number"
                                    step="any"
                                    value={categoryForm.data.order}
                                    onChange={e =>
                                        categoryForm.setData('order', e.target.value === '' ? 0 : Number(e.target.value))
                                    }
                                    className={inputCls}
                                />
                                {categoryForm.errors.order && (
                                    <p className="text-red-500 text-xs mt-1">{categoryForm.errors.order}</p>
                                )}
                            </div>
                            <div>
                                <label className={labelCls}>Tile accent (#RRGGBB, optional)</label>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="color"
                                        value={
                                            /^#[0-9A-Fa-f]{6}$/.test(categoryForm.data.accent_color.trim())
                                                ? categoryForm.data.accent_color.trim()
                                                : '#f97316'
                                        }
                                        onChange={e => categoryForm.setData('accent_color', e.target.value)}
                                        className="h-9 w-14 cursor-pointer rounded border border-gray-300 dark:border-gray-600 bg-white p-0.5"
                                        aria-label="Pick accent color"
                                    />
                                    <input
                                        type="text"
                                        placeholder="#f97316"
                                        value={categoryForm.data.accent_color}
                                        onChange={e => categoryForm.setData('accent_color', e.target.value)}
                                        className={`${inputCls} flex-1 font-mono text-xs`}
                                    />
                                </div>
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Used for storefront category chips. Leave blank for default styling.
                                </p>
                                {categoryForm.errors.accent_color && (
                                    <p className="text-red-500 text-xs mt-1">{categoryForm.errors.accent_color}</p>
                                )}
                            </div>
                            <div>
                                <label className={labelCls}>Thumbnail (optional)</label>
                                <AdminImageDropzone
                                    file={categoryForm.data.thumbnail}
                                    onFileChange={f => categoryForm.setData('thumbnail', f)}
                                    error={
                                        typeof categoryForm.errors.thumbnail === 'string'
                                            ? categoryForm.errors.thumbnail
                                            : undefined
                                    }
                                    compact
                                />
                            </div>
                            <div className="flex justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={closeCategoryModal}
                                    className="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={categoryForm.processing}
                                    className="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {categoryForm.processing ? 'Saving…' : editingId ? 'Update' : 'Create'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Assign products */}
            {assignCategory && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <button
                        type="button"
                        className="fixed inset-0 bg-black/50 backdrop-blur-sm"
                        aria-label="Close"
                        onClick={closeAssign}
                    />
                    <div className="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col border border-gray-100 dark:border-gray-700">
                        <div className="flex items-start justify-between gap-2 p-4 border-b border-gray-100 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Products in “{assignCategory.name}”
                                </h2>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {assignSelected.length} selected
                                </p>
                            </div>
                            <button
                                type="button"
                                onClick={closeAssign}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0"
                            >
                                <X size={20} />
                            </button>
                        </div>
                        <div className="p-4 border-b border-gray-100 dark:border-gray-700">
                            <div className="relative">
                                <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input
                                    type="search"
                                    placeholder="Search by name or SKU…"
                                    value={productSearch}
                                    onChange={e => setProductSearch(e.target.value)}
                                    className={`${inputCls} pl-9`}
                                />
                            </div>
                        </div>
                        <div className="flex-1 overflow-y-auto p-2 min-h-[200px] max-h-[50vh]">
                            {filteredProducts.length === 0 ? (
                                <p className="text-sm text-gray-500 text-center py-8">No products match your search.</p>
                            ) : (
                                <ul className="space-y-1">
                                    {filteredProducts.map(p => (
                                        <li key={p.id}>
                                            <label className="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    checked={assignSelected.includes(p.id)}
                                                    onChange={() => toggleAssignProduct(p.id)}
                                                    className="rounded border-gray-300 text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                />
                                                <span className="text-sm text-gray-900 dark:text-white flex-1 min-w-0 truncate">
                                                    {p.label}
                                                </span>
                                                <span className="text-xs font-mono text-gray-500 shrink-0">
                                                    {p.sku || '—'}
                                                </span>
                                            </label>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <div className="flex justify-end gap-2 p-4 border-t border-gray-100 dark:border-gray-700">
                            <button
                                type="button"
                                onClick={closeAssign}
                                className="px-4 py-2 text-sm rounded-lg border border-gray-200 dark:border-gray-600"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                onClick={saveAssign}
                                className="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700"
                            >
                                Save assignments
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
