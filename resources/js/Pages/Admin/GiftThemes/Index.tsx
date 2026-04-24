import React, { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    ActionButtons,
    AdminImageDropzone,
    AdminImagePreviewList,
    AdminNameSlugFields,
    Breadcrumbs,
    StatusBadge,
} from '@/Components/Admin';
import type { AdminImagePreviewItem } from '@/Components/Admin/AdminImagePreviewList';
import { Brush, Plus } from 'lucide-react';

type ThemeRow = {
    id: number;
    name: string;
    slug: string;
    primary_image_url: string | null;
    gallery_urls?: string[];
    gallery_paths?: string[];
    is_active: boolean;
    sort_order: number;
};

type Paginated<T> = {
    data: T[];
};

type Props = {
    themes: Paginated<ThemeRow>;
};

type ThemeForm = {
    name: string;
    slug: string;
    gallery_files: File[];
    sort_order: number;
    is_active: boolean;
    retained_gallery_images?: string[];
    /** Laravel method spoofing for multipart updates (PHP does not parse multipart PUT bodies reliably). */
    _method?: 'put';
};

const BASE = '/panel/gift-themes';

const initialForm: ThemeForm = {
    name: '',
    slug: '',
    gallery_files: [],
    sort_order: 0,
    is_active: true,
};

async function hashFileContent(file: File): Promise<string> {
    const buf = await file.arrayBuffer();
    const digest = await crypto.subtle.digest('SHA-256', buf);
    const bytes = Array.from(new Uint8Array(digest));
    return bytes.map(b => b.toString(16).padStart(2, '0')).join('');
}

