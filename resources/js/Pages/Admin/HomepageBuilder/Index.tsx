import React, { useMemo, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs, ConfirmDialog } from '@/Components/Admin';
import {
    DndContext,
    closestCenter,
    PointerSensor,
    KeyboardSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Plus, Save, Trash2, Pencil, Rocket, PackagePlus, Layers, X, Images } from 'lucide-react';

type SectionTypeOption = { value: string; label: string };

type SectionRow = {
    id: number;
    slug: string;
    type: string;
    title: string | null;
    is_enabled: boolean;
    sort_order: number;
    platform: 'web' | 'mobile' | 'both';
    start_at: string | null;
    end_at: string | null;
    priority: number;
    metadata: Record<string, unknown>;
    items_count?: number;
    products_count?: number;
};

type Props = {
    sections: SectionRow[];
    sectionTypes: SectionTypeOption[];
    products: Array<{ id: number; name: string; sku: string }>;
    brands: Array<{ id: number; name: string }>;
    categories: Array<{ id: number; name: string }>;
};

type ItemRow = {
    id: number;
    sort_order: number;
    is_enabled: boolean;
    title: string | null;
    subtitle: string | null;
    cta_type: string | null;
    cta_text: string | null;
    redirect_url: string | null;
    product_id: number | null;
    category_id: number | null;
    brand_id: number | null;
};

function SortableSectionRow({
    section,
    onEdit,
    onDelete,
    onBulkAddWoohoo,
    onManageItems,
    disabled,
}: {
    section: SectionRow;
    onEdit: () => void;
    onDelete: () => void;
    onBulkAddWoohoo: () => void;
    onManageItems: () => void;
    disabled: boolean;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: section.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.85 : 1,
    };

    const isCarousel = section.type === 'carousel' || section.type === 'hero_carousel';
    const showBulkWoohoo = section.type === 'featured_products' || section.type === 'voucher_slider';

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <button
                type="button"
                className="p-2 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:bg-gray-900/60 cursor-grab active:cursor-grabbing"
                {...attributes}
                {...listeners}
                title="Drag to reorder"
            >
                <GripVertical size={18} />
            </button>

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <span className="font-semibold text-gray-900 dark:text-white truncate">
                        {section.title || section.slug}
                    </span>
                    <span className="text-[11px] rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {section.type}
                    </span>
                    {!section.is_enabled && (
                        <span className="text-[11px] rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            Disabled
                        </span>
                    )}
                </div>
                <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                    slug: <span className="font-mono">{section.slug}</span> • platform: {section.platform} • order:{' '}
                    {section.sort_order}
                    <span className="ml-2">
                        • items: {Number(section.items_count ?? 0)} • products: {Number(section.products_count ?? 0)}
                    </span>
                </p>
            </div>

            <div className="flex items-center gap-1">
                {isCarousel ? (
                    <Link
                        href="/panel/settings/hero-slides"
                        className="p-2 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-product-primary dark:text-gray-200 dark:hover:bg-blue-900/20"
                        title="Hero carousel images"
                    >
                        <Images size={16} />
                    </Link>
                ) : (
                    <button
                        type="button"
                        onClick={onManageItems}
                        disabled={disabled}
                        className="p-2 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-product-primary dark:text-gray-200 dark:hover:bg-blue-900/20 disabled:opacity-40 disabled:pointer-events-none"
                        title="Manage section items"
                    >
                        <Layers size={16} />
                    </button>
                )}

                {showBulkWoohoo && (
                    <button
                        type="button"
                        onClick={onBulkAddWoohoo}
                        disabled={disabled}
                        className="p-2 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-product-primary dark:text-gray-200 dark:hover:bg-blue-900/20 disabled:opacity-40 disabled:pointer-events-none"
                        title="Bulk add Woohoo products"
                    >
                        <PackagePlus size={16} />
                    </button>
                )}
                <button
                    type="button"
                    onClick={onEdit}
                    disabled={disabled}
                    className="p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 disabled:opacity-40 disabled:pointer-events-none"
                    title="Edit"
                >
                    <Pencil size={16} />
                </button>
                <button
                    type="button"
                    onClick={onDelete}
                    disabled={disabled}
                    className="p-2 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 dark:text-gray-200 dark:hover:bg-red-900/20 disabled:opacity-40 disabled:pointer-events-none"
                    title="Delete"
                >
                    <Trash2 size={16} />
                </button>
            </div>
        </div>
    );
}

