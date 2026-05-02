import React, { useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { AdminImageDropzone, Breadcrumbs } from '@/Components/Admin';
import { ArrowLeft, Save } from 'lucide-react';

type LookupRow = { id: number; name: string; sku?: string | null };

type SlidePayload = {
    id?: number;
    priority?: number | null;
    status?: number | null;
    display_on_page?: string | null;
    img_alt_tag?: string | null;
    small_header?: string | null;
    big_header?: string | null;
    cta_value?: string | null;
    cta_link?: string | null;
    slider_location?: string | null;
    product_id?: number | null;
    category_id?: number | null;
    brand_id?: number | null;
    custom_url?: string | null;
    link_type?: string | null;
    desktop_image_url?: string | null;
    image_mobile_url?: string | null;
};

type Props = {
    slide: SlidePayload | null;
    products: LookupRow[];
    categories: LookupRow[];
    brands: LookupRow[];
};

const HERO_SLIDES_BASE = '/panel/settings/hero-slides';

export default function Form({ slide, products, categories, brands }: Props) {
    const isEdit = !!slide?.id;

    const { data, setData, post, put, processing, errors, reset, transform } = useForm({
        status: 1,
        priority: '' as string | number,
        display_on_page: 'homepage',
        img_alt_tag: '',
        small_header: '',
        big_header: '',
        cta_value: '',
        cta_link: '',
        slider_location: '',
        product_id: '' as string | number,
        category_id: '' as string | number,
        brand_id: '' as string | number,
        custom_url: '',
        link_type: '',
        desktop_image_file: null as File | null,
        image_mobile_file: null as File | null,
    });

    transform(data => ({
        ...data,
        priority: data.priority === '' ? null : Number(data.priority),
        product_id: data.product_id === '' ? null : Number(data.product_id),
        category_id: data.category_id === '' ? null : Number(data.category_id),
        brand_id: data.brand_id === '' ? null : Number(data.brand_id),
    }));

    useEffect(() => {
        if (!slide?.id) {
            reset();
            return;
        }
        setData({
            status: slide.status === 0 ? 0 : 1,
            priority: slide.priority ?? '',
            display_on_page: slide.display_on_page ?? 'homepage',
            img_alt_tag: slide.img_alt_tag ?? '',
            small_header: slide.small_header ?? '',
            big_header: slide.big_header ?? '',
            cta_value: slide.cta_value ?? '',
            cta_link: slide.cta_link ?? '',
            slider_location: slide.slider_location ?? '',
            product_id: slide.product_id ?? '',
            category_id: slide.category_id ?? '',
            brand_id: slide.brand_id ?? '',
            custom_url: slide.custom_url ?? '',
            link_type: slide.link_type ?? '',
            desktop_image_file: null,
            image_mobile_file: null,
        });
    }, [slide, reset, setData]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && slide?.id) {
            put(`${HERO_SLIDES_BASE}/${slide.id}`, { forceFormData: true });
        } else {
            post(HERO_SLIDES_BASE, { forceFormData: true });
        }
    };

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit slide' : 'New slide'} />
            <div className="space-y-6 max-w-3xl">
                <Breadcrumbs
                    items={[
                        { label: 'Settings', href: '/panel/settings' },
                        { label: 'Hero carousel', href: HERO_SLIDES_BASE },
                        { label: isEdit ? 'Edit' : 'Create' },
                    ]}
                />
                <div className="flex items-center gap-3">
                    <Link
                        href={HERO_SLIDES_BASE}
                        className="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300"
                    >
                        <ArrowLeft size={16} />
                        Back
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        {isEdit ? 'Edit slide' : 'New slide'}
                    </h1>
                </div>

                <form onSubmit={submit} className="space-y-6 bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm">
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                        Upload wide images for desktop (~21:8) and mobile (~2:1). At least one image is recommended.
                    </p>

                    {slide?.desktop_image_url ? (
                        <div>
                            <p className="text-xs text-gray-500 mb-1">Current desktop</p>
                            <img
                                src={slide.desktop_image_url}
                                alt=""
                                className="max-h-32 rounded border dark:border-gray-600"
                            />
                        </div>
                    ) : null}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Desktop image
                        </label>
                        <AdminImageDropzone
                            file={data.desktop_image_file}
                            onFileChange={f => setData('desktop_image_file', f)}
                            error={errors.desktop_image_file}
                            compact
                        />
                    </div>

                    {slide?.image_mobile_url ? (
                        <div>
                            <p className="text-xs text-gray-500 mb-1">Current mobile</p>
                            <img
                                src={slide.image_mobile_url}
                                alt=""
                                className="max-h-32 rounded border dark:border-gray-600"
                            />
                        </div>
                    ) : null}
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">
                            Mobile image
                        </label>
                        <AdminImageDropzone
                            file={data.image_mobile_file}
                            onFileChange={f => setData('image_mobile_file', f)}
                            error={errors.image_mobile_file}
                            compact
                        />
                    </div>

                    <div className="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Status</label>
                            <select
                                value={data.status}
                                onChange={e => setData('status', Number(e.target.value) as 0 | 1)}
                                className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option value={1}>Active</option>
                                <option value={0}>Inactive</option>
                            </select>
                            {errors.status ? <p className="text-red-600 text-xs mt-1">{errors.status}</p> : null}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Priority (lower first)</label>
                            <input
                                type="number"
                                value={data.priority}
                                onChange={e => setData('priority', e.target.value)}
                                className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            />
                            {errors.priority ? <p className="text-red-600 text-xs mt-1">{errors.priority}</p> : null}
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Display on page</label>
                        <input
                            value={data.display_on_page}
                            onChange={e => setData('display_on_page', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            placeholder="homepage"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Image alt text</label>
                        <input
                            value={data.img_alt_tag}
                            onChange={e => setData('img_alt_tag', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </div>

                    <div className="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Product ID (optional)</label>
                            <select
                                value={data.product_id === '' ? '' : String(data.product_id)}
                                onChange={e =>
                                    setData('product_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                                className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option value="">— None —</option>
                                {products.map(p => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                        {p.sku ? ` (${p.sku})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Category (optional)</label>
                            <select
                                value={data.category_id === '' ? '' : String(data.category_id)}
                                onChange={e =>
                                    setData('category_id', e.target.value === '' ? '' : Number(e.target.value))
                                }
                                className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                                <option value="">— None —</option>
                                {categories.map(c => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Brand (optional)</label>
                        <select
                            value={data.brand_id === '' ? '' : String(data.brand_id)}
                            onChange={e => setData('brand_id', e.target.value === '' ? '' : Number(e.target.value))}
                            className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                            <option value="">— None —</option>
                            {brands.map(b => (
                                <option key={b.id} value={b.id}>
                                    {b.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Custom URL (optional)</label>
                        <input
                            value={data.custom_url}
                            onChange={e => setData('custom_url', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                            placeholder="https://…"
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">CTA link (optional)</label>
                        <input
                            value={data.cta_link}
                            onChange={e => setData('cta_link', e.target.value)}
                            className="w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium disabled:opacity-60"
                    >
                        <Save size={18} />
                        {processing ? 'Saving…' : 'Save'}
                    </button>
                </form>
            </div>
        </AdminLayout>
    );
}
