import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    AdminImageDropzone,
    Breadcrumbs,
    RichTextEditor,
    ImageGalleryManager,
    HelpTooltip,
    type GalleryItem,
} from '@/Components/Admin';
import { Save, ArrowLeft, Package } from 'lucide-react';

function defaultCatalogAudience(sourceProvider: string): 'b2c' | 'b2b' | 'both' {
    if (sourceProvider === 'vouchagram_pull') return 'b2b';
    if (sourceProvider === 'vouchagram_send' || sourceProvider === 'vouchagram') return 'b2c';
    return 'both';
}

type ProductPayload = {
    id?: number;
    sku?: string | null;
    product_name?: string | null;
    name?: string | null;
    display_name?: string;
    source_provider?: string | null;
    catalog_audience?: string | null;
    selling_price?: string | number | null;
    mrp?: string | number | null;
    denomination?: string | number | null;
    discount_percentage?: string | number | null;
    gst_rate?: string | number | null;
    custom_description?: string | null;
    how_to_redeem?: string | null;
    terms_and_conditions?: string | null;
    description?: string | null;
    tnc?: string | null;
    show_product?: boolean | number;
    hot_deal_rank?: string | number | null;
    display_order?: string | number | null;
    media?: GalleryItem[];
};

type Props = {
    product: ProductPayload | null;
    default_catalog_audience?: 'b2c' | 'b2b' | 'both' | null;
};

const tabs = [
    { id: 'basic', label: 'Basic' },
    { id: 'content', label: 'Content & images' },
    { id: 'pricing', label: 'Pricing & tax' },
    { id: 'settings', label: 'Visibility' },
] as const;

