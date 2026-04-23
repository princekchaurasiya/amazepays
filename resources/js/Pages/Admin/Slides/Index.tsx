import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { ActionButtons, Breadcrumbs } from '@/Components/Admin';
import { Image as ImageIcon, Plus } from 'lucide-react';

type SlideRow = {
    id: number;
    priority: number | null;
    status: number | null;
    display_on_page: string | null;
    img_alt_tag: string | null;
    desktop_image: string | null;
    image_mobile: string | null;
    product_id: number | null;
    category_id: number | null;
    brand_id: number | null;
};

type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};

type Props = {
    slides: Paginated<SlideRow>;
};

const HERO_SLIDES_BASE = '/panel/settings/hero-slides';

export default function Index({ slides }: Props) {
    return (
        <AdminLayout>
            <Head title="Hero carousel" />
            <div className="space-y-6">
                <Breadcrumbs
                    items={[
                        { label: 'Settings', href: '/panel/settings' },
                        { label: 'Hero carousel' },
                    ]}
                />
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <ImageIcon className="text-indigo-600" size={28} />
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Hero carousel</h1>
                    </div>
                    <Link
                        href={`${HERO_SLIDES_BASE}/create`}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium"
                    >
                        <Plus size={18} />
                        New slide
                    </Link>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                    <table className="min-w-full text-sm">
                        <thead className="bg-gray-50 dark:bg-gray-900/40 text-left text-gray-600 dark:text-gray-300">
                            <tr>
                                <th className="px-4 py-3">Preview</th>
                                <th className="px-4 py-3">Priority</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3">Page</th>
                                <th className="px-4 py-3">Link</th>
                                <th className="px-4 py-3 w-28">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                            {slides.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                        No slides yet. Create one for the storefront hero carousel.
                                    </td>
                                </tr>
                            ) : (
                                slides.data.map(row => (
                                    <tr key={row.id} className="hover:bg-gray-50/80 dark:hover:bg-gray-900/30">
                                        <td className="px-4 py-2">
                                            <div className="flex gap-2">
                                                {row.image_mobile ? (
                                                    <img
                                                        src={row.image_mobile}
                                                        alt=""
                                                        className="h-14 w-24 object-cover rounded border dark:border-gray-600"
                                                    />
                                                ) : row.desktop_image ? (
                                                    <img
                                                        src={row.desktop_image}
                                                        alt=""
                                                        className="h-14 w-24 object-cover rounded border dark:border-gray-600"
                                                    />
                                                ) : (
                                                    <span className="text-gray-400 text-xs">No image</span>
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-2">{row.priority ?? '—'}</td>
                                        <td className="px-4 py-2">{row.status === 1 ? 'Active' : 'Off'}</td>
                                        <td className="px-4 py-2">{row.display_on_page ?? '—'}</td>
                                        <td className="px-4 py-2 text-xs text-gray-600 dark:text-gray-400">
                                            {row.product_id
                                                ? `Product #${row.product_id}`
                                                : row.category_id
                                                  ? `Category #${row.category_id}`
                                                  : row.brand_id
                                                    ? `Brand #${row.brand_id}`
                                                    : '—'}
                                        </td>
                                        <td className="px-4 py-2">
                                            <ActionButtons
                                                editHref={`${HERO_SLIDES_BASE}/${row.id}/edit`}
                                                onDelete={() =>
                                                    router.delete(`${HERO_SLIDES_BASE}/${row.id}`, {
                                                        preserveScroll: true,
                                                    })
                                                }
                                                deleteConfirmTitle="Delete this slide?"
                                                deleteConfirmMessage="This slide will be removed from the hero carousel."
                                            />
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
