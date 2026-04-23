import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    type DragEndEvent,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    rectSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { GripVertical, Star, Trash2 } from 'lucide-react';
import ConfirmDialog from './ConfirmDialog';
import AdminImageDropzone from './AdminImageDropzone';

export type GalleryItem = {
    id: number;
    url: string;
    collection: string;
    sort_order: number;
    alt_text?: string | null;
    /** supplier = from API/sync JSON; upload = product_media */
    source?: 'supplier' | 'upload';
};

type Props = {
    productId: number;
    items: GalleryItem[];
};

function normalizePreviewUrl(url: string): string {
    if (!url) return url;
    try {
        const parsed = new URL(url);
        const isLocal = parsed.hostname === '127.0.0.1' || parsed.hostname === 'localhost';
        if (isLocal && typeof window !== 'undefined') {
            return `${window.location.origin}${parsed.pathname}${parsed.search}${parsed.hash}`;
        }
        return url;
    } catch {
        return url;
    }
}

function SupplierThumb({ item }: { item: GalleryItem }) {
    return (
        <div className="relative rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-600 overflow-hidden bg-slate-50 dark:bg-slate-900/50">
            <span className="absolute top-1 right-1 z-10 rounded bg-slate-800/85 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                Supplier
            </span>
            <img src={normalizePreviewUrl(item.url)} alt="" className="w-full h-32 object-contain bg-white" />
            <p className="text-[10px] text-slate-500 dark:text-slate-400 px-2 py-1 truncate" title={item.url}>
                From catalog sync — upload below to override
            </p>
        </div>
    );
}

function SortableThumb({
    item,
    onHero,
    onDelete,
}: {
    item: GalleryItem;
    onHero: () => void;
    onDelete: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: item.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.85 : 1,
    };

    const isHero = item.collection === 'hero';

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`relative group rounded-lg border-2 overflow-hidden bg-gray-50 dark:bg-gray-900 ${
                isHero ? 'border-amber-400 ring-2 ring-amber-200' : 'border-gray-200 dark:border-gray-700'
            }`}
        >
            <img src={normalizePreviewUrl(item.url)} alt="" className="w-full h-32 object-cover" />
            <div className="absolute top-1 left-1 flex gap-1">
                <button
                    type="button"
                    className="p-1 rounded bg-white/90 dark:bg-gray-800/90 shadow cursor-grab active:cursor-grabbing"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical size={14} className="text-gray-600" />
                </button>
            </div>
            <div className="absolute bottom-1 right-1 flex gap-1">
                <button
                    type="button"
                    onClick={onHero}
                    className={`p-1.5 rounded shadow ${isHero ? 'bg-amber-500 text-white' : 'bg-white/90 text-gray-600 hover:text-amber-600'}`}
                    title="Set as main image"
                >
                    <Star size={14} fill={isHero ? 'currentColor' : 'none'} />
                </button>
                <button
                    type="button"
                    onClick={onDelete}
                    className="p-1.5 rounded bg-white/90 text-red-600 shadow hover:bg-red-50"
                    title="Remove"
                >
                    <Trash2 size={14} />
                </button>
            </div>
        </div>
    );
}

export default function ImageGalleryManager({ productId, items: initialItems }: Props) {
    const [items, setItems] = useState<GalleryItem[]>(() =>
        [...initialItems].sort((a, b) => a.sort_order - b.sort_order),
    );
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [uploading, setUploading] = useState(false);

    const supplierItems = items.filter(i => i.source === 'supplier');
    const uploadedItems = items.filter(i => i.source !== 'supplier');

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    React.useEffect(() => {
        setItems([...initialItems].sort((a, b) => a.sort_order - b.sort_order));
    }, [initialItems]);

    const onDragEnd = (event: DragEndEvent) => {
        const { active, over } = event;
        if (!over || active.id === over.id) return;
        const movable = uploadedItems;
        const oldIndex = movable.findIndex(i => i.id === Number(active.id));
        const newIndex = movable.findIndex(i => i.id === Number(over.id));
        if (oldIndex < 0 || newIndex < 0) return;
        const reorderedUploads = arrayMove(movable, oldIndex, newIndex);
        const next = [...supplierItems, ...reorderedUploads];
        setItems(next);
        const orderedIds = reorderedUploads.map(i => i.id);
        router.put(
            `/panel/products/${productId}/media/reorder`,
            { ordered_ids: orderedIds },
            { preserveScroll: true, preserveState: true },
        );
    };

    const setHero = (id: number) => {
        router.put(`/panel/products/${productId}/media/${id}/hero`, {}, { preserveScroll: true });
    };

    const confirmDelete = () => {
        if (deleteId === null) return;
        router.delete(`/panel/products/${productId}/media/${deleteId}`, { preserveScroll: true });
        setDeleteId(null);
    };

    const onFileSelected = (file: File) => {
        setUploading(true);
        const fd = new FormData();
        fd.append('file', file);
        router.post(`/panel/products/${productId}/media`, fd, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => setUploading(false),
        });
    };

    const isEmpty = supplierItems.length === 0 && uploadedItems.length === 0;

    return (
        <div className="space-y-4">
            <ConfirmDialog
                open={deleteId !== null}
                onClose={() => setDeleteId(null)}
                onConfirm={confirmDelete}
                title="Remove this image?"
                message="Customers will fall back to the next image or the supplier thumbnail."
                confirmLabel="Remove"
                variant="danger"
            />

            <AdminImageDropzone
                onFileSelected={onFileSelected}
                busy={uploading}
                maxFileBytes={5 * 1024 * 1024}
            />

            {isEmpty ? (
                <p className="text-sm text-gray-500 text-center">No gallery images yet. Upload a sharp product photo above.</p>
            ) : (
                <div className="space-y-6">
                    {supplierItems.length > 0 && (
                        <div>
                            <p className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                Supplier / API images (read-only)
                            </p>
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                {supplierItems.map(item => (
                                    <SupplierThumb key={item.id} item={item} />
                                ))}
                            </div>
                        </div>
                    )}

                    {uploadedItems.length > 0 && (
                        <div>
                            {supplierItems.length > 0 && (
                                <p className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">
                                    Your uploads (drag to reorder)
                                </p>
                            )}
                            <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={onDragEnd}>
                                <SortableContext items={uploadedItems.map(i => i.id)} strategy={rectSortingStrategy}>
                                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                        {uploadedItems.map(item => (
                                            <SortableThumb
                                                key={item.id}
                                                item={item}
                                                onHero={() => setHero(item.id)}
                                                onDelete={() => setDeleteId(item.id)}
                                            />
                                        ))}
                                    </div>
                                </SortableContext>
                            </DndContext>
                        </div>
                    )}
                </div>
            )}
            <p className="text-xs text-gray-500">
                Star marks the main image shown in listings. Drag to reorder uploaded images only. Supplier images come from
                the catalog until you add uploads here.
            </p>
        </div>
    );
}
