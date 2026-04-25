import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { Breadcrumbs, ConfirmDialog } from '@/Components/Admin';
import AdminImageDropzone from '@/Components/Admin/AdminImageDropzone';
import { Trash2, UploadCloud } from 'lucide-react';

type MediaAssetRow = {
    id: number;
    path: string;
    disk: string;
    mime: string | null;
    width: number | null;
    height: number | null;
    alt_text: string | null;
    url: string | null;
    created_at: string | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type Props = {
    assets: Paginated<MediaAssetRow>;
};

export default function Index({ assets }: Props) {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [uploading, setUploading] = useState(false);

    const onFileSelected = (file: File) => {
        setUploading(true);
        const fd = new FormData();
        fd.append('file', file);
        router.post('/panel/media', fd, { forceFormData: true, preserveScroll: true, onFinish: () => setUploading(false) });
    };

    const confirmDelete = () => {
        if (deleteId === null) return;
        router.delete(`/panel/media/${deleteId}`, { preserveScroll: true, onFinish: () => setDeleteId(null) });
    };

    return (
        <AdminLayout>
            <Head title="Media Library" />
            <div className="space-y-6">
                <Breadcrumbs items={[{ label: 'Media Library' }]} />
                <div className="flex items-center gap-2">
                    <UploadCloud className="text-indigo-600" size={26} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Media Library</h1>
                </div>

                <ConfirmDialog
                    open={deleteId !== null}
                    onClose={() => setDeleteId(null)}
                    onConfirm={confirmDelete}
                    title="Delete this asset?"
                    message="This will remove the file from storage. Sections referencing it must be updated manually."
                    confirmLabel="Delete"
                    variant="danger"
                />

                <AdminImageDropzone onFileSelected={onFileSelected} busy={uploading} maxFileBytes={8 * 1024 * 1024} />

                {assets.data.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 p-10 text-center text-gray-600 dark:border-gray-700 dark:text-gray-300">
                        No media assets yet.
                    </div>
                ) : (
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                        {assets.data.map(a => (
                            <div
                                key={a.id}
                                className="group relative overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                                {a.url ? (
                                    <img src={a.url} alt="" className="h-28 w-full object-cover bg-gray-50 dark:bg-gray-900/30" />
                                ) : (
                                    <div className="h-28 w-full flex items-center justify-center text-xs text-gray-500 bg-gray-50 dark:bg-gray-900/30">
                                        No preview
                                    </div>
                                )}
                                <div className="p-2">
                                    <p className="text-[11px] text-gray-600 dark:text-gray-300 truncate" title={a.path}>
                                        {a.path}
                                    </p>
                                    <p className="text-[10px] text-gray-400 truncate">
                                        #{a.id} {a.mime ?? ''}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setDeleteId(a.id)}
                                    className="absolute top-2 right-2 hidden group-hover:inline-flex items-center justify-center rounded-lg bg-white/90 p-2 text-red-600 shadow hover:bg-red-50 dark:bg-gray-900/70"
                                    title="Delete"
                                >
                                    <Trash2 size={16} />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

