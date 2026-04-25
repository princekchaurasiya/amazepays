import React, { useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
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
import { GripVertical, Plus, Save, Trash2, Pencil, Rocket } from 'lucide-react';

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
};

type Props = {
    sections: SectionRow[];
    sectionTypes: SectionTypeOption[];
};

function SortableSectionRow({
    section,
    onEdit,
    onDelete,
}: {
    section: SectionRow;
    onEdit: () => void;
    onDelete: () => void;
}) {
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: section.id });
    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.85 : 1,
    };

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
                </p>
            </div>

            <div className="flex items-center gap-1">
                <button
                    type="button"
                    onClick={onEdit}
                    className="p-2 rounded-lg text-gray-600 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    title="Edit"
                >
                    <Pencil size={16} />
                </button>
                <button
                    type="button"
                    onClick={onDelete}
                    className="p-2 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-600 dark:text-gray-200 dark:hover:bg-red-900/20"
                    title="Delete"
                >
                    <Trash2 size={16} />
                </button>
            </div>
        </div>
    );
}

export default function Index({ sections: initialSections, sectionTypes }: Props) {
    const page = usePage<{ auth?: { user?: { permissions?: string[] } } }>();
    const canEdit = page.props.auth?.user?.permissions?.includes('settings.update') ?? false;

    const [sections, setSections] = useState<SectionRow[]>(
        [...initialSections].sort((a, b) => a.sort_order - b.sort_order || a.id - b.id),
    );
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [editing, setEditing] = useState<SectionRow | null>(null);
    const [modalOpen, setModalOpen] = useState(false);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 6 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const orderedIds = useMemo(() => sections.map(s => s.id), [sections]);

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
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
                            >
                                <Rocket size={18} />
                                Publish
                            </button>
                            <button
                                type="button"
                                onClick={openCreate}
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700"
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
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}

                {modalOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                        <button type="button" className="fixed inset-0 bg-black/50" onClick={closeModal} />
                        <div className="relative z-10 w-full max-w-xl rounded-xl border border-gray-100 bg-white p-6 shadow-2xl dark:border-gray-700 dark:bg-gray-800">
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
                                    <button type="submit" className="inline-flex items-center gap-2 px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
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