function SortableItemRow({
    item,
    label,
    onToggleEnabled,
    onDelete,
}: {
    item: ItemRow;
    label: string;
    onToggleEnabled: () => void;
    onDelete: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: item.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.85 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className="flex items-center gap-3 rounded-lg border border-gray-100 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800"
        >
            <button
                type="button"
                className="p-2 rounded-lg bg-gray-50 text-gray-600 hover:bg-gray-100 dark:bg-gray-900/40 dark:text-gray-300 dark:hover:bg-gray-900/60 cursor-grab active:cursor-grabbing"
                {...attributes}
                {...listeners}
                title="Drag to reorder"
            >
                <GripVertical size={18} />
            </button>

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    <span className="font-medium text-gray-900 dark:text-white truncate">{label}</span>
                    {!item.is_enabled && (
                        <span className="text-[11px] rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            Disabled
                        </span>
                    )}
                </div>
                <p className="mt-0.5 text-xs text-gray-500 dark:text-gray-400 truncate">
                    order: {item.sort_order} • id: {item.id}
                </p>
            </div>

            <div className="flex items-center gap-1">
                <button
                    type="button"
                    onClick={onToggleEnabled}
                    className="px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    title="Toggle enabled"
                >
                    {item.is_enabled ? 'Disable' : 'Enable'}
                </button>
                <button
                    type="button"
                    onClick={onDelete}
                    className="p-2 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 dark:text-gray-200 dark:hover:bg-red-900/20"
                    title="Remove item"
                >
                    <Trash2 size={16} />
                </button>
            </div>
        </div>
    );
}