export default function Index({ themes }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [editingName, setEditingName] = useState<string>('');
    const [retainedPersistedImages, setRetainedPersistedImages] = useState<Array<{ path: string; url: string }>>([]);
    const { data, setData, post, processing, reset, errors, clearErrors, transform } = useForm<ThemeForm>(initialForm);

    transform(formData =>
        editingId
            ? {
                  ...formData,
                  _method: 'put',
                  retained_gallery_images: retainedPersistedImages.map(item => item.path),
              }
            : { ...formData },
    );

    const localPreviewItems = useMemo<AdminImagePreviewItem[]>(
        () =>
            data.gallery_files.map((file, index) => ({
                kind: 'local',
                key: `local-${index}-${file.name}-${file.size}`,
                name: file.name,
                url: URL.createObjectURL(file),
            })),
        [data.gallery_files],
    );

    useEffect(() => {
        return () => {
            localPreviewItems.forEach(item => {
                URL.revokeObjectURL(item.url);
            });
        };
    }, [localPreviewItems]);

    // Multipart + real HTTP PUT: PHP often omits parsed fields; use POST + _method (handled in transform when editing).
    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(editingId ? `${BASE}/${editingId}` : BASE, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                if (editingId) {
                    setEditingId(null);
                    setEditingName('');
                }
                setRetainedPersistedImages([]);
                reset();
                clearErrors();
            },
        });
    };

    const startEdit = (theme: ThemeRow) => {
        setEditingId(theme.id);
        setEditingName(theme.name);
        setData({
            name: theme.name,
            slug: theme.slug,
            gallery_files: [],
            sort_order: theme.sort_order,
            is_active: theme.is_active,
        });
        const paths = Array.isArray(theme.gallery_paths) ? theme.gallery_paths : [];
        const urls = Array.isArray(theme.gallery_urls) ? theme.gallery_urls : [];
        const persisted = paths
            .map((path, idx) => ({ path, url: urls[idx] ?? '' }))
            .filter(item => item.path && item.url);
        setRetainedPersistedImages(persisted);
        clearErrors();
    };

    const cancelEdit = () => {
        setEditingId(null);
        setEditingName('');
        setRetainedPersistedImages([]);
        reset();
        clearErrors();
    };

    const removeLocalPreview = useCallback((itemKey: string) => {
        const index = Number(itemKey.split('-')[1]);
        if (!Number.isFinite(index)) {
            return;
        }
        setData(
            'gallery_files',
            data.gallery_files.filter((_, i) => i !== index),
        );
    }, [setData, data.gallery_files]);

    const persistedPreviewItems = useMemo<AdminImagePreviewItem[]>(
        () =>
            retainedPersistedImages.map((item, idx) => ({
                kind: 'persisted',
                key: `persisted-${idx}-${item.path}`,
                path: item.path,
                url: item.url,
            })),
        [retainedPersistedImages],
    );

    const removePersistedPreview = useCallback((itemKey: string) => {
        const path = itemKey.replace(/^persisted-\d+-/, '');
        setRetainedPersistedImages(current => current.filter(item => item.path !== path));
    }, []);

    const handleGalleryFilesChange = useCallback(async (files: File[]) => {
        const hashed = await Promise.all(
            files.map(async file => ({
                file,
                hash: await hashFileContent(file),
            })),
        );

        const seen = new Set<string>();
        const uniqueFiles: File[] = [];
        hashed.forEach(({ file, hash }) => {
            if (seen.has(hash)) {
                return;
            }
            seen.add(hash);
            uniqueFiles.push(file);
        });

        setData('gallery_files', uniqueFiles);
    }, [setData]);

    const setNameSlugData = useCallback((patch: { name?: string; slug?: string }) => {
        if (patch.name !== undefined) {
            setData('name', patch.name);
        }
        if (patch.slug !== undefined) {
            setData('slug', patch.slug);
        }
    }, [setData]);

    const activeCount = useMemo(() => themes.data.filter((x) => x.is_active).length, [themes.data]);

    return (
        <AdminLayout>
            <Head title="Gift themes" />

            <div className="space-y-6">
                <Breadcrumbs
                    items={[
                        { label: 'Dashboard', href: '/panel' },
                        { label: 'Gift themes' },
                    ]}
                />

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-2">
                        <Brush className="text-indigo-600" size={26} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Gift themes</h1>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-300">
                        {themes.data.length} total, {activeCount} active
                    </p>
                </div>

                <form onSubmit={submit} className="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                    {editingId ? (
                        <p className="mb-3 rounded-lg border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-900/20 dark:text-indigo-300">
                            Editing: <span className="font-semibold">{editingName}</span> (#{editingId})
                        </p>
                    ) : null}
                    <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        <AdminNameSlugFields
                            name={data.name}
                            slug={data.slug}
                            setData={setNameSlugData}
                            syncResetKey={editingId ?? 'new'}
                            nameLabel="Theme name"
                            slugLabel="Slug"
                            nameError={typeof errors.name === 'string' ? errors.name : undefined}
                            slugError={typeof errors.slug === 'string' ? errors.slug : undefined}
                            disabled={processing}
                        />
                        <div>
                            <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Sort order</label>
                            <input
                                type="number"
                                min={0}
                                value={data.sort_order}
                                onChange={(e) => setData('sort_order', Number(e.target.value || 0))}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900"
                            />
                            {errors.sort_order && <p className="mt-1 text-xs text-red-600">{errors.sort_order}</p>}
                        </div>
                        <div className="md:col-span-2 lg:col-span-3">
                            <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-300">Gallery images (multiple)</label>
                            <AdminImageDropzone
                                multiple
                                files={data.gallery_files}
                                onFilesChange={handleGalleryFilesChange}
                                primaryText="Upload images (PNG, JPG, WebP — max 5MB each)"
                                error={
                                    typeof errors.gallery_files === 'string' ? errors.gallery_files : undefined
                                }
                                maxFileBytes={5 * 1024 * 1024}
                                compact
                            />
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Click the X icon on any preview to remove it before saving.
                            </p>
                            {editingId && (
                                <div className="mt-2 space-y-1">
                                    <p className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                        Existing gallery images
                                    </p>
                                    <AdminImagePreviewList
                                        items={persistedPreviewItems}
                                        onRemove={removePersistedPreview}
                                        emptyText="No saved gallery images."
                                    />
                                </div>
                            )}
                            <div className="mt-2 space-y-1">
                                <p className="text-xs font-medium text-gray-600 dark:text-gray-300">
                                    Newly selected images
                                </p>
                                <AdminImagePreviewList
                                    items={localPreviewItems}
                                    onRemove={removeLocalPreview}
                                    emptyText="No new images selected."
                                />
                            </div>
                        </div>
                    </div>

                    <label className="mt-3 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(e) => setData('is_active', e.target.checked)}
                            className="rounded border-gray-300 text-indigo-600"
                        />
                        Active
                    </label>

                    <div className="mt-4 flex flex-wrap items-center gap-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                        >
                            <Plus size={15} />
                            {editingId ? 'Update theme' : 'Add theme'}
                        </button>
                        {editingId ? (
                            <button
                                type="button"
                                onClick={cancelEdit}
                                className="rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 dark:border-gray-600 dark:text-gray-200"
                            >
                                Cancel edit
                            </button>
                        ) : null}
                    </div>
                </form>

                <div className="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                            <tr>
                                <th className="px-4 py-3">Preview</th>
                                <th className="px-4 py-3">Name</th>
                                <th className="px-4 py-3">Slug</th>
                                <th className="px-4 py-3">Sort</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                            {themes.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                        No gift themes yet.
                                    </td>
                                </tr>
                            ) : (
                                themes.data.map((theme) => (
                                    <tr key={theme.id} className="hover:bg-gray-50/80 dark:hover:bg-gray-900/30">
                                        <td className="px-4 py-2">
                                            {theme.primary_image_url ? (
                                                <img
                                                    src={theme.primary_image_url}
                                                    alt={theme.name}
                                                    className="h-12 w-20 rounded border object-cover dark:border-gray-600"
                                                />
                                            ) : (
                                                <span className="text-xs text-gray-400">No image</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-2 font-medium text-gray-800 dark:text-gray-100">{theme.name}</td>
                                        <td className="px-4 py-2 text-gray-600 dark:text-gray-300">{theme.slug}</td>
                                        <td className="px-4 py-2">{theme.sort_order}</td>
                                        <td className="px-4 py-2">
                                            <StatusBadge
                                                status={theme.is_active ? 'active' : 'inactive'}
                                                showTooltip
                                            />
                                        </td>
                                        <td className="px-4 py-2">
                                            <ActionButtons
                                                onEditClick={() => startEdit(theme)}
                                                toggleOn={theme.is_active}
                                                onToggle={() =>
                                                    router.patch(`${BASE}/${theme.id}/toggle`, {}, { preserveScroll: true })
                                                }
                                                toggleLabel={theme.is_active ? 'Disable' : 'Enable'}
                                                toggleTitle={
                                                    theme.is_active ? 'Deactivate theme' : 'Activate theme'
                                                }
                                                onDelete={() =>
                                                    router.delete(`${BASE}/${theme.id}`, { preserveScroll: true })
                                                }
                                                deleteConfirmTitle="Delete gift theme?"
                                                deleteConfirmMessage={`This will remove "${theme.name}" and its uploaded images from storage.`}
                                            />
                                            {theme.gallery_urls && theme.gallery_urls.length > 0 ? (
                                                <div className="mt-2 flex flex-wrap gap-1">
                                                    {theme.gallery_urls.slice(0, 4).map((url, idx) => (
                                                        <img key={`${theme.id}-${idx}`} src={url} alt="" className="h-8 w-10 rounded border object-cover dark:border-gray-600" />
                                                    ))}
                                                    {theme.gallery_urls.length > 4 ? (
                                                        <span className="text-[11px] text-gray-500">+{theme.gallery_urls.length - 4}</span>
                                                    ) : null}
                                                </div>
                                            ) : null}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AdminLayout>
    );
}