export default function Form({ product, default_catalog_audience }: Props) {
    const isEdit = !!product?.id;
    const [tab, setTab] = useState<(typeof tabs)[number]['id']>('basic');
    const skipProviderAudienceOnce = useRef(false);

    const { data, setData, setDefaults, post, put, processing, errors, reset } = useForm({
        product_name: '',
        sku: '',
        source_provider: 'manual',
        catalog_audience: 'both' as 'b2c' | 'b2b' | 'both',
        selling_price: '' as string | number,
        mrp: '' as string | number,
        denomination: '' as string | number,
        discount_percentage: '' as string | number,
        gst_rate: '' as string | number,
        custom_description: '',
        how_to_redeem: '',
        terms_and_conditions: '',
        show_product: true,
        hot_deal_rank: '' as string | number,
        display_order: 0,
        custom_image: null as File | null,
    });

    useEffect(() => {
        if (!product) {
            reset();
            const sp = 'manual';
            const audience =
                default_catalog_audience === 'b2c' ||
                default_catalog_audience === 'b2b' ||
                default_catalog_audience === 'both'
                    ? default_catalog_audience
                    : defaultCatalogAudience(sp);
            skipProviderAudienceOnce.current = true;
            const hydrated = {
                product_name: '',
                sku: '',
                source_provider: sp,
                catalog_audience: audience,
                selling_price: '' as string | number,
                mrp: '' as string | number,
                denomination: '' as string | number,
                discount_percentage: '' as string | number,
                gst_rate: '' as string | number,
                custom_description: '',
                how_to_redeem: '',
                terms_and_conditions: '',
                show_product: true,
                hot_deal_rank: '' as string | number,
                display_order: 0,
                custom_image: null as File | null,
            };
            setDefaults(hydrated);
            setData(hydrated);
            return;
        }
        const sp = String(product.source_provider ?? 'manual');
        const hydrated = {
            product_name: String(product.product_name ?? product.name ?? product.display_name ?? ''),
            sku: String(product.sku ?? ''),
            source_provider: sp,
            catalog_audience: (product.catalog_audience as 'b2c' | 'b2b' | 'both') ?? defaultCatalogAudience(sp),
            selling_price: product.selling_price ?? '',
            mrp: product.mrp ?? '',
            denomination: product.denomination ?? '',
            discount_percentage: product.discount_percentage ?? '',
            gst_rate: product.gst_rate ?? '',
            custom_description: String(product.custom_description ?? ''),
            how_to_redeem: String(product.how_to_redeem ?? ''),
            terms_and_conditions: String(product.terms_and_conditions ?? ''),
            show_product: Boolean(product.show_product),
            hot_deal_rank:
                product.hot_deal_rank !== null && product.hot_deal_rank !== undefined && product.hot_deal_rank !== ''
                    ? Number(product.hot_deal_rank)
                    : '',
            display_order: Number(product.display_order ?? 0),
            custom_image: null as File | null,
        };
        setDefaults(hydrated);
        setData(hydrated);
    }, [product, default_catalog_audience, reset, setData, setDefaults]);

    useEffect(() => {
        if (isEdit) return;
        if (skipProviderAudienceOnce.current) {
            skipProviderAudienceOnce.current = false;
            return;
        }
        setData('catalog_audience', defaultCatalogAudience(data.source_provider));
    }, [data.source_provider, isEdit, setData]);

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit && product?.id) {
            put(`/panel/products/${product.id}`, { forceFormData: true });
        } else {
            post('/panel/products', { forceFormData: true });
        }
    };

    const saveContentOnly = () => {
        if (!product?.id) return;
        router.put(`/panel/products/${product.id}/content`, {
            custom_description: data.custom_description,
            how_to_redeem: data.how_to_redeem,
            terms_and_conditions: data.terms_and_conditions,
        }, { preserveScroll: true });
    };

    const mediaItems: GalleryItem[] = product?.media ?? [];

    return (
        <AdminLayout>
            <Head title={isEdit ? 'Edit product' : 'New product'} />
            <div className="space-y-6 max-w-5xl">
                <Breadcrumbs
                    items={[
                        { label: 'Products', href: '/panel/products' },
                        { label: isEdit ? `Edit #${product?.id}` : 'New' },
                    ]}
                />

                <div className="flex items-center gap-3">
                    <Link href="/panel/products" className="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <ArrowLeft size={22} />
                    </Link>
                    <Package className="text-indigo-600" size={28} />
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                        {isEdit ? `Edit: ${product?.display_name || product?.product_name || product?.sku}` : 'New product'}
                    </h1>
                </div>

                <div className="flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700 pb-2">
                    {tabs.map(t => (
                        <button
                            key={t.id}
                            type="button"
                            onClick={() => setTab(t.id)}
                            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                                tab === t.id
                                    ? 'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                            }`}
                        >
                            {t.label}
                        </button>
                    ))}
                </div>

                <form onSubmit={submit} className="space-y-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                    {tab === 'basic' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 flex items-center gap-1">
                                    Product name
                                    <HelpTooltip text="Shown to customers. You can make the title clearer than the supplier’s raw name." />
                                </label>
                                <input
                                    type="text"
                                    value={data.product_name}
                                    onChange={e => setData('product_name', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                                {errors.product_name && <p className="text-red-600 text-xs mt-1">{errors.product_name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SKU</label>
                                <input
                                    type="text"
                                    value={data.sku}
                                    onChange={e => setData('sku', e.target.value)}
                                    disabled={isEdit}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 px-3 py-2 text-sm disabled:opacity-70"
                                />
                                {errors.sku && <p className="text-red-600 text-xs mt-1">{errors.sku}</p>}
                            </div>
                            {!isEdit && (
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source provider</label>
                                    <select
                                        value={data.source_provider}
                                        onChange={e => setData('source_provider', e.target.value)}
                                        className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                    >
                                        {[
                                            'woohoo',
                                            'kgen',
                                            'value_design',
                                            'lysto',
                                            'ezpin',
                                            'gyftrr',
                                            'manual',
                                            'vouchagram',
                                            'vouchagram_send',
                                            'vouchagram_pull',
                                        ].map(p => (
                                            <option key={p} value={p}>
                                                {p}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.source_provider && <p className="text-red-600 text-xs mt-1">{errors.source_provider}</p>}
                                </div>
                            )}
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Single hero image upload (optional)
                                </label>
                                <AdminImageDropzone
                                    file={data.custom_image}
                                    onFileChange={f => setData('custom_image', f)}
                                    error={errors.custom_image}
                                    hintText="For multiple images use the Content tab after saving."
                                    maxFileBytes={5 * 1024 * 1024}
                                    compact
                                />
                            </div>
                        </div>
                    )}

                    {tab === 'content' && (
                        <div className="space-y-8">
                            {product?.description && (
                                <div className="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3">
                                    <strong className="block text-gray-700 dark:text-gray-300 mb-1">Supplier description (read-only)</strong>
                                    <div className="max-h-24 overflow-y-auto whitespace-pre-wrap">{String(product.description).slice(0, 500)}
                                        {String(product.description).length > 500 ? '…' : ''}
                                    </div>
                                </div>
                            )}
                            <div>
                                <label className="block text-sm font-medium mb-2 flex items-center gap-1">
                                    Customer-facing description (HTML)
                                    <HelpTooltip text="This replaces the supplier text on the storefront when saved. Use the toolbar for headings, lists, tables, and images." />
                                </label>
                                <RichTextEditor
                                    value={data.custom_description}
                                    onChange={html => setData('custom_description', html)}
                                    placeholder="Write a clear, friendly description…"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2">How to redeem</label>
                                <RichTextEditor
                                    value={data.how_to_redeem}
                                    onChange={html => setData('how_to_redeem', html)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2">Terms &amp; conditions</label>
                                <RichTextEditor
                                    value={data.terms_and_conditions}
                                    onChange={html => setData('terms_and_conditions', html)}
                                />
                            </div>
                            {isEdit && product?.id && (
                                <button
                                    type="button"
                                    onClick={saveContentOnly}
                                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700"
                                >
                                    Save content only
                                </button>
                            )}
                            {isEdit && product?.id && (
                                <>
                                    <hr className="border-gray-200 dark:border-gray-700" />
                                    <h3 className="font-semibold text-gray-900 dark:text-white">Image gallery</h3>
                                    <ImageGalleryManager productId={product.id} items={mediaItems} />
                                </>
                            )}
                            {!isEdit && (
                                <p className="text-sm text-gray-500">Save the product first, then you can upload a full image gallery.</p>
                            )}
                        </div>
                    )}

                    {tab === 'pricing' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Selling price (₹)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.selling_price}
                                    onChange={e => setData('selling_price', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                                {errors.selling_price && <p className="text-red-600 text-xs mt-1">{errors.selling_price}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">MRP (₹)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.mrp}
                                    onChange={e => setData('mrp', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Denomination</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.denomination}
                                    onChange={e => setData('denomination', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discount %</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.discount_percentage}
                                    onChange={e => setData('discount_percentage', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GST rate %</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.gst_rate}
                                    onChange={e => setData('gst_rate', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    )}

                    {tab === 'settings' && (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Catalog audience (B2B vs B2C)
                                </label>
                                <select
                                    value={data.catalog_audience}
                                    onChange={e => setData('catalog_audience', e.target.value as 'b2c' | 'b2b' | 'both')}
                                    className="w-full max-w-md rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                >
                                    <option value="b2c">B2C only (consumer storefront)</option>
                                    <option value="b2b">B2B only (tenant shop / wallet orders)</option>
                                    <option value="both">Both channels</option>
                                </select>
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Vouchagram Send defaults to B2C; Pull defaults to B2B. Catalog sync updates this unless you override here.
                                </p>
                                {errors.catalog_audience && (
                                    <p className="text-red-600 text-xs mt-1">{errors.catalog_audience}</p>
                                )}
                            </div>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.show_product}
                                    onChange={e => setData('show_product', e.target.checked)}
                                    className="rounded border-gray-300 text-indigo-600"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">Visible on storefront</span>
                            </label>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hot deal rank
                                    <span className="ml-1 font-normal text-gray-500">(leave empty to exclude from hot deals)</span>
                                </label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.hot_deal_rank}
                                    onChange={e => {
                                        const v = e.target.value;
                                        setData('hot_deal_rank', v === '' ? '' : Number(v));
                                    }}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Display order</label>
                                <input
                                    type="number"
                                    min="0"
                                    value={data.display_order}
                                    onChange={e => setData('display_order', Number(e.target.value))}
                                    className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 px-3 py-2 text-sm"
                                />
                            </div>
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <Link href="/panel/products" className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50"
                        >
                            <Save size={18} />
                            {processing ? 'Saving…' : isEdit ? 'Save product' : 'Create product'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}