export default function Index({ sections: initialSections, sectionTypes, products, brands, categories }: Props) {
    const page = usePage<{ auth?: { user?: { permissions?: string[] } } }>();
    const canEdit = page.props.auth?.user?.permissions?.includes('settings.update') ?? false;

    const [sections, setSections] = useState<SectionRow[]>(
        [...initialSections].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id),
    );
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [editing, setEditing] = useState<SectionRow | null>(null);
    const [modalOpen, setModalOpen] = useState(false);

    const [itemsOpen, setItemsOpen] = useState(false);
    const [itemsSection, setItemsSection] = useState<SectionRow | null>(null);
    const [items, setItems] = useState<ItemRow[]>([]);
    const [itemsBusy, setItemsBusy] = useState<string | null>(null);
    const [newItemKind, setNewItemKind] = useState<'product' | 'brand' | 'category'>('product');
    const [newItemRefId, setNewItemRefId] = useState<string>('');
    const [bulkQuery, setBulkQuery] = useState('');
    const [bulkSelectedIds, setBulkSelectedIds] = useState<Record<string, boolean>>({});
    const [toast, setToast] = useState<string | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const orderedIds = useMemo(() => sections.map(s => s.id), [sections]);
    const orderedItemIds = useMemo(() => items.map(i => i.id), [items]);
    const filteredBulkProducts = useMemo(() => {
        const q = bulkQuery.trim().toLowerCase();
        if (q === '') return products;
        return products.filter(p => `${p.name} ${p.sku}`.toLowerCase().includes(q));
    }, [products, bulkQuery]);

    const bulkSelectedCount = useMemo(
        () => Object.values(bulkSelectedIds).filter(Boolean).length,
        [bulkSelectedIds],
    );

    const labelForItem = (it: ItemRow) => {
        if (it.product_id) {
            const p = products.find(x => x.id === it.product_id);
            return p ? `Product: ${p.name} (${p.sku})` : `Product #${it.product_id}`;
        }
        if (it.brand_id) {
            const b = brands.find(x => x.id === it.brand_id);
            return b ? `Brand: ${b.name}` : `Brand #${it.brand_id}`;
        }
        if (it.category_id) {
            const c = categories.find(x => x.id === it.category_id);
            return c ? `Category: ${c.name}` : `Category #${it.category_id}`;
        }
        return `Item #${it.id}`;
    };

    const onDragEnd = (event: DragEndEvent) => {
        if (!canEdit) return;
        const { active, over } = event;
        if (!over || active.id === over.id) return;
        const oldIndex = sections.findIndex(s => s.id === Number(active.id));
        const newIndex = sections.findIndex(s => s.id === Number(over.id));
        if (oldIndex < 0 || newIndex < 0) return;
        const next = arrayMove(sections, oldIndex, newIndex).map((s, idx) => ({ ...s, sort_order: idx + 1 }));
        setSections(next);
        router.post('/panel/homepage-builder/sections/reorder', { ids: next.map(s => s.id) }, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (deleteId === null) return;
        router.delete(`/panel/homepage-builder/sections/${deleteId}`, { preserveScroll: true, onFinish: () => setDeleteId(null) });
    };

    const openManageItems = async (s: SectionRow) => {
        setItemsSection(s);
        setItemsOpen(true);
        setItemsBusy('load');
        try {
            const resp = await (window as any).axios.get(`/panel/homepage-builder/sections/${s.id}/items`);
            const next = (resp?.data?.data?.items ?? resp?.data?.items ?? []) as ItemRow[];
            setItems(Array.isArray(next) ? next : []);
        } catch {
            setItems([]);
        } finally {
            setItemsBusy(null);
        }
    };

    const bulkAddWoohoo = (s: SectionRow) => {
        if (!canEdit) return;
        setToast(null);
        router.post(`/panel/homepage-builder/sections/${s.id}/bulk-add-woohoo-products`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setToast(`Woohoo products added to "${s.slug}".`);
                router.reload({ only: ['sections'] });
            },
            onError: () => {
                setToast(`Bulk add Woohoo products failed for "${s.slug}".`);
            },
        });
    };

    const closeItems = () => {
        setItemsOpen(false);
        setItemsSection(null);
        setItems([]);
        setItemsBusy(null);
        setNewItemKind('product');
        setNewItemRefId('');
        setBulkQuery('');
        setBulkSelectedIds({});
    };

    const addItem = async () => {
        if (!itemsSection) return;
        const refId = Number(newItemRefId || 0);
        if (!refId) return;
        setItemsBusy('add');
        try {
            const payload: any = { is_enabled: true };
            if (newItemKind === 'product') payload.product_id = refId;
            if (newItemKind === 'brand') payload.brand_id = refId;
            if (newItemKind === 'category') payload.category_id = refId;
            const resp = await (window as any).axios.post(`/panel/homepage-builder/sections/${itemsSection.id}/items`, payload);
            const created = resp?.data?.data?.item ?? null;
            if (created && typeof created.id === 'number') {
                setItems(prev => [...prev, created]);
                setNewItemRefId('');
            }
        } finally {
            setItemsBusy(null);
        }
    };

    const toggleBulkSelected = (id: number) => {
        const k = String(id);
        setBulkSelectedIds(prev => ({ ...prev, [k]: !prev[k] }));
    };

    const selectAllFiltered = () => {
        const next: Record<string, boolean> = { ...bulkSelectedIds };
        filteredBulkProducts.forEach(p => {
            next[String(p.id)] = true;
        });
        setBulkSelectedIds(next);
    };

    const clearAllBulk = () => setBulkSelectedIds({});

    const bulkAddSelectedProducts = async () => {
        if (!itemsSection) return;
        const ids = Object.entries(bulkSelectedIds)
            .filter(([, v]) => v)
            .map(([k]) => Number(k))
            .filter(Boolean);
        if (ids.length === 0) return;

        setItemsBusy('bulk-add');
        try {
            await (window as any).axios.post(`/panel/homepage-builder/sections/${itemsSection.id}/items/bulk`, {
                kind: 'product',
                ids,
            });
            // Reload items to get created IDs + correct order.
            await openManageItems(itemsSection);
            setBulkSelectedIds({});
        } finally {
            setItemsBusy(null);
        }
    };

    const toggleItemEnabled = async (it: ItemRow) => {
        setItemsBusy(`toggle:${it.id}`);
        try {
            await (window as any).axios.put(`/panel/homepage-builder/items/${it.id}`, { is_enabled: !it.is_enabled });
            setItems(prev => prev.map(x => (x.id === it.id ? { ...x, is_enabled: !x.is_enabled } : x)));
        } finally {
            setItemsBusy(null);
        }
    };

    const deleteItem = async (it: ItemRow) => {
        setItemsBusy(`delete:${it.id}`);
        try {
            await (window as any).axios.delete(`/panel/homepage-builder/items/${it.id}`);
            setItems(prev => prev.filter(x => x.id !== it.id));
        } finally {
            setItemsBusy(null);
        }
    };

    const onDragEndItems = async (event: DragEndEvent) => {
        if (!canEdit) return;
        if (!itemsSection) return;
        const { active, over } = event;
        if (!over || active.id === over.id) return;
        const oldIndex = items.findIndex(i => i.id === Number(active.id));
        const newIndex = items.findIndex(i => i.id === Number(over.id));
        if (oldIndex < 0 || newIndex < 0) return;
        const next = arrayMove(items, oldIndex, newIndex).map((i, idx) => ({ ...i, sort_order: idx + 1 }));
        setItems(next);
        try {
            await (window as any).axios.post(`/panel/homepage-builder/sections/${itemsSection.id}/items/reorder`, { ids: next.map(i => i.id) });
        } catch {
            // if reorder fails, reload
            openManageItems(itemsSection);
        }
    };

    const openCreate = () => {
        setEditing(null);
        setModalOpen(true);
    };
    const openEdit = (s: SectionRow) => {
        setEditing(s);
        setModalOpen(true);
    };

    const closeModal = () => {
        setModalOpen(false);
        setEditing(null);
    };

    const submitSection = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const fd = new FormData(form);
        const payload = {
            slug: String(fd.get('slug') || '').trim(),
            type: String(fd.get('type') || '').trim(),
            title: String(fd.get('title') || '').trim() || null,
            is_enabled: fd.get('is_enabled') === 'on',
            platform: String(fd.get('platform') || 'both'),
            start_at: String(fd.get('start_at') || '') || null,
            end_at: String(fd.get('end_at') || '') || null,
            priority: Number(fd.get('priority') || 0),
        };

        if (editing) {
            router.put(`/panel/homepage-builder/sections/${editing.id}`, payload as any, { preserveScroll: true, onSuccess: closeModal });
        } else {
            router.post('/panel/homepage-builder/sections', payload as any, { preserveScroll: true, onSuccess: closeModal });
        }
    };

    return (
        <AdminLayout>
            <Head title="Homepage Builder" />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs items={[{ label: 'Homepage Builder' }]} />

                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Homepage Builder</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Drag sections to reorder. Publish creates a version snapshot for the mobile API cache.
                        </p>
                    </div>

                    {canEdit && (
                        <div className="flex flex-wrap items-center gap-2 justify-end">
                            <button
                                type="button"
                                onClick={() => router.post('/panel/homepage-builder/publish', {}, { preserveScroll: true })}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-product-primary text-white text-sm font-medium hover:opacity-90"
                            >
                                <Rocket size={18} />
                                Publish
                            </button>
                            <button
                                type="button"
                                onClick={openCreate}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-product-primary text-white text-sm font-medium hover:opacity-90"
                            >
                                <Plus size={18} />
                                Add section
                            </button>
                        </div>
                    )}
                </div>

                <ConfirmDialog
                    open={deleteId !== null}
                    onClose={() => setDeleteId(null)}
                    onConfirm={confirmDelete}
                    title="Remove this section?"
                    message="This section will disappear from the homepage layout."
                    confirmLabel="Delete"
                    variant="danger"
                />

                {sections.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No sections yet.
                    </div>
                ) : (
                    <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                        <SortableContext items={orderedIds} strategy={verticalListSortingStrategy}>
                            <div className="space-y-3">
                                {sections.map(s => (
                                    <SortableSectionRow
                                        key={s.id}
                                        section={s}
                                        onEdit={() => openEdit(s)}
                                        onDelete={() => setDeleteId(s.id)}
                                        onBulkAddWoohoo={() => bulkAddWoohoo(s)}
                                        onManageItems={() => openManageItems(s)}
                        disabled={!canEdit}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}

                {toast && (
                    <div className="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-product-primary dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-100">
                        {toast}
                    </div>
                )}

                {itemsOpen && itemsSection && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <button type="button" className="fixed inset-0 bg-black/50" onClick={closeItems} />
                        <div className="relative z-10 w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Manage items</h3>
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Section: <span className="font-mono">{itemsSection.slug}</span>
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={closeItems}
                                    className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
                                    aria-label="Close"
                                >
                                    <X size={18} />
                                </button>
                            </div>

                            <div className="mt-4 grid gap-3 sm:grid-cols-[160px_1fr_auto]">
                                <select
                                    value={newItemKind}
                                    onChange={e => setNewItemKind(e.target.value as any)}
                                    className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                >
                                    <option value="product">Product</option>
                                    <option value="brand">Brand</option>
                                    <option value="category">Category</option>
                                </select>

                                {newItemKind === 'product' ? (
                                    <select
                                        value={newItemRefId}
                                        onChange={e => setNewItemRefId(e.target.value)}
                                        className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="">Select product…</option>
                                        {products.map(p => (
                                            <option key={p.id} value={String(p.id)}>
                                                {p.name} ({p.sku})
                                            </option>
                                        ))}
                                    </select>
                                ) : newItemKind === 'brand' ? (
                                    <select
                                        value={newItemRefId}
                                        onChange={e => setNewItemRefId(e.target.value)}
                                        className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="">Select brand…</option>
                                        {brands.map(b => (
                                            <option key={b.id} value={String(b.id)}>
                                                {b.name}
                                            </option>
                                        ))}
                                    </select>
                                ) : (
                                    <select
                                        value={newItemRefId}
                                        onChange={e => setNewItemRefId(e.target.value)}
                                        className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <option value="">Select category…</option>
                                        {categories.map(c => (
                                            <option key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                )}

                                <button
                                    type="button"
                                    onClick={addItem}
                                    disabled={!canEdit || itemsBusy !== null || newItemRefId === ''}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-product-primary text-white text-sm font-medium hover:opacity-90 disabled:opacity-50"
                                >
                                    <Plus size={18} />
                                    Add
                                </button>
                            </div>

                            <div className="mt-4 rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm font-semibold text-gray-900 dark:text-white">Bulk add products</p>
                                        <p className="mt-0.5 text-xs text-gray-600 dark:text-gray-400">
                                            Search + tick multiple products, then add them in one go.
                                        </p>
                                    </div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <button
                                            type="button"
                                            onClick={selectAllFiltered}
                                            disabled={!canEdit || itemsBusy !== null || filteredBulkProducts.length === 0}
                                            className="px-3 py-2 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                                        >
                                            Select all (filtered)
                                        </button>
                                        <button
                                            type="button"
                                            onClick={clearAllBulk}
                                            disabled={!canEdit || itemsBusy !== null || bulkSelectedCount === 0}
                                            className="px-3 py-2 rounded-lg text-xs font-medium bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 disabled:opacity-50"
                                        >
                                            Clear
                                        </button>
                                        <button
                                            type="button"
                                            onClick={bulkAddSelectedProducts}
                                            disabled={!canEdit || itemsBusy !== null || bulkSelectedCount === 0}
                                            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-product-primary text-white text-xs font-semibold hover:opacity-90 disabled:opacity-50"
                                        >
                                            <Plus size={16} />
                                            Add selected ({bulkSelectedCount})
                                        </button>
                                    </div>
                                </div>

                                <div className="mt-3 grid gap-3">
                                    <input
                                        value={bulkQuery}
                                        onChange={e => setBulkQuery(e.target.value)}
                                        placeholder="Search products by name or SKU…"
                                        className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                    />

                                    <div className="max-h-64 overflow-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
                                        {filteredBulkProducts.length === 0 ? (
                                            <div className="p-4 text-sm text-gray-600 dark:text-gray-300">No matching products.</div>
                                        ) : (
                                            <ul className="divide-y divide-gray-100 dark:divide-gray-700">
                                                {filteredBulkProducts.slice(0, 250).map(p => {
                                                    const checked = !!bulkSelectedIds[String(p.id)];
                                                    return (
                                                        <li key={p.id}>
                                                            <label className="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40 cursor-pointer">
                                                                <input
                                                                    type="checkbox"
                                                                    checked={checked}
                                                                    onChange={() => toggleBulkSelected(p.id)}
                                                                    className="h-4 w-4 rounded border-gray-300 text-product-primary"
                                                                />
                                                                <span className="min-w-0 flex-1">
                                                                    <span className="block truncate text-sm font-medium text-gray-900 dark:text-white">
                                                                        {p.name}
                                                                    </span>
                                                                    <span className="block truncate text-xs text-gray-500 dark:text-gray-400">
                                                                        {p.sku}
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        )}
                                    </div>
                                    {filteredBulkProducts.length > 250 && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Showing first 250 results. Narrow your search to select more precisely.
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="mt-5">
                                {itemsBusy === 'load' ? (
                                    <div className="text-sm text-gray-600 dark:text-gray-300">Loading…</div>
                                ) : items.length === 0 ? (
                                    <div className="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-600 dark:border-gray-700 dark:text-gray-300">
                                        No items yet.
                                    </div>
                                ) : (
                                    <DndContext collisionDetection={closestCenter} onDragEnd={onDragEndItems}>
                                        <SortableContext items={orderedItemIds} strategy={verticalListSortingStrategy}>
                                            <div className="space-y-2">
                                                {items.map(it => (
                                                    <SortableItemRow
                                                        key={it.id}
                                                        item={it}
                                                        label={labelForItem(it)}
                                                        onToggleEnabled={() => toggleItemEnabled(it)}
                                                        onDelete={() => deleteItem(it)}
                                                    />
                                                ))}
                                            </div>
                                        </SortableContext>
                                    </DndContext>
                                )}
                            </div>

                            <div className="mt-6 flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeItems}
                                    className="px-4 py-2 text-sm rounded-lg border dark:border-gray-600 text-gray-700 dark:text-gray-200"
                                >
                                    Done
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                {modalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <button type="button" className="fixed inset-0 bg-black/50" onClick={closeModal} />
                        <div className="relative z-10 w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                {editing ? 'Edit section' : 'Create section'}
                            </h3>
                            <form onSubmit={submitSection} className="mt-4 grid gap-4">
                                {!editing && (
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Slug</label>
                                        <input
                                            name="slug"
                                            defaultValue=""
                                            required
                                            pattern="[a-z0-9_-]+"
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </div>
                                )}
                                <div className="grid sm:grid-cols-2 gap-3">
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Type</label>
                                        <select
                                            name="type"
                                            defaultValue={editing?.type ?? 'custom'}
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            {sectionTypes.map(opt => (
                                                <option key={opt.value} value={opt.value}>
                                                    {opt.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Platform</label>
                                        <select
                                            name="platform"
                                            defaultValue={editing?.platform ?? 'both'}
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            <option value="both">Both</option>
                                            <option value="web">Web</option>
                                            <option value="mobile">Mobile</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-xs text-gray-500 mb-1">Title</label>
                                    <input
                                        name="title"
                                        defaultValue={editing?.title ?? ''}
                                        className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                    />
                                </div>
                                <div className="grid sm:grid-cols-3 gap-3">
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Priority</label>
                                        <input
                                            name="priority"
                                            type="number"
                                            defaultValue={editing?.priority ?? 0}
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">Start at</label>
                                        <input
                                            name="start_at"
                                            type="datetime-local"
                                            defaultValue={editing?.start_at ? editing.start_at.slice(0, 16) : ''}
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-xs text-gray-500 mb-1">End at</label>
                                        <input
                                            name="end_at"
                                            type="datetime-local"
                                            defaultValue={editing?.end_at ? editing.end_at.slice(0, 16) : ''}
                                            className="w-full rounded-lg border px-3 py-2 text-sm dark:bg-gray-700 dark:border-gray-600"
                                        />
                                    </div>
                                </div>
                                <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                    <input name="is_enabled" type="checkbox" defaultChecked={editing ? editing.is_enabled : true} />
                                    Enabled
                                </label>
                                <div className="flex justify-end gap-2 pt-2">
                                    <button type="button" onClick={closeModal} className="px-4 py-2 text-sm rounded-lg border dark:border-gray-600">
                                        Cancel
                                    </button>
                                    <button type="submit" className="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-product-primary text-white hover:opacity-90">
                                        <Save size={18} />
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

